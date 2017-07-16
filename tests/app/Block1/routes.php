<?php

return [

    '/index1' => 'index',

    '/user1-:id' => [
        'use' => 'user.html',
        'arguments' => ['id' => '[0-9]+']
    ],

    '/home1' => [
        'use' => 'JetFire\Routing\App\Block1\Namespace1Controller@index',
    ],

    '/home-:id' => [
        'use' => 'Namespace1Controller@index2',
        'arguments' => ['id' => '[0-9]+']
    ],

    '/contact1' => [
        'use' => 'Normal1Controller@contact',
        'template' => 'contact.php',
        'name' => 'contact'
    ],

    '/log' => [
        'use' => 'Normal1Controller@log',
        'template' => 'log.php',
        'name' => 'log'
    ],

    '/search1-:id-:name' => [
        'use' => function ($id, $name) {
            echo 'Search' . $id . $name;
        },
        'arguments' => ['id' => '[0-9]+', 'name' => '[a-z]*'],
    ],
];