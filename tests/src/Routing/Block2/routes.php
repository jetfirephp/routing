<?php

return [
	
	'/index2'	=> 'index.html',

    '/user2-:id' => [
        'use' => 'user.html',
        'arguments' => ['id' => '[0-9]+']
    ],

	'/home2'	=> [
		'use' => 'JetFire\Routing\Test\Block2\Controllers\Namespace2Controller@index',
	],

    '/home-:id'	=> [
        'use' => 'Namespace2Controller@index2',
        'arguments' => ['id' => '[0-9]+']
    ],

    '/contact2'	=> [
        'use' => 'Normal2Controller@contact',
        'name' => 'contact'
    ],
	
	'/search2' => [
		'use' => 'Normal2Controller@search',
		'method' => 'POST',
        'name' => 'search'
	],
];