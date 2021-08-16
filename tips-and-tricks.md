chgrp www-data storage -R
chmod g+rwx storage -R
chown www-data:www-data storage -R

#ignore files
git rm --cached file