<?php

return [
	
	'/'	=> 'index',

    '/user1-:id' => [
        'use' => 'user.html',
        'arguments' => ['id' => '[0-9]+']
    ],

	'/home1'	=> [
		'use' => 'JetFire\Routing\Block1\Namespace1Controller@index',
        'template' => 'index.php'
	],

    '/home-:id'	=> [
        'use' => 'JetFire\Routing\Block1\Namespace1Controller@index2',
        'arguments' => ['id' => '[0-9]+']
    ],

    '/contact1'	=> [
        'use' => 'Normal1Controller@contact',
        'name' => 'contact'
    ],

	'/search1-:id-:name' => [
		'use' => function($id,$name){
            return 'Search'.$id.$name;
        },
        'arguments' => ['id' => '[0-9]+','name' => '[a-z]*'],
	],
];