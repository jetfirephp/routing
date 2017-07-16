<?php

return [
	
	'/index2'	=> 'index.html',

    '/user2-:id' => [
        'use' => 'user.html',
        'arguments' => ['id' => '[0-9]+']
    ],

    '/log2' => [
        'use' => function(){
            return ['name' => 'JetFire'];
        },
        'template' => 'log.php'
    ],

	'/home2'	=> [
		'use' => 'JetFire\Routing\App\Block2\Controllers\Namespace2Controller@index',
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
	
	'/api/users' => [
		'use' => [
			'GET' => function($response){
				$response->setStatusCode(500);
			}
		]
	],
	'/api/users/1' => [
		'use' => [
			'GET' => function($response){
				$response->setHeaders(['Content-Type' => 'application/json']);
				return ['name' => 'Peter'];
			}
		]
	]
];