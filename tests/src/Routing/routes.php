<?php

return [
	
	'/index'	=> 'index.html',

    '/user-:id' => [
        'use' => 'user.html',
        'arguments' => ['id' => '[0-9]+']
    ],

	'/home'	=> [
		'use' => 'JetFire\Routing\Test\Controllers\NamespaceController@index',
	],

    '/contact'	=> [
        'use' => 'NormalController@contact',
        'name' => 'contact'
    ],
	
	'/search' => [
		'use' => 'NormalController@search',
		'method' => 'POST',
        'name' => 'search'
	],
];