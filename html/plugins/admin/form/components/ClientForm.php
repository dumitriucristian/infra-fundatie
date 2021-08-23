<?php namespace Admin\Form\Components;

use Cms\Classes\ComponentBase;
use Admin\Form\Models\Client;
use Validator;
use Input;
use Redirect;

class ClientForm extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Client Form',
            'description' => 'Form to create a client',
        ];
    }

    public function onSave()
    {
        $data = [
            'firstname' => Input::get('firstname'),
            'lastname' => Input::get('lastname'),
            'email' => Input::get('email'),
            'cnp' => Input::get('cnp'),
            'dad_initial' => Input::get('dad_initial'),
            'phone_number' => Input::get('phone_number'),
            'street' => Input::get('street'),
            'address_number' => Input::get('address_number'),
            'block' => Input::get('block'),
            'staircase' => Input::get('staircase'),
            'floor' => Input::get('floor'),
            'apartement' => Input::get('apartement'),
            'county' => Input::get('county'),
            'city' => Input::get('city'),
            'income_type' => Input::get('income_type'), 
        ];

        $validator = Validator::make($data,
        [
            'firstname' => ['required','string','max:254'],
            'lastname' => ['required','string','max:254'],
            'email' => ['required','string','max:254','email','unique:admin_form_client,email'],
            'cnp' => ['required','numeric','min:1000000000000','max:9999999999999'],
            'dad_initial' => ['required','string','max:4'],
            'phone_number' => ['required','string','size:10'],
            'street' => ['required','string','max:254'],
            'address_number' => ['required','string','max:10'],
            'block' => ['nullable','string','max:10'],
            'staircase' => ['nullable','string','max:10'],
            'floor' => ['nullable','integer',],
            'apartement' => ['nullable','string','max:10'],
            'county' => ['required','string','max:70'],
            'city' => ['required','string','max:70'],
            'income_type' => ['required','in:pensie/salariu,alta'],
        ],
        [
            'firstname.required' => 'Prenumele trebuie completat',
            'firstname.string' => 'Prenumele trebuie sa fie un text',
            'firstname.max' => 'Prenumele trebuie sa aiba maxim :max caractere',

            'lastname.required' => 'Numele trebuie completat',
            'lastname.string' => 'Numele trebuie sa fie un text',
            'lastname.max' => 'Numele trebuie sa aiba maxim :max caractere',

            'email.required' => 'Email-ul trebuie completat',
            'email.string' => 'Email-ul trebuie sa fie un text',
            'email.max' => 'Email-ul trebuie sa aiba maxim :max caractere',
            'email.email' => 'Email-ul nu este o adresa de email valida',
            'email.unique' => 'Adresa de email exista deja in baza de date',

            'cnp.required' => 'CNP-ul trebuie completat',
            'cnp.numeric' => 'CNP-ul trebuie sa fie un numar',
            'cnp.min' => 'CNP-ul trebuie sa aiba 13 cifre',
            'cnp.max' => 'CNP-ul trebuie sa aiba 13 cifre',

            'dad_initial.required' => 'Initiala tatalui trebuie completat',
            'dad_initial.string' => 'Initiala tatalui trebuie sa fie un text',
            'dad_initial.max' => 'Initiala tatalui trebuie sa aiba maxim :max caractere',

            'phone_number.required' => 'Numarul de telefon trebuie completat',
            'phone_number.string' => 'Numarul de telefon trebuie sa fie un text',
            'phone_number.size' => 'Numarul de telefon trebuie sa aiba :size caractere',

            'street.required' => 'Strada trebuie completat',
            'street.string' => 'Strada trebuie sa fie un text',
            'street.max' => 'Strada trebuie sa aiba maxim :max caractere',

            'address_number.required' => 'Numarul trebuie completat',
            'address_number.string' => 'Numarul trebuie sa fie un text',
            'address_number.max' => 'Numarul trebuie sa aiba maxim :max caractere',

            'block.string' => 'Blocul trebuie sa fie un text',
            'block.max' => 'Blocul trebuie sa aiba maxim :max caractere',

            'staircase.string' => 'Scara trebuie sa fie un text',
            'staircase.max' => 'Scara trebuie sa aiba maxim :max caractere',

            'floor.integer' => 'Scara trebuie sa fie un numar',
            
            'apartement.string' => 'Scara trebuie sa fie un text',
            'apartement.max' => 'Scara trebuie sa aiba maxim :max caractere',

            'county.required' => 'Judetul trebuie completat',
            'county.string' => 'Judetul trebuie sa fie un text',
            'county.max' => 'Judetul trebuie sa aiba maxim :max caractere',

            'city.required' => 'Orasul trebuie completat',
            'city.string' => 'Orasul trebuie sa fie un text',
            'city.max' => 'Orasul trebuie sa aiba maxim :max caractere',

            'income_type.required' => 'Tipul de venit trebuie completat',
        ]);

        if($validator->fails())
        {
            return Redirect::back()->withErrors($validator);
        }
        else 
        {
            Client::create($data);
            Redirect::to('');
        }

    }
    
}