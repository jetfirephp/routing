## JetFire PHP Routing
[![Build Status](https://travis-ci.org/jetfirephp/routing.svg?branch=master)](https://travis-ci.org/jetfirephp/routing)

A simple & powerful router for PHP 5.4+

### Features

* Support static & dynamic route patterns
* [Support REST routing](#rest)
* [Support reversed routing using named routes](#named-routes)
* [Smart routing](#smart-routing)
* [Array routing](#array-routing)
* [Template matching](#template-matching)
* [MVC matching](#mvc-matching)
* [Route Middleware](#middleware)
* [Custom response](#response)
* [Integration with other libraries](#libraries)

### Getting started

1. PHP 5.4+ is required
2. Install `JetFire\Routing` using Composer
3. Setup URL rewriting so that all requests are handled by index.php (see .htaccess file)

### Installation

Via [composer](https://getcomposer.org)

```bash
$ composer require jetfirephp/routing
```

### Usage

Create an instance of `JetFire\Routing\RouteCollection` and define your routes. Then create an instance of `JetFire\Routing\Router` and run your routes.

```php
// Require composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Create RouteCollection instance
$collection = new \JetFire\Routing\RouteCollection();

// Define routes
// ...

// Create an instance of Router
$router = new \JetFire\Routing\Router($collection)

// Run it!
$router->run();
```
### Matcher

`JetFire\Routing` provide 2 type of matcher for your routes : `JetFire\Routing\Match\RoutesMatch` and `JetFire\Routing\Match\SmartMatch`

<a name="smart-routing"></a>
#### Smart Routing

With Smart Routing you don't have to define your routes. Depending on the uri it can check if a target exist for the current url.
But you have to define your views directory path and the namespace for controllers to the collection like this :

```php
$options = [
    'path' => '_VIEW_DIR_PATH_',
    'namespace' => '_CONTROLLERS_NAMESPACE_'
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

##### Template matcher

Smart Routing check if an `index.php` file exist in `/_VIEW_DIR_PATH_/Home` directory. 

If you want to check for other extension (html,json,...) You can configure the router like this :

```php
$router->setConfig([
	
	// Define your template extension like this
	'viewExtension' => ['.php','.html','.twig','.json','.xml'],

]);
```

##### Mvc matcher

If Smart Routing failed to find the template then it checks if a controller with name `HomeController` located in the namespace `_CONTROLLERS_NAMESPACE_` has the `index` method.
Smart Routing support also dynamic routes. For example if the uri is : `/home/user/peter/parker` then you must have a method `user` with two parameters like this :

```php
class HomeController {
    public function user($firstName,$lastName){
        // $firstName = peter
        // $lasstName = parker
    }
}
```

If you want to disable SmartRouting you have to remove 'JetFire\Routing\Match\SmartMatch' from your router configuration :

```php
$router->setConfig([
    // default : 'matcher' => ['JetFire\Routing\Match\RoutesMatch', 'JetFire\Routing\Match\SmartMatch']
	'matcher' => ['JetFire\Routing\Match\RoutesMatch'],
]);
```
 
<a name="array-routing"></a>
#### Array Routing

With Array Routing you have to add your routes like this :

```php
$options = [
    'path' => '_VIEW_DIR_PATH_',
    'namespace' => '_CONTROLLERS_NAMESPACE_'
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

You have 3 actions possible for Array Routing. We assume you are using a separate file for your routes.
<a name="template-matching"></a>
##### Template Route

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
<a name="mvc-matching"></a>
##### Mvc Route


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

##### Closure Route

```php
return [
	
	// static route
	'/home/index' => function(){
		return 'Hello world !';
	},
	
	// dynamic route with arguments
	'/home/user-:id-:slug' => [
		'use' => 'function($id,$slug){
			return 'Hello User '.$id.'-'.$slug;
		},
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
$collection->addRoutes('admin_routes_path',['path' => 'admin_view_path' , 'namespace' => 'admin_controllers_namespace','prefix' => 'admin']);
$collection->addRoutes('public_routes_path',['path' => 'public_view_path' , 'namespace' => 'public_controllers_namespace']);

// Create an instance of Router
$router = new \JetFire\Routing\Router($collection)

// Run it!
$router->run();
```

### Router Configuration

Here are the list of router configuration that you can edit :

```php
$router->setConfig([
	
	// You can enable/disable a matcher or you can add you custom matcher class 
	// default matcher are JetFire\Routing\Match\RoutesMatch and JetFire\Routing\Match\SmartMatch
	'matcher' => ['JetFire\Routing\Match\RoutesMatch', 'JetFire\Routing\Match\SmartMatch'],

	// You can add/remove extension for views
	// default extension for views
	'viewExtension'      => ['.html', '.php', '.json', '.xml'],

	// If you use template engine library, you can use this to render the view
	// See the 'Integration with other libraries' section for more details
	'viewCallback'       => [],

	// See the Named Routes section for more details
	'generateRoutesPath' => false,
]);
```

### Collection Options

Here are the list of options that you can edit for each collection routes :

```php
$options = [
    // your view directory
    'path' => 'view_directory',
    // your controllers namespace
    'namespace' => 'controllers_namespace',
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

Supported only in `JetFire\Routing\Match\RoutesMatch`.
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
    	// Here you define you middleware class to be called
        'app\Middleware\Global',
    ],

	// block middleware are called when the current route block match one of the following block
    'block_middleware' => [
    	// You define here for each block the middleware class to be called
        'app/Blocks/PublicBlock/' => 'app\Middleware\Public',
        'app/Blocks/AdminBlock/' => 'app\Middleware\Admin',
        'app/Blocks/UserBlock/' => 'app\Middleware\User',
    ],
	
	// class middleware are called when the mvc router match one of the following controller
    'class_middleware' => [
    	// You define here for each controller the middleware class to be called
        'app/Blocks/PublicBlock/Controllers/HomeController' => 'app\Middleware\Home',
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
	public function handle(Route $route){
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
$router->setResponse([
	// you can use a closure to handle error
    '404' => function() use ($router){
        $router->route->setResponse('code',404);
        return '404';
    },
    
    // or a template
    '404' => 'app/Blocks/PublicBlock/Views/404.html',
    
    // or a controller
    '404' => 'ErrorController@notfound'
]);
```
<a name="libraries"></a>
### Integration with other libraries

If you want to integrate other template engine libraries like twig, smarty ... you have to set the 'viewCallback' in router.

```php
// Twig template engine
require_once '/path/to/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

// Other template engine
$tpl = new \Acme\Template\Template();

$router->setConfig([
	'viewCallback' => [

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
			$tpl->setdata($route->getData());
			$tpl->display();
		}
	],

	// Other configuration
	// ...
]);
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
	route										// JetFire\Routing\Route instance
	collection									// JetFire\Routing\RouteCollection instance
	middleware									// the middleware instance
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
	getResponse($key = null)					// return the route response (code,type,message)
	setResponse($key = null,$value = null)  	// set route response 
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