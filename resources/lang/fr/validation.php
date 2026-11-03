<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max kilo-octets.',
    ],
    'date' => 'Le champ :attribute doit être une date valide.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'unique' => 'Cette valeur pour :attribute est déjà utilisée.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'mimes' => 'Le fichier :attribute doit être au format :values.',
    'attributes' => [
        'first_name' => 'prénom',
        'middle_name' => 'deuxième prénom',
        'last_name' => 'nom de famille',
        'country' => 'pays',
        'date_of_birth' => 'date de naissance',
        'place_of_birth' => 'lieu de naissance',
        'passport_number' => 'numéro de passeport',
        'passport_file' => 'document de passeport',
    ],
];
