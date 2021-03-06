<?php namespace Backend\Behaviors;

use Db;
use Lang;
use Flash;
use Request;
use Form as FormHelper;
use Backend\Classes\ControllerBehavior;
use October\Rain\Database\Model;
use ApplicationException;

/**
 * RelationController uses a combination of lists and forms for managing Model relations.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         'Backend.Behaviors.RelationController',
 *     ];
 *
 *     public $relationConfig = 'config_relation.yaml';
 *
 * The `$relationConfig` property makes reference to the configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class RelationController extends ControllerBehavior
{
    use \Backend\Traits\FormModelSaver;

    /**
     * @var const PARAM_FIELD postback parameter for the active relationship field
     */
    const PARAM_FIELD = '_relation_field';

    /**
     * @var const PARAM_MODE postback parameter for the active management mode
     */
    const PARAM_MODE = '_relation_mode';

    /**
     * @var const PARAM_EXTRA_CONFIG postback parameter for read only mode
     */
    const PARAM_EXTRA_CONFIG = '_relation_extra_config';

    /**
     * @var Backend\Widgets\Search searchWidget
     */
    protected $searchWidget;

    /**
     * @var Backend\Widgets\Toolbar toolbarWidget
     */
    protected $toolbarWidget;

    /**
     * @var Backend\Classes\WidgetBase viewWidget used for viewing (list or form)
     */
    protected $viewWidget;

    /**
     * @var \Backend\Widgets\Filter viewFilterWidget
     */
    protected $viewFilterWidget;

    /**
     * @var Backend\Classes\WidgetBase manageWidget used for relation management
     */
    protected $manageWidget;

    /**
     * @var \Backend\Widgets\Filter manageFilterWidget
     */
    protected $manageFilterWidget;

    /**
     * @var Backend\Classes\WidgetBase pivotWidget for relations with pivot data
     */
    protected $pivotWidget;

    /**
     * @var array requiredProperties
     */
    protected $requiredProperties = ['relationConfig'];

    /**
     * @var array requiredRelationProperties that must exist for each relationship definition
     */
    protected $requiredRelationProperties = ['label'];

    /**
     * @var array requiredConfig that must exist when applying the primary config file
     */
    protected $requiredConfig = [];

    /**
     * @var array actions visible in context of the controller
     */
    protected $actions = [];

    /**
     * @var object originalConfig values
     */
    protected $originalConfig;

    /**
     * @var array extraConfig provided by the relationRender method
     */
    protected $extraConfig;

    /**
     * @var bool initialized informs if everything is ready
     */
    protected $initialized = false;

    /**
     * @var string relationType
     */
    public $relationType;

    /**
     * @var string relationName
     */
    public $relationName;

    /**
     * @var Model relationModel
     */
    public $relationModel;

    /**
     * @var Model relationObject
     */
    public $relationObject;

    /**
     * @var Model model used as parent of the relationship
     */
    protected $model;

    /**
     * @var Model field for the relationship as defined in the configuration
     */
    protected $field;

    /**
     * @var string alias is something unique to pass to widgets
     */
    protected $alias;

    /**
     * @var array toolbarButtons to display in view mode.
     */
    protected $toolbarButtons;

    /**
     * @var Model viewModel is a reference to the model used for viewing (form only)
     */
    protected $viewModel;

    /**
     * @var string viewMode if relation has many (multi) or has one (single)
     */
    protected $viewMode;

    /**
     * @var string manageTitle used for the manage popup
     */
    protected $manageTitle;

    /**
     * @var string pivotTitle used for the pivot popup
     */
    protected $pivotTitle;

    /**
     * @var string manageMode of relation as list, form, or pivot
     */
    protected $manageMode;

    /**
     * @var string forceViewMode
     */
    protected $forceViewMode;

    /**
     * @var string forceManageMode
     */
    protected $forceManageMode;

    /**
     * @var string eventTarget that triggered an AJAX event (button, list)
     */
    protected $eventTarget;

    /**
     * @var int manageId is the primary id of an existing relation record
     */
    protected $manageId;

    /**
     * @var int foreignId of a selected pivot record
     */
    protected $foreignId;

    /**
     * @var string sessionKey used for deferred bindings
     */
    public $sessionKey;

    /**
     * @var bool readOnly disables the ability to add, update, delete or create relations
     */
    public $readOnly = false;

    /**
     * @var bool deferredBinding defers all binding actions using a session key
     */
    public $deferredBinding = false;

    /**
     * @var array customMessages contains default messages that you can override
     */
    protected $customMessages = [
        'buttonCreate' => 'backend::lang.relation.create_name',
        'buttonUpdate' => 'backend::lang.relation.update_name',
        'buttonAdd' => 'backend::lang.relation.add_name',
        'buttonLink' => 'backend::lang.relation.link_name',
        'buttonDelete' => 'backend::lang.relation.delete',
        'buttonRemove' => 'backend::lang.relation.remove',
        'buttonUnlink' => 'backend::lang.relation.unlink',
        'confirmDelete' => 'backend::lang.relation.delete_confirm',
        'confirmUnlink' => 'backend::lang.relation.unlink_confirm',
        'titlePreviewForm' => 'backend::lang.relation.preview_name',
        'titleCreateForm' => 'backend::lang.relation.create_name',
        'titleUpdateForm' => 'backend::lang.relation.update_name',
        'titleLinkForm' => 'backend::lang.relation.link_a_new',
        'titleAddForm' => 'backend::lang.relation.add_a_new',
        'titlePivotForm' => 'backend::lang.relation.related_data',
        'flashCreate' => 'backend::lang.form.create_success',
        'flashUpdate' => 'backend::lang.form.update_success',
        'flashDelete' => 'backend::lang.form.delete_success',
        'flashAdd' => 'backend::lang.relation.add_success',
        'flashLink' => 'backend::lang.relation.link_success',
        'flashRemove' => 'backend::lang.relation.remove_success',
        'flashUnlink' => 'backend::lang.relation.unlink_success',
    ];

    /**
     * __construct the behavior
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        $this->addJs('js/october.relation.js', 'core');
        $this->addCss('css/relation.css', 'core');

        /*
         * Build configuration
         */
        $this->config = $this->originalConfig = $this->makeConfig($controller->relationConfig, $this->requiredConfig);
    }

    /**
     * Validates the supplied field and initializes the relation manager.
     * @param string $field The relationship field.
     * @return string The active field name.
     */
    protected function validateField($field = null)
    {
        $field = $field ?: post(self::PARAM_FIELD);

        if ($field && $field !== $this->field) {
            $this->initRelation($this->model, $field);
        }

        if (!$field && !$this->field) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_definition', compact('field')));
        }

        return $field ?: $this->field;
    }

    /**
     * prepareVars for display
     */
    public function prepareVars()
    {
        $this->vars['relationManageId'] = $this->manageId;
        $this->vars['relationLabel'] = $this->config->label ?: $this->field;
        $this->vars['relationManageTitle'] = $this->manageTitle;
        $this->vars['relationPivotTitle'] = $this->pivotTitle;
        $this->vars['relationField'] = $this->field;
        $this->vars['relationType'] = $this->relationType;
        $this->vars['relationSearchWidget'] = $this->searchWidget;
        $this->vars['relationManageFilterWidget'] = $this->manageFilterWidget;
        $this->vars['relationViewFilterWidget'] = $this->viewFilterWidget;
        $this->vars['relationToolbarWidget'] = $this->toolbarWidget;
        $this->vars['relationManageMode'] = $this->manageMode;
        $this->vars['relationManageWidget'] = $this->manageWidget;
        $this->vars['relationToolbarButtons'] = $this->toolbarButtons;
        $this->vars['relationViewMode'] = $this->viewMode;
        $this->vars['relationViewWidget'] = $this->viewWidget;
        $this->vars['relationViewModel'] = $this->viewModel;
        $this->vars['relationPivotWidget'] = $this->pivotWidget;
        $this->vars['relationSessionKey'] = $this->relationGetSessionKey();
        $this->vars['relationExtraConfig'] = $this->extraConfig;
    }

    /**
     * The controller action is responsible for supplying the parent model
     * so it's action must be fired. Additionally, each AJAX request must
     * supply the relation's field name (_relation_field).
     */
    protected function beforeAjax()
    {
        if ($this->initialized) {
            return;
        }

        $this->controller->pageAction();
        if ($fatalError = $this->controller->getFatalError()) {
            throw new ApplicationException($fatalError);
        }

        $this->validateField();
        $this->prepareVars();
        $this->initialized = true;
    }

    //
    // Interface
    //

    /**
     * Prepare the widgets used by this behavior
     * @param Model $model
     * @param string $field
     * @return void
     */
    public function initRelation($model, $field = null)
    {
        if ($field === null) {
            $field = post(self::PARAM_FIELD);
        }

        $this->config = $this->originalConfig;
        $this->model = $model;
        $this->field = $field;

        if ($field === null) {
            return;
        }

        if (!$this->model) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_model', [
                'class' => get_class($this->controller),
            ]));
        }

        if (!$this->model instanceof Model) {
            throw new ApplicationException(Lang::get('backend::lang.model.invalid_class', [
                'model' => get_class($this->model),
                'class' => get_class($this->controller),
            ]));
        }

        if (!$this->getConfig($field)) {
            throw new ApplicationException(Lang::get('backend::lang.relation.missing_definition', compact('field')));
        }

        if ($extraConfig = post(self::PARAM_EXTRA_CONFIG)) {
            $this->applyExtraConfig($extraConfig);
        }

        $this->alias = camel_case('relation ' . $field);
        $this->config = $this->makeConfig($this->getConfig($field), $this->requiredRelationProperties);
        $this->controller->relationExtendConfig($this->config, $this->field, $this->model);

        /*
         * Relationship details
         */
        $this->relationName = $field;
        $this->relationType = $this->model->getRelationType($field);
        $this->relationObject = $this->model->{$field}();
        $this->relationModel = $this->relationObject->getRelated();

        $this->manageId = post('manage_id');
        $this->foreignId = post('foreign_id');
        $this->readOnly = $this->getConfig('readOnly');
        $this->deferredBinding = $this->getConfig('deferredBinding') || !$this->model->exists;
        $this->viewMode = $this->evalViewMode();
        $this->manageMode = $this->evalManageMode();
        $this->manageTitle = $this->evalManageTitle();
        $this->pivotTitle = $this->evalPivotTitle();
        $this->toolbarButtons = $this->evalToolbarButtons();

        /*
         * Toolbar widget
         */
        if ($this->toolbarWidget = $this->makeToolbarWidget()) {
            $this->toolbarWidget->bindToController();
        }

        /*
         * Search widget
         */
        if ($this->searchWidget = $this->makeSearchWidget()) {
            $this->searchWidget->bindToController();
        }

        /*
         * Filter widgets (optional)
         */
        if ($this->manageFilterWidget = $this->makeFilterWidget('manage')) {
            $this->controller->relationExtendManageFilterWidget($this->manageFilterWidget, $this->field, $this->model);
            $this->manageFilterWidget->bindToController();
        }

        if ($this->viewFilterWidget = $this->makeFilterWidget('view')) {
            $this->controller->relationExtendViewFilterWidget($this->viewFilterWidget, $this->field, $this->model);
            $this->viewFilterWidget->bindToController();
        }

        /*
         * View widget
         */
        if ($this->viewWidget = $this->makeViewWidget()) {
            $this->controller->relationExtendViewWidget($this->viewWidget, $this->field, $this->model);
            $this->viewWidget->bindToController();
        }

        /*
         * Manage widget
         */
        if ($this->manageWidget = $this->makeManageWidget()) {
            $this->controller->relationExtendManageWidget($this->manageWidget, $this->field, $this->model);
            $this->manageWidget->bindToController();
        }

        /*
         * Pivot widget
         */
        if ($this->manageMode === 'pivot' && $this->pivotWidget = $this->makePivotWidget()) {
            $this->controller->relationExtendPivotWidget($this->pivotWidget, $this->field, $this->model);
            $this->pivotWidget->bindToController();
        }
    }

    /**
     * Renders the relationship manager.
     * @param string $field The relationship field.
     * @param array $options
     * @return string Rendered HTML for the relationship manager.
     */
    public function relationRender($field, $options = [])
    {
        /*
         * Session key
         */
        if (is_string($options)) {
            $options = ['sessionKey' => $options];
        }

        if (isset($options['sessionKey'])) {
            $this->sessionKey = $options['sessionKey'];
        }

        /*
         * Apply options and extra config
         */
        $allowConfig = ['readOnly', 'recordUrl', 'recordOnClick'];
        $extraConfig = array_only($options, $allowConfig);
        $this->extraConfig = $extraConfig;
        $this->applyExtraConfig($extraConfig, $field);

        /*
         * Initialize
         */
        $this->validateField($field);
        $this->prepareVars();

        /*
         * Determine the partial to use based on the supplied section option
         */
        $section = $options['section'] ?? null;
        switch (strtolower($section)) {
            case 'toolbar':
                return $this->toolbarWidget ? $this->toolbarWidget->render() : null;

            case 'view':
                return $this->relationMakePartial('view');

            default:
                return $this->relationMakePartial('container');
        }
    }

    /**
     * Refreshes the relation container only, useful for returning in custom AJAX requests.
     * @param  string $field Relation definition.
     * @return array The relation element selector as the key, and the relation view contents are the value.
     */
    public function relationRefresh($field = null)
    {
        $field = $this->validateField($field);

        $result = ['#'.$this->relationGetId('view') => $this->relationRenderView($field)];
        if ($toolbar = $this->relationRenderToolbar($field)) {
            $result['#'.$this->relationGetId('toolbar')] = $toolbar;
        }

        if ($eventResult = $this->controller->relationExtendRefreshResults($field)) {
            $result = $eventResult + $result;
        }

        return $result;
    }

    /**
     * Renders the toolbar only.
     * @param string $field The relationship field.
     * @return string Rendered HTML for the toolbar.
     */
    public function relationRenderToolbar($field = null)
    {
        return $this->relationRender($field, ['section' => 'toolbar']);
    }

    /**
     * Renders the view only.
     * @param string $field The relationship field.
     * @return string Rendered HTML for the view.
     */
    public function relationRenderView($field = null)
    {
        return $this->relationRender($field, ['section' => 'view']);
    }

    /**
     * Controller accessor for making partials within this behavior.
     * @param string $partial
     * @param array $params
     * @return string Partial contents
     */
    public function relationMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('relation_'.$partial, $params + $this->vars, false);
        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }

    /**
     * Returns a unique ID for this relation and field combination.
     * @param string $suffix A suffix to use with the identifier.
     * @return string
     */
    public function relationGetId($suffix = null)
    {
        $id = class_basename($this);
        if ($this->field) {
            $id .= '-' . $this->field;
        }

        if ($suffix !== null) {
            $id .= '-' . $suffix;
        }

        return $this->controller->getId($id);
    }

    /**
     * Returns the active session key.
     */
    public function relationGetSessionKey($force = false)
    {
        if ($this->sessionKey && !$force) {
            return $this->sessionKey;
        }

        if (post('_relation_session_key')) {
            return $this->sessionKey = post('_relation_session_key');
        }

        if (post('_session_key')) {
            return $this->sessionKey = post('_session_key');
        }

        return $this->sessionKey = FormHelper::getSessionKey();
    }

    /**
     * relationGetMessage is a public API for accessing custom messages
     */
    public function relationGetMessage(string $code): string
    {
        return $this->getCustomLang($code);
    }

    //
    // Widgets
    //

    /**
     * Initialize a filter widget
     *
     * @param $type string Either 'manage' or 'view'
     * @return \Backend\Classes\WidgetBase|null
     */
    protected function makeFilterWidget($type)
    {
        if (!$this->getConfig($type . '[filter]')) {
            return null;
        }

        $filterConfig = $this->makeConfig($this->getConfig($type . '[filter]'));
        $filterConfig->alias = $this->alias . ucfirst($type) . 'Filter';
        $filterWidget = $this->makeWidget(\Backend\Widgets\Filter::class, $filterConfig);

        return $filterWidget;
    }


    protected function makeToolbarWidget()
    {
        $defaultConfig = [];

        /*
         * Add buttons to toolbar
         */
        $defaultButtons = null;

        if (!$this->readOnly && $this->toolbarButtons) {
            $defaultButtons = '~/modules/backend/behaviors/relationcontroller/partials/_toolbar.htm';
        }

        $defaultConfig['buttons'] = $this->getConfig('view[toolbarPartial]', $defaultButtons);

        /*
         * Make config
         */
        $toolbarConfig = $this->makeConfig($this->getConfig('toolbar', $defaultConfig));
        $toolbarConfig->alias = $this->alias . 'Toolbar';

        /*
         * Add search to toolbar
         */
        $useSearch = $this->viewMode === 'multi' && $this->getConfig('view[showSearch]');

        if ($useSearch) {
            $toolbarConfig->search = [
                'prompt' => 'backend::lang.list.search_prompt'
            ];
        }

        /*
         * No buttons, no search should mean no toolbar
         */
        if (empty($toolbarConfig->search) && empty($toolbarConfig->buttons)) {
            return;
        }

        $toolbarWidget = $this->makeWidget(\Backend\Widgets\Toolbar::class, $toolbarConfig);
        $toolbarWidget->cssClasses[] = 'list-header';

        return $toolbarWidget;
    }

    protected function makeSearchWidget()
    {
        if (!$this->getConfig('manage[showSearch]')) {
            return null;
        }

        $config = $this->makeConfig();
        $config->alias = $this->alias . 'ManageSearch';
        $config->growable = false;
        $config->prompt = 'backend::lang.list.search_prompt';
        $widget = $this->makeWidget(\Backend\Widgets\Search::class, $config);
        $widget->cssClasses[] = 'recordfinder-search';

        /*
         * Persist the search term across AJAX requests only
         */
        if (!Request::ajax()) {
            $widget->setActiveTerm(null);
        }

        return $widget;
    }

    protected function makeViewWidget()
    {
        $widget = null;

        /*
         * Multiple (has many, belongs to many)
         */
        if ($this->viewMode === 'multi') {
            $config = $this->makeConfigForMode('view', 'list');
            $config->model = $this->relationModel;
            $config->alias = $this->alias . 'ViewList';
            $config->showSorting = $this->getConfig('view[showSorting]', true);
            $config->defaultSort = $this->getConfig('view[defaultSort]');
            $config->recordsPerPage = $this->getConfig('view[recordsPerPage]');
            $config->showCheckboxes = $this->getConfig('view[showCheckboxes]', !$this->readOnly);
            $config->recordUrl = $this->getConfig('view[recordUrl]', null);
            $config->customViewPath = $this->getConfig('view[customViewPath]', null);

            $defaultOnClick = sprintf(
                "$.oc.relationBehavior.clickViewListRecord(':%s', '%s', '%s')",
                $this->relationModel->getKeyName(),
                $this->relationGetId(),
                $this->relationGetSessionKey()
            );

            if ($config->recordUrl) {
                $defaultOnClick = null;
            }
            elseif (
                !$this->makeConfigForMode('manage', 'form', false) &&
                !$this->makeConfigForMode('pivot', 'form', false)
            ) {
                $defaultOnClick = null;
            }

            $config->recordOnClick = $this->getConfig('view[recordOnClick]', $defaultOnClick);

            if ($emptyMessage = $this->getConfig('emptyMessage')) {
                $config->noRecordsMessage = $emptyMessage;
            }

            $widget = $this->makeWidget(\Backend\Widgets\Lists::class, $config);

            /*
             * Apply defined constraints
             */
            if ($sqlConditions = $this->getConfig('view[conditions]')) {
                $widget->bindEvent('list.extendQueryBefore', function ($query) use ($sqlConditions) {
                    $query->whereRaw($sqlConditions);
                });
            }
            elseif ($scopeMethod = $this->getConfig('view[scope]')) {
                $widget->bindEvent('list.extendQueryBefore', function ($query) use ($scopeMethod) {
                    $query->$scopeMethod($this->model);
                });
            }
            else {
                $widget->bindEvent('list.extendQueryBefore', function ($query) {
                    $this->relationObject->addDefinedConstraintsToQuery($query);
                });
            }

            /*
             * Constrain the query by the relationship and deferred items
             */
            $widget->bindEvent('list.extendQuery', function ($query) {
                $this->relationObject->setQuery($query);

                $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey() : null;

                if ($sessionKey) {
                    $this->relationObject->withDeferred($sessionKey);
                }
                elseif ($this->model->exists) {
                    $this->relationObject->addConstraints();
                }

                /*
                 * Allows pivot data to enter the fray
                 */
                if (in_array($this->relationType, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    $this->relationObject->setQuery($query->getQuery());
                    return $this->relationObject;
                }
            });

            /*
             * Constrain the list by the search widget, if available
             */
            if ($this->toolbarWidget && $this->getConfig('view[showSearch]')
                && $searchWidget = $this->toolbarWidget->getSearchWidget()
            ) {
                $searchWidget->bindEvent('search.submit', function () use ($widget, $searchWidget) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm());
                    return $widget->onRefresh();
                });

                // Linkage for JS plugins
                $searchWidget->listWidgetId = $widget->getId();

                // Persist the search term across AJAX requests only
                if (Request::ajax()) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm());
                }
                else {
                    $searchWidget->setActiveTerm(null);
                }
            }

            /*
             * Link the Filter Widget to the List Widget
             */
            if ($this->viewFilterWidget) {
                $this->viewFilterWidget->bindEvent('filter.update', function () use ($widget) {
                    return $widget->onFilter();
                });

                // Apply predefined filter values
                $widget->addFilter([$this->viewFilterWidget, 'applyAllScopesToQuery']);
            }
        }
        /*
         * Single (belongs to, has one)
         */
        elseif ($this->viewMode === 'single') {
            $this->viewModel = $this->relationObject->getResults()
                ?: $this->relationModel;

            $config = $this->makeConfigForMode('view', 'form');
            $config->model = $this->viewModel;
            $config->arrayName = class_basename($this->relationModel);
            $config->context = 'relation';
            $config->alias = $this->alias . 'ViewForm';

            $widget = $this->makeWidget(\Backend\Widgets\Form::class, $config);
            $widget->previewMode = true;
        }

        return $widget;
    }

    protected function makeManageWidget()
    {
        $widget = null;

        /*
         * List / Pivot
         */
        if ($this->manageMode === 'list' || $this->manageMode === 'pivot') {
            $isPivot = $this->manageMode === 'pivot';

            $config = $this->makeConfigForMode('manage', 'list');
            $config->model = $this->relationModel;
            $config->alias = $this->alias . 'ManageList';
            $config->showSetup = false;
            $config->showCheckboxes = $this->getConfig('manage[showCheckboxes]', !$isPivot);
            $config->showSorting = $this->getConfig('manage[showSorting]', !$isPivot);
            $config->defaultSort = $this->getConfig('manage[defaultSort]');
            $config->recordsPerPage = $this->getConfig('manage[recordsPerPage]');

            if ($this->viewMode === 'single') {
                $config->showCheckboxes = false;
                $config->recordOnClick = sprintf(
                    "$.oc.relationBehavior.clickManageListRecord(':%s', '%s', '%s')",
                    $this->relationModel->getKeyName(),
                    $this->relationGetId(),
                    $this->relationGetSessionKey()
                );
            }
            elseif ($config->showCheckboxes) {
                $config->recordOnClick = "$.oc.relationBehavior.toggleListCheckbox(this)";
            }
            elseif ($isPivot) {
                $config->recordOnClick = sprintf(
                    "$.oc.relationBehavior.clickManagePivotListRecord(':%s', '%s', '%s')",
                    $this->relationModel->getKeyName(),
                    $this->relationGetId(),
                    $this->relationGetSessionKey()
                );
            }

            $widget = $this->makeWidget(\Backend\Widgets\Lists::class, $config);

            /*
             * Apply defined constraints
             */
            if ($sqlConditions = $this->getConfig('manage[conditions]')) {
                $widget->bindEvent('list.extendQueryBefore', function ($query) use ($sqlConditions) {
                    $query->whereRaw($sqlConditions);
                });
            }
            elseif ($scopeMethod = $this->getConfig('manage[scope]')) {
                $widget->bindEvent('list.extendQueryBefore', function ($query) use ($scopeMethod) {
                    $query->$scopeMethod($this->model);
                });
            }
            else {
                $widget->bindEvent('list.extendQueryBefore', function ($query) {
                    $this->relationObject->addDefinedConstraintsToQuery($query);

                    // Reset any orders that may have come from the definition
                    // because it has a tendency to break things
                    $query->getQuery()->orders = [];
                });
            }

            /*
             * Link the Search Widget to the List Widget
             */
            if ($this->searchWidget) {
                $this->searchWidget->bindEvent('search.submit', function () use ($widget) {
                    $widget->setSearchTerm($this->searchWidget->getActiveTerm());
                    return $widget->onRefresh();
                });

                // Linkage for JS plugins
                $this->searchWidget->listWidgetId = $widget->getId();

                // Persist the search term across AJAX requests only
                if (Request::ajax()) {
                    $widget->setSearchTerm($this->searchWidget->getActiveTerm());
                }
            }

            /*
             * Link the Filter Widget to the List Widget
             */
            if ($this->manageFilterWidget) {
                $this->manageFilterWidget->bindEvent('filter.update', function () use ($widget) {
                    return $widget->onFilter();
                });

                // Apply predefined filter values
                $widget->addFilter([$this->manageFilterWidget, 'applyAllScopesToQuery']);
            }
        }
        /*
         * Form
         */
        elseif ($this->manageMode === 'form') {
            if (!$config = $this->makeConfigForMode('manage', 'form', false)) {
                return null;
            }

            $config->model = $this->relationModel;
            $config->arrayName = class_basename($this->relationModel);
            $config->context = $this->evalFormContext('manage', !!$this->manageId);
            $config->alias = $this->alias . 'ManageForm';

            /*
             * Existing record
             */
            if ($this->manageId) {
                $model = $config->model->find($this->manageId);
                if ($model) {
                    $config->model = $model;
                } else {
                    throw new ApplicationException(Lang::get('backend::lang.model.not_found', [
                        'class' => get_class($config->model),
                        'id' => $this->manageId,
                    ]));
                }
            }

            $widget = $this->makeWidget(\Backend\Widgets\Form::class, $config);
        }

        if (!$widget) {
            return null;
        }

        /*
         * Exclude existing relationships
         */
        if ($this->manageMode === 'pivot' || $this->manageMode === 'list') {
            $widget->bindEvent('list.extendQuery', function ($query) {
                /*
                 * Where not in the current list of related records
                 */
                $existingIds = $this->findExistingRelationIds();
                if (count($existingIds)) {
                    $query->whereNotIn($this->relationModel->getQualifiedKeyName(), $existingIds);
                }
            });
        }

        return $widget;
    }

    protected function makePivotWidget()
    {
        $config = $this->makeConfigForMode('pivot', 'form');
        $config->model = $this->relationModel;
        $config->arrayName = class_basename($this->relationModel);
        $config->context = $this->evalFormContext('pivot', !!$this->manageId);
        $config->alias = $this->alias . 'ManagePivotForm';

        $foreignKeyName = $this->relationModel->getQualifiedKeyName();

        /*
         * Existing record
         */
        if ($this->manageId) {
            $hydratedModel = $this->relationObject->where($foreignKeyName, $this->manageId)->first();

            if ($hydratedModel) {
                $config->model = $hydratedModel;
            }
            else {
                throw new ApplicationException(Lang::get('backend::lang.model.not_found', [
                    'class' => get_class($config->model),
                    'id' => $this->manageId,
                ]));
            }
        }
        /*
         * New record
         */
        else {
            if ($this->foreignId) {
                $foreignModel = $this->relationModel
                    ->whereIn($foreignKeyName, (array) $this->foreignId)
                    ->first();

                if ($foreignModel) {
                    $foreignModel->exists = false;
                    $config->model = $foreignModel;
                }
            }

            $pivotModel = $this->relationObject->newPivot();
            $config->model->setRelation('pivot', $pivotModel);
        }

        return $this->makeWidget(\Backend\Widgets\Form::class, $config);
    }

    //
    // AJAX (Buttons)
    //

    public function onRelationButtonAdd()
    {
        $this->eventTarget = 'button-add';

        return $this->onRelationManageForm();
    }

    public function onRelationButtonCreate()
    {
        $this->eventTarget = 'button-create';

        return $this->onRelationManageForm();
    }

    public function onRelationButtonDelete()
    {
        return $this->onRelationManageDelete();
    }

    public function onRelationButtonLink()
    {
        $this->eventTarget = 'button-link';

        return $this->onRelationManageForm();
    }

    public function onRelationButtonUnlink()
    {
        return $this->onRelationManageRemove();
    }

    public function onRelationButtonRemove()
    {
        return $this->onRelationManageRemove();
    }

    public function onRelationButtonUpdate()
    {
        $this->eventTarget = 'button-update';

        return $this->onRelationManageForm();
    }

    //
    // AJAX (List events)
    //

    public function onRelationClickManageList()
    {
        return $this->onRelationManageAdd();
    }

    public function onRelationClickManageListPivot()
    {
        return $this->onRelationManagePivotForm();
    }

    public function onRelationClickViewList()
    {
        $this->eventTarget = 'list';
        return $this->onRelationManageForm();
    }

    //
    // AJAX
    //

    public function onRelationManageForm()
    {
        $this->beforeAjax();

        if ($this->manageMode === 'pivot' && $this->manageId) {
            return $this->onRelationManagePivotForm();
        }

        // The form should not share its session key with the parent
        $this->vars['newSessionKey'] = str_random(40);

        $view = 'manage_' . $this->manageMode;

        return $this->relationMakePartial($view);
    }

    /**
     * Create a new related model
     */
    public function onRelationManageCreate()
    {
        $this->forceManageMode = 'form';
        $this->beforeAjax();
        $saveData = $this->manageWidget->getSaveData();
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey(true) : null;
        $parentModel = $this->relationObject->getParent();

        if ($this->viewMode === 'multi') {
            $newModel = $this->relationModel;

            /*
             * In special cases, has one/many will require a foreign key set
             * to pass any constraints imposed by the database. This emulates
             * the "create" method on the relation object.
             */
            $isSavable = $parentModel->exists && in_array($this->relationType, ['hasOne', 'hasMany', 'morphOne', 'morphMany']);
            if ($isSavable) {
                $newModel->setAttribute(
                    $this->relationObject->getForeignKeyName(),
                    $this->relationObject->getParentKey()
                );
            }

            $modelsToSave = $this->prepareModelsToSave($newModel, $saveData);
            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save(null, $this->manageWidget->getSessionKey());
            }

            // No need to add relationships that have already been associated
            if (!$isSavable) {
                $this->relationObject->add($newModel, $sessionKey);
            }
        }
        elseif ($this->viewMode === 'single') {
            $newModel = $this->viewModel;
            $this->viewWidget->setFormValues($saveData);

            /*
             * Has one relations will save as part of the add() call.
             */
            if ($this->deferredBinding || in_array($this->relationType, ['hasOne', 'morphOne'])) {
                $newModel->save(null, $this->manageWidget->getSessionKey());
            }

            $this->relationObject->add($newModel, $sessionKey);

            /*
             * Belongs to relations won't save when using add() so
             * it should occur if the conditions are right.
             */
            if (
                !$this->deferredBinding &&
                $this->relationType === 'belongsTo' &&
                $parentModel->exists
            ) {
                $parentModel->save();
            }
        }

        $this->showFlashMessage('flashCreate');

        return $this->relationRefresh();
    }

    /**
     * Updated an existing related model's fields
     */
    public function onRelationManageUpdate()
    {
        $this->forceManageMode = 'form';
        $this->beforeAjax();
        $saveData = $this->manageWidget->getSaveData();

        if ($this->viewMode === 'multi') {
            $model = $this->manageWidget->model;
            $modelsToSave = $this->prepareModelsToSave($model, $saveData);
            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save(null, $this->manageWidget->getSessionKey());
            }
        }
        elseif ($this->viewMode === 'single') {
            $this->viewWidget->setFormValues($saveData);
            $this->viewModel->save(null, $this->manageWidget->getSessionKey());
        }

        $this->showFlashMessage('flashUpdate');

        return $this->relationRefresh();
    }

    /**
     * Delete an existing related model completely
     */
    public function onRelationManageDelete()
    {
        $this->beforeAjax();

        /*
         * Multiple (has many, belongs to many)
         */
        if ($this->viewMode === 'multi') {
            if (($checkedIds = post('checked')) && is_array($checkedIds)) {
                foreach ($checkedIds as $relationId) {
                    if (!$obj = $this->relationModel->find($relationId)) {
                        continue;
                    }

                    $obj->delete();
                }
            }
        }
        /*
         * Single (belongs to, has one)
         */
        elseif ($this->viewMode === 'single') {
            $relatedModel = $this->viewModel;
            if ($relatedModel->exists) {
                $relatedModel->delete();
            }

            $this->resetViewWidgetModel();
            $this->viewModel = $this->relationModel;
        }

        $this->showFlashMessage('flashDelete');

        return $this->relationRefresh();
    }

    /**
     * Add an existing related model to the primary model
     */
    public function onRelationManageAdd()
    {
        $this->beforeAjax();

        $recordId = post('record_id');
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey() : null;

        /*
         * Add
         */
        if ($this->viewMode === 'multi') {
            $checkedIds = $recordId ? [$recordId] : post('checked');

            if (is_array($checkedIds)) {
                /*
                 * Remove existing relations from the array
                 */
                $existingIds = $this->findExistingRelationIds($checkedIds);
                $checkedIds = array_diff($checkedIds, $existingIds);
                $foreignKeyName = $this->relationModel->getKeyName();

                $models = $this->relationModel->whereIn($foreignKeyName, $checkedIds)->get();
                foreach ($models as $model) {
                    $this->relationObject->add($model, $sessionKey);
                }
            }

            $this->showFlashMessage('flashAdd');
        }
        /*
         * Link
         */
        elseif ($this->viewMode === 'single') {
            if ($recordId && ($model = $this->relationModel->find($recordId))) {
                $this->relationObject->add($model, $sessionKey);
                $this->viewWidget->setFormValues($model->attributes);

                /*
                 * Belongs to relations won't save when using add() so
                 * it should occur if the conditions are right.
                 */
                if (!$this->deferredBinding && $this->relationType === 'belongsTo') {
                    $parentModel = $this->relationObject->getParent();
                    if ($parentModel->exists) {
                        $parentModel->save();
                    }
                }
            }

            $this->showFlashMessage('flashLink');
        }

        return $this->relationRefresh();
    }

    /**
     * Remove an existing related model from the primary model
     */
    public function onRelationManageRemove()
    {
        $this->beforeAjax();

        $recordId = post('record_id');
        $sessionKey = $this->deferredBinding ? $this->relationGetSessionKey() : null;
        $relatedModel = $this->relationModel;

        /*
         * Remove
         */
        if ($this->viewMode === 'multi') {
            $checkedIds = $recordId ? [$recordId] : post('checked');

            if (is_array($checkedIds)) {
                $foreignKeyName = $relatedModel->getKeyName();

                $models = $relatedModel->whereIn($foreignKeyName, $checkedIds)->get();
                foreach ($models as $model) {
                    $this->relationObject->remove($model, $sessionKey);
                }
            }

            $this->showFlashMessage('flashRemove');
        }
        /*
         * Unlink
         */
        elseif ($this->viewMode === 'single') {
            if ($this->relationType === 'belongsTo') {
                $this->relationObject->dissociate();
                $this->relationObject->getParent()->save();
            }
            elseif ($this->relationType === 'hasOne' || $this->relationType === 'morphOne') {
                if ($obj = $relatedModel->find($recordId)) {
                    $this->relationObject->remove($obj, $sessionKey);
                }
                elseif ($this->viewModel->exists) {
                    $this->relationObject->remove($this->viewModel, $sessionKey);
                }
            }

            $this->resetViewWidgetModel();

            $this->showFlashMessage('flashUnlink');
        }

        return $this->relationRefresh();
    }

    /**
     * Add multiple items using a single pivot form.
     */
    public function onRelationManageAddPivot()
    {
        return $this->onRelationManagePivotForm();
    }

    public function onRelationManagePivotForm()
    {
        $this->beforeAjax();

        $this->vars['foreignId'] = $this->foreignId ?: post('checked');

        return $this->relationMakePartial('pivot_form');
    }

    public function onRelationManagePivotCreate()
    {
        $this->beforeAjax();

        /*
         * If the pivot model fails for some reason, abort the sync
         */
        Db::transaction(function () {
            /*
             * Add the checked IDs to the pivot table
             */
            $foreignIds = (array) $this->foreignId;
            $this->relationObject->sync($foreignIds, false);

            /*
             * Save data to models
             */
            $foreignKeyName = $this->relationModel->getQualifiedKeyName();
            $hydratedModels = $this->relationObject->whereIn($foreignKeyName, $foreignIds)->get();
            $saveData = $this->pivotWidget->getSaveData();

            foreach ($hydratedModels as $hydratedModel) {
                $modelsToSave = $this->prepareModelsToSave($hydratedModel, $saveData);
                foreach ($modelsToSave as $modelToSave) {
                    $modelToSave->save(null, $this->pivotWidget->getSessionKey());
                }
            }
        });

        $this->showFlashMessage('flashAdd');

        return ['#'.$this->relationGetId('view') => $this->relationRenderView()];
    }

    public function onRelationManagePivotUpdate()
    {
        $this->beforeAjax();

        $foreignKeyName = $this->relationModel->getQualifiedKeyName();
        $hydratedModel = $this->relationObject->where($foreignKeyName, $this->manageId)->first();
        $saveData = $this->pivotWidget->getSaveData();

        $modelsToSave = $this->prepareModelsToSave($hydratedModel, $saveData);
        foreach ($modelsToSave as $modelToSave) {
            $modelToSave->save(null, $this->pivotWidget->getSessionKey());
        }

        $this->showFlashMessage('flashUpdate');

        return ['#'.$this->relationGetId('view') => $this->relationRenderView()];
    }

    //
    // Overrides
    //

    /**
     * Provides an opportunity to manipulate the field configuration.
     * @param object $config
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendConfig($config, $field, $model)
    {
    }

    /**
     * Provides an opportunity to manipulate the view widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendViewWidget($widget, $field, $model)
    {
    }

    /**
     * Provides an opportunity to manipulate the manage widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendManageWidget($widget, $field, $model)
    {
    }

    /**
     * Provides an opportunity to manipulate the pivot widget.
     * @param Backend\Classes\WidgetBase $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendPivotWidget($widget, $field, $model)
    {
    }

    /**
     * Provides an opportunity to manipulate the manage filter widget.
     * @param \Backend\Widgets\Filter $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendManageFilterWidget($widget, $field, $model)
    {
    }

    /**
     * Provides an opportunity to manipulate the view filter widget.
     * @param \Backend\Widgets\Filter $widget
     * @param string $field
     * @param \October\Rain\Database\Model $model
     */
    public function relationExtendViewFilterWidget($widget, $field, $model)
    {
    }

    /**
     * The view widget is often refreshed when the manage widget makes a change,
     * you can use this method to inject additional containers when this process
     * occurs. Return an array with the extra values to send to the browser, eg:
     *
     * return ['#myCounter' => 'Total records: 6'];
     *
     * @param string $field
     * @return array
     */
    public function relationExtendRefreshResults($field)
    {
    }

    //
    // Helpers
    //

    /**
     * Returns the existing record IDs for the relation.
     */
    protected function findExistingRelationIds($checkIds = null)
    {
        $foreignKeyName = $this->relationModel->getQualifiedKeyName();

        $results = $this->relationObject
            ->getBaseQuery()
            ->select($foreignKeyName);

        if ($checkIds !== null && is_array($checkIds) && count($checkIds)) {
            $results = $results->whereIn($foreignKeyName, $checkIds);
        }

        return $results->lists($foreignKeyName);
    }

    /**
     * Determine the default buttons based on the model relationship type.
     * @return string
     */
    protected function evalToolbarButtons()
    {
        $buttons = $this->getConfig('view[toolbarButtons]');

        if ($buttons === false) {
            return null;
        }
        elseif (is_string($buttons)) {
            return array_map('trim', explode('|', $buttons));
        }
        elseif (is_array($buttons)) {
            return $buttons;
        }

        if ($this->manageMode === 'pivot') {
            return ['add', 'remove'];
        }

        switch ($this->relationType) {
            case 'hasMany':
            case 'morphMany':
            case 'morphToMany':
            case 'morphedByMany':
            case 'belongsToMany':
                return ['create', 'add', 'delete', 'remove'];

            case 'hasOne':
            case 'morphOne':
            case 'belongsTo':
                return ['create', 'update', 'link', 'delete', 'unlink'];

            case 'hasManyThrough':
                return [];
        }
    }

    /**
     * evalViewMode determines the view mode based on the model relationship type
     * @return string
     */
    protected function evalViewMode()
    {
        if ($this->forceViewMode) {
            return $this->forceViewMode;
        }

        switch ($this->relationType) {
            case 'hasMany':
            case 'morphMany':
            case 'morphToMany':
            case 'morphedByMany':
            case 'belongsToMany':
            case 'hasManyThrough':
                return 'multi';

            case 'hasOne':
            case 'morphOne':
            case 'belongsTo':
                return 'single';
        }
    }

    /**
     * evalManageTitle determines the management mode popup title
     */
    protected function evalManageTitle(): string
    {
        if ($customTitle = $this->getConfig('manage[title]')) {
            return $customTitle;
        }

        switch ($this->manageMode) {
            case 'pivot':
            case 'list':
                if ($this->eventTarget === 'button-link') {
                    return $this->getCustomLang('titleLinkForm');
                }
                else {
                    return $this->getCustomLang('titleAddForm');
                }
            case 'form':
                if ($this->readOnly) {
                    return $this->getCustomLang('titlePreviewForm');
                }
                elseif ($this->manageId) {
                    return $this->getCustomLang('titleUpdateForm');
                }
                else {
                    return $this->getCustomLang('titleCreateForm');
                }
        }

        return '';
    }

    /**
     * evalPivotTitle determines the pivot mode popup title
     */
    protected function evalPivotTitle(): string
    {
        if ($customTitle = $this->getConfig('pivot[title]')) {
            return $customTitle;
        }

        return $this->getCustomLang('titlePivotForm');
    }

    /**
     * evalManageMode determines the management mode based on the relation type and settings
     * @return string
     */
    protected function evalManageMode()
    {
        if ($mode = post(self::PARAM_MODE)) {
            return $mode;
        }

        if ($this->forceManageMode) {
            return $this->forceManageMode;
        }

        switch ($this->eventTarget) {
            case 'button-create':
            case 'button-update':
                return 'form';

            case 'button-link':
                return 'list';
        }

        switch ($this->relationType) {
            case 'belongsTo':
                return 'list';

            case 'morphToMany':
            case 'morphedByMany':
            case 'belongsToMany':
                if (isset($this->config->pivot)) {
                    return 'pivot';
                }
                elseif ($this->eventTarget === 'list') {
                    return 'form';
                }
                else {
                    return 'list';
                }

            case 'hasOne':
            case 'morphOne':
            case 'hasMany':
            case 'morphMany':
            case 'hasManyThrough':
                if ($this->eventTarget === 'button-add') {
                    return 'list';
                }

                return 'form';
        }
    }

    /**
     * evalFormContext determines supplied form context
     */
    protected function evalFormContext($mode = 'manage', $exists = false)
    {
        $config = $this->config->{$mode} ?? [];

        if (($context = array_get($config, 'context')) && is_array($context)) {
            $context = $exists
                ? array_get($context, 'update')
                : array_get($context, 'create');
        }

        if (!$context) {
            $context = $exists ? 'update' : 'create';
        }

        return $context;
    }

    /**
     * applyExtraConfig
     */
    protected function applyExtraConfig($config, $field = null)
    {
        if (!$field) {
            $field = $this->field;
        }

        if (!$config || !isset($this->originalConfig->{$field})) {
            return;
        }

        if (
            !is_array($config) &&
            (!$config = @json_decode(@base64_decode($config), true))
        ) {
            return;
        }

        $parsedConfig = array_only($config, ['readOnly']);
        $parsedConfig['view'] = array_only($config, ['recordUrl', 'recordOnClick']);

        $this->originalConfig->{$field} = array_replace_recursive(
            $this->originalConfig->{$field},
            $parsedConfig
        );
    }

    /**
     * makeConfigForMode returns the configuration for a mode (view, manage, pivot) for an
     * expected type (list, form) and uses fallback configuration
     */
    protected function makeConfigForMode($mode = 'view', $type = 'list', $throwException = true)
    {
        $config = null;

        /*
         * Look for $this->config->view['list']
         */
        if (
            isset($this->config->{$mode}) &&
            array_key_exists($type, $this->config->{$mode})
        ) {
            $config = $this->config->{$mode}[$type];
        }
        /*
         * Look for $this->config->list
         */
        elseif (isset($this->config->{$type})) {
            $config = $this->config->{$type};
        }

        /*
         * Apply substitutes:
         *
         * - view.list => manage.list
         */
        if (!$config) {
            if ($mode === 'manage' && $type === 'list') {
                return $this->makeConfigForMode('view', $type);
            }

            if ($throwException) {
                throw new ApplicationException('Missing configuration for '.$mode.'.'.$type.' in RelationController definition '.$this->field);
            }

            return false;
        }

        return $this->makeConfig($config);
    }

    /**
     * resetViewWidgetModel is an internal method used when deleting singular relationships
     */
    protected function resetViewWidgetModel()
    {
        $this->viewWidget->model = $this->relationModel;
        $this->viewWidget->setFormValues([]);
    }

    /**
     * getCustomLang parses custom messages provided by the config
     */
    protected function getCustomLang(string $name, string $default = null, array $extras = []): string
    {
        $foundKey = $this->getConfig("customMessages[${name}]");

        if ($foundKey === null) {
            $foundKey = $this->originalConfig->customMessages[$name] ?? null;
        }

        if ($foundKey === null) {
            $foundKey = $default;
        }

        if ($foundKey === null) {
            $foundKey = $this->customMessages[$name] ?? '???';
        }

        $vars = $extras + [
            'name' => Lang::get($this->getConfig('label', $this->field))
        ];

        return Lang::get($foundKey, $vars);
    }

    /**
     * showFlashMessage displays a flash message if its found
     */
    protected function showFlashMessage(string $message): void
    {
        if (!$this->useFlashMessages()) {
            return;
        }

        if ($message = $this->getCustomLang($message)) {
            Flash::success($message);
        }
    }

    /**
     * useFlashMessages determines if flash messages should be used
     */
    protected function useFlashMessages(): bool
    {
        $useFlash = $this->getConfig('showFlash');

        if ($useFlash === null) {
            $useFlash = $this->originalConfig->showFlash ?? null;
        }

        if ($useFlash === null) {
            $useFlash = true;
        }

        return $useFlash;
    }

    /**
     * relationGetManageWidget returns the manage widget used by this behavior
     * @return \Backend\Classes\WidgetBase
     */
    public function relationGetManageWidget()
    {
        return $this->manageWidget;
    }

    /**
     * relationGetViewWidget returns the view widget used by this behavior
     * @return \Backend\Classes\WidgetBase
     */
    public function relationGetViewWidget()
    {
        return $this->viewWidget;
    }
}
