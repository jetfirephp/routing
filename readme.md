## JetFire PHP Routing
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/aeb83524-44bc-4501-90dc-18041cc7a84a/mini.png)](https://insight.sensiolabs.com/projects/aeb83524-44bc-4501-90dc-18041cc7a84a) [![Build Status](https://travis-ci.org/jetfirephp/routing.svg?branch=master)](https://travis-ci.org/jetfirephp/routing) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jetfirephp/routing/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jetfirephp/routing/?branch=master)

A simple & powerful router for PHP 5.4+

### Features

V1.3
* [Support subdomain](#subdomain)

V1.2
* [ClosureTemplate resolver](#closureTemplate-resolver)
* [ControllerTemplate resolver](#controllerTemplate-resolver)
* Possibility to choose a resolver

V1.1
* [Support dependency injection container](#config)
* [Add your custom matcher and dispatcher](#custom-matcher)

V1.0
* Support static & dynamic route patterns
* [Support REST routing](#rest)
* [Support reversed routing using named routes](#named-routes)
* [Uri matcher](#uri-matcher)
* [Array matcher](#array-matcher)
* [Closure resolver](#closure-resolver)
* [Template resolver](#template-resolver)
* [Controller resolver](#controller-resolver)
* [Route Middleware](#middleware)
* [Custom response](#response)
* [Integration with other libraries](#libraries)

### Getting started

1. PHP 5.4+ is required
2. Install `JetFire\Routing` using Composer
3. Setup URL rewriting so that all requests are handled by index.php

### Installation

Via [composer](https://getcomposer.org)

```bash
$ composer require jetfirephp/routing
```

#### .htaccess

```php
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.[a-zA-Z0-9\-\_\/]*)$ index.php?url=$1 [QSA,L]
```

### Usage

Create an instance of `JetFire\Routing\RouteCollection` and define your routes. Then create an instance of `JetFire\Routing\Router` and run your routes.

```php
// Require composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Create RouteCollection instance
$collection = new \JetFire\Routing\RouteCollection();

// Define your routes
// ...

// Create an instance of Router
$router = new \JetFire\Routing\Router($collection)

// select your matcher
$matcher1 =  new \JetFire\Routing\Matcher\ArrayMatcher($router);
$matcher2 =  new \JetFire\Routing\Matcher\UriMatcher($router);

// set your matcher to the router
$router->setMatcher([$matcher1,$matcher2])

// Run it!
$router->run();
```
### Matcher

`JetFire\Routing` provide 2 type of matcher for your routes : `JetFire\Routing\Matcher\ArrayMatcher` and `JetFire\Routing\Matcher\UriMatcher`

<a name="uri-matcher"></a>
#### Uri Matcher

With Uri Matcher you don't have to define your routes. Depending on the uri it can check if a target exist for the current url.
But you have to define your views directory path and controllers namespace to the collection :

```php
$options = [
    'view_dir' => '_VIEW_DIR_PATH_',
    'ctrl_namespace' => '_CONTROLLERS_NAMESPACE_'
];
$collection = new \JetFire\Routing\RouteCollection(null,$options);
// or
$collection = new \JetFire\Routing\RouteCollection();
$collection->setOption($options);
// or
$collection = new \JetFire\Routing\RouteCollection();
$collection->addRoutes(null,$options)
```

For example if the uri is : `/home/index`

##### Resolver

Here are the list of Uri Matcher resolver :

```php
$resolver = [
    'isControllerAndTemplate',
    'isController',
    'isTemplate'
];
```

##### Template resolver

Uri Matcher check if an `index.php` file exist in `/_VIEW_DIR_PATH_/Home` directory. 

If you want to check for other extension (html,json,...) You can configure the router like this :

```php
$router->setConfig([
	
	// Define your template extension like this
	'templateExtension' => ['.php','.html','.twig','.json','.xml'],

]);
```

##### Controller resolver

With Controller resolver, Uri Matcher checks if a controller with name `HomeController` located in the namespace `_CONTROLLERS_NAMESPACE_` has the `index` method.
You have to require your controller before matching or you can use your custom autoloader to load your controllers.
Uri Matcher support also dynamic routes. For example if the uri is : `/home/user/peter/parker` then you must have a method `user` with two parameters like this :

```php
class HomeController {
    public function user($firstName,$lastName){
        // $firstName = peter
        // $lastName = parker
    }
}
```

 
<a name="array-matcher"></a>
#### Array Matcher

With Array Matcher you have to add your routes like this :

```php
$options = [
    'view_dir' => '_VIEW_DIR_PATH_',
    'ctrl_namespace' => '_CONTROLLERS_NAMESPACE_'
];

// addRoutes expect an array argument 
$collection->addRoutes([
	'/home/index' => '_TARGET_'	
],$options);

// or a file containing an array
$collection->addRoutes('path_to_array_file',$options);
```

We recommend that you define your routes in a separate file and pass the path to `addRoutes()` method.

```php
// routes.php file
return [
	'/home/index' => '_TARGET_'
];
```

##### Resolver

Here are the list of Uri Matcher resolver :

```php
$resolver = [
    'isControllerAndTemplate',
    'isClosureAndTemplate',
    'isClosure',
    'isController',
    'isTemplate'
];
```

You have 5 actions possible for Array Routing. We assume you are using a separate file for your routes.

<a name="template-resolver"></a>
##### Template resolver

```php
return [
	
	// static route
	'/home/index' => 'Home/index.php',
	
	// dynamic route with arguments
	'/home/user-:id-:slug' => [
		'use' => 'Home/page.html',
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
	],

];
```
<a name="controller-resolver"></a>
##### Controller resolver

```php
return [
	
	// static route
	'/home/index' => 'HomeController@index',

	// dynamic route with arguments
	'/home/user-:id-:slug' => [
		'use' => 'HomeController@page',
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
	],
	
];
```
<a name="controllerTemplate-resolver"></a>
##### Controller and Template resolver

```php
return [
	
	// controller and template resolver
	// call first the controller and render then the template
	// if the template is not found, the controller is returned
	'/home/log' => [
	    'use' => 'HomeController@log',
	    'template' => 'Home/log.php', //in your controller you can return an array of data that you can access in your template
	],
	
	// dynamic route with arguments
    '/home/user-:id-:slug' => [
        'use' => 'HomeController@page',
        'template' => 'Home/log.php',
        'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
    ],

];
```
<a name="closure-resolver"></a>
##### Closure resolver

```php
return [
		
	// static route
	'/home/index' => function(){
		return 'Hello world !';
	},
	
	// dynamic route with arguments
	'/home/user-:id-:slug' => [
		'use' => function($id,$slug){
			return 'Hello User '.$id.'-'.$slug;
		},
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
	],
	
];
```
<a name="closureTemplate-resolver"></a>
##### Closure and Template resolver

```php
return [
	
	// closure and template matching
    // call first the closure and render then the template
    '/home/log' => [
        'use' => function(){
            return ['name' => 'Peter'];
        }
        'template' => 'Home/log.php', // in log.php you can access the return data like this : $name ='Peter'
    ],
    
    '/home/user-:id-:slug' => [
        'use' => function($id,$slug){
            return ['id' => $id,'slug' => $slug];
        },
        'template' => 'Home/log.php',
        'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
    ],
    
];
```

### Block Routes

With `JetFire\Routing` you have the ability to create block routes to better organize your code.
For example , if you have an administration for your website , you can create block only for this section and another block to the public part like this :

```php
// Create RouteCollection instance
$collection = new \JetFire\Routing\RouteCollection();

// Block routes
$collection->addRoutes('admin_routes_path',['view_dir' => 'admin_view_path' , 'ctrl_namespace' => 'admin_controllers_namespace','prefix' => 'admin']);
$collection->addRoutes('public_routes_path',['view_dir' => 'public_view_path' , 'ctrl_namespace' => 'public_controllers_namespace']);

// Create an instance of Router
$router = new \JetFire\Routing\Router($collection)
// Select your matcher
$router->addRouter(new \JetFire\Routing\Matcher\ArrayMatcher($router));

// Run it!
$router->run();
```
<a name="config"></a>
### Router Configuration

Here are the list of router configuration that you can edit :

```php
$router->setConfig([

	// You can add/remove extension for views
	// default extension for views
	'templateExtension'      => ['.html', '.php', '.json', '.xml'],

	// If you use template engine library, you can use this to render the view
	// See the 'Integration with other libraries' section for more details
	'templateCallback'       => [],
	
	// If you want to add a dependency injection container for your controllers constructor or method
	// for example if your controller 'HomeController' method 'log' method require a class like this : public function log(Request $request)
	// by default :
	'di'                => function($class){
	                            return new $class;
	                       },

	// See the Named Routes section for more details
	'generateRoutesPath' => false,
]);
```

### Collection Options

Here are the list of options that you can edit for each collection routes :

```php
$options = [
    // your view directory
    'view_dir' => 'view_directory',
    // your controllers namespace
    'ctrl_namespace' => 'controllers_namespace',
    // your routes prefix
    'prefix' => 'your_prefix'
];
```

<a name="named-routes"></a>
### Named Routes

You can specify a name for each route like this :

```php
return [
	
	'/home/index' => [
		'use' => 'Home/index.html',
		'name' => 'home.index'
	],

	'/home/user-:id-:slug' => [
		'use' => 'HomeController@user',
		'name' => 'home.user',
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
	],
];
```
And then to get the url of this route you can do like this :

```php
// You have to enable generateRoutesPath to get routes url
$router->setConfig([
	'generateRoutesPath' => true,
	// Other configuration
	// ...
]);

// Reverse routing
$collection->getRoutePath('home.index'); // return http://your_domain/home/index
$collection->getRoutePath('home.user',[ 'id' => 1, 'slug' => 'toto']); // return http://your_domain/home/user-1-toto
```

Supported only in `JetFire\Routing\Matcher\ArrayMatcher`.
<a name="rest"></a>
### REST Routing

You can specify the request method for each route like this :

```php
return [
	
	'/home/index' => [
		'use' => 'Home/index.html',
		'name' => 'home.index',
		'method' => 'POST' // Single method accepted
	],

	'/home/user-:id-:slug' => [
		'use' => 'HomeController@user',
		'name' => 'home.user',
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
		'method' => ['GET','POST'] // Multiple methods accepted
	],
];
```

### Prefix

You can set a prefix for each routes collection like this :

```php
$collection->addRoutes('routes_file_1',['prefix' => 'prefix_1']); // all routes in routes_file_1 begin with prefix_1/
$collection->addRoutes('routes_file_2',['prefix' => 'prefix_2']); // all routes in routes_file_2 begin with prefix_2/
```
Or :
```php
$collection->addRoutes('routes_file_1'); 
$collection->addRoutes('routes_file_2'); 
$collection->setPrefix(['prefix_1','prefix_2']);
```
<a name="middleware"></a>
### Middleware

Middlewares are called after the route target is defined.
You have to create a middleware config file like this :

```php
// Your middleware file
return [
	
	// global middleware are called every time
    'global_middleware' => [    
    	// Here you define your middleware class to be called
        'app\Middleware\Global',
    ],

	// block middleware are called when the current route block match one of the following block
    'block_middleware' => [
    	// You define here for each block the middleware class to be called
        '/app/Blocks/PublicBlock/' => 'app\Middleware\Public',
        '/app/Blocks/AdminBlock/' => 'app\Middleware\Admin',
        '/app/Blocks/UserBlock/' => 'app\Middleware\User',
    ],
	
	// class middleware are called when the controller router match one of the following controller
    'class_middleware' => [
    	// You define here for each controller the middleware class to be called
        '/app/Blocks/PublicBlock/Controllers/HomeController' => 'app\Middleware\Home',
    ],

	// route middleware are called when the current route match one of the following middleware name
    'route_middleware' => [
    	// You define here a name to the middleware and assign the class to be called
    	// You have to specify this name to the route like this : `'middleware' => 'home'`
        'home' => 'app\Middleware\App'
    ],

];
```

Then you have to set the middleware config file to the `RouteCollection` like this :

```php
$collection->setMiddleware('your_middleware_file');
```

Let see how to create your Middleware Class. For example we take the Global middleware :

```php
namespace app\Middleware;

class Global{

	// Middleware class must implements handle method
	// object passed in argument will be inject automatically
	public function handle(){
		// here you put your code
		// ...
	}
}
```
See the API section to learn how to handle your $route in middleware class.
<a name="response"></a>
### Custom Response

If you want to handle custom 404,450... error template, you can do it like this :

```php
$router->setResponses([
	// you can use a closure to handle error
    '404' => function(){
        return '404';
    },
    
    // or a template
    '404' => 'app/Blocks/PublicBlock/Views/404.html',
    
    // or a controller method
    '404' => 'ErrorController@notFound'
]);
```
<a name="custom-matcher"></a>
### Custom Matcher and Dispatcher

If the default matcher and dispatcher doesn't match your expectation, you can write your own matcher and dispatcher like this :

```php
class MyCustomMatcher implements MatcherInterface{
    
    public function __construct(Router $router);

    // in this method you can check if the current uri match your expectation
    // return true or false
    // if it match you have to set your route target with an array of params and the dispatcher class name to be called
    // $this->setTarget(['dispatcher' => '\My\Custom\Dispatcher\Class\Name', 'other_params' => 'values']);
    public function match();
    
    // set your route target $this->router->route->setTarget($target);
    public function setTarget($target = []);

    // set your resolver
    public function setResolver($resolver = []);
    
    // you can add multiple resolver method in the same matcher
    public function addResolver($resolver);
    
    // to retrieve your resolver
    public function getResolver();
    
    // dispatcher yo be called
    public function setDispatcher($dispatcher = []);
    
    public function addDispatcher($dispatcher);
}

class MyCustomDispatcher implements DispatcherInterface{
   
    public function __construct(Route $route);
       
    // your target to call
    // you can get your route target information with $this->route->getTarget()
    public function call();
}

$router->addMatcher('MyCustomMatcher');
```

You can also override the default matcher like this :

```php
class MyCustomMatcher extends ArrayMatcher implements MatcherInterface{
    
    public function __construct(Router $router){
        parent::__construct($router);
        // your custom match method
        $this->addResolver('customResolver');
    }

    public function customResolver(){
        // your code here
        // ...
        // then you set the route target with the dispatcher
    }
}

class MyCustomDispatcher implements DispatcherInterface{
   
    public function __construct(Route $route);
       
    // your target to call
    // you can get your route target information with $this->route->getTarget()
    public function call();
}
```

<a name="libraries"></a>
### Integration with other libraries

If you want to integrate other template engine libraries like twig, smarty ... you have to set the 'templateCallback' in router.

```php
// Twig template engine
require_once '/path/to/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

// Other template engine
$tpl = new \Acme\Template\Template();

$router->setConfig([
	'templateCallback' => [

		// if the router find a template with twig enxtension then it will call the twig template engine
		'twig' => function($route){				
			$loader = new Twig_Loader_Filesystem($route->getTarget('block'));
			$twig = new Twig_Environment($loader, []);
			$template = $twig->loadTemplate($route->getTarget('template'));
			echo $template->render($route->getData());
		},
	
		// for other template engine
		'tpl' => function($route) use ($tpl){
			$tpl->load($route->getTarget('template'));
			$tpl->setData($route->getData());
			$tpl->display();
		}
	],

	// Other configuration
	// ...
]);
```

<a name="subdomain"></a>
### Subdomain

```php
return [
    '{subdomain}.{host}/home' => [
         'use' => 'AdminController@index',
         'name' => 'admin.home.index',
         'subdomain' => 'admin' // could be a regex for multiple subdomain
    ]
];
```

Or if you want to add a subdomain for a bloc, you have to add this line in your route collection options :

```php
$options = [
    // ...
    'subdomain' => 'your_subdomain'
];
```

### API

Below is a list of the public methods and variables in the common classes you will most likely use.

```php
// JetFire\Routing\RouteCollection
$collection->
	routesByName								// routes url by their name
	countRoutes									// count routes block
	middleware									// middleware config file
	addRoutes($collection,$options = [])		// set your routes
	getRoutes($key = null)						// return all routes
	getRoutePath($name,$params = []) 			// return the url of route	
	setPrefix($prefix)							// $prefix can be a string (applied for every collection) 
												// or an array (for each collection you can specify a prefix)
    setOption($options = [])                    // set your routes option												
	setMiddleware($middleware)					// set you middleware config file
	generateRoutesPath() 						// generate routes url by their name

// JetFire\Routing\Router
$router->
	response							        // JetFire\Routing\ResponseInterface instance
	route										// JetFire\Routing\Route instance
	collection									// JetFire\Routing\RouteCollection instance
	middleware									// the middleware instance
	resolver									// list of resolver
	dispatcher									// the dispatcher instance
	setConfig($config) 							// router configuration
	getConfig() 								// get router configuration
	run() 										// run the router with the request url
	setResponse($response = [])					// set your custom 404,405,500 ... routes 

// JetFire\Routing\Route
$route->
	set($args = []) 							// set your route array parameters
	getUrl() 									// return the route url
	setUrl($url) 								// set the route url
	getName()			 						// return the route name
	setName($name) 								// set the route name
	getCallback()  								// return the route callback (template,controller or anonymous function)
	setCallback($callback) 						// set the route callback
	getMethod() 								// return the route method (GET,POST,PUT,DELETE)
	getDetail() 								// return the route detail 
	setDetail($detail) 							// set the route detail
	addDetail($key,$value)						// add a detail for the route
	getTarget($key = null) 						// return the route target (dispatcher,template or controller|action or function)
	setTarget($target = [])  					// set the route target
	hasTarget($key = null)						// check if the route has the following target
	getData() 								    // return data for the route
	__call($name,$arguments)  					// magic call to get or set route detail

```

### License

The JetFire Routing is released under the MIT public license : http://www.opensource.org/licenses/MIT. 
