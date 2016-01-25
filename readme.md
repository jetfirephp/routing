## JetFire PHP Routing

A simple & powerful router for PHP 5.4+

### Features

* Supprot static & dynamic route patterns
* Smart matcher
* Template matching
* Controller matching
* Support REST routing
* Route Middleware
* Create your custom matcher

### Getting started

1. PHP 5.4+ is required
2. Install `JetFire\Routing` using Composer
3. Setup URL rewriting so that all requests are handled by index.php

### Installation

Via [composer](https://getcomposer.org)

```bash
$ composer require jetfirephp/routing
```

### Usage

Create an instance of `JetFire\Routing\RouteCollection` and define your routes. Then create an instance of `JetFire\Routing\Router` and run the following routes.

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

#### Smart Routing

With Smart Routing you don't have to define your routes. Depending on the url it can check if a target exist for the current url.

For exemple if the url is : `/home/index`

##### Template matcher

Smart Routing check if an `index.php` file exist in the `/Views/Home` directory. By default `Views/` is the directory containing all templates. But you can change it using `setConfig()` function. Look Views section for more details.

If you want to check for other extension (html,json,...) You can configure the router like this :

```php
$router->setConfig([
	
	// Define your template extension like this
	'viewExtension' => ['.php','.html','.twig','.json','.xml'],

]);
```

##### Controller matcher

If Smart Routing failed to find the template then it check if a controller with name `HomeController` has the `index` method.

#### Array Routing

With Array Routing you have to add your routes like this :

```php
// addRoutes expect an array argument 
$collection->addRoutes([
	'/home/index' => '_TARGET_'	
]);

// or a file containing an array
$collection->addRoutes('path_to_array_file');
```

We recommend that you define your routes in a separate file and add the path to the RouteCollection.

```php
// routes.php file
return [
	'/home/index' => '_TARGET_'
];
```

You have 3 action possible for Array Routing. We assume you are using a separate file for your routes.

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

##### Callback Route

```php
return [
	
	// static route
	'/home/index' => function(){
		return 'Hello world !';
	},
	
	// dynamic route with arguments
	'/home/user-:id-:slug' => [
		'use' => 'function(){
			return 'Hello world !';
		},
		'arguments' => ['id' => '[0-9]+','slug' => '[a-z-]*'],
	],

];
```

### Router Configuration

Here are the list of router configuration that you can edit :

```php
$router->setConfig([
	
	// You can enable/disable a matcher or you can add you custom matcher class 
	// default matcher are JetFire\Routing\Match\RoutesMatch and JetFire\Routing\Match\SmartMatch
	'matcher' => ['JetFire\Routing\Match\RoutesMatch', 'JetFire\Routing\Match\SmartMatch'],
	
	// Define you custom views directory
	'viewPath' => 'my_view_path',

	// You can add/remove extension for views
	// default extension for views
	'viewExtension'      => ['.html', '.php', '.json', '.xml'],

	// If you use template engine library, you can use this to render the view
	// See the 'Integration with other libraries' section for more details
	'viewCallback'       => [],

	// If you use a controller to render views, you can specify here your controllers path
	// default controllers directory is Controllers
	'controllerPath'     => 'Controllers',

	// See the Named Routes section for more details
	'generateRoutesPath' => false,
]);
```

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

$collection->getRoutePath('home.index'); // return http://your_domain/home/index
$collection->getRoutePath('home.user',[ 'id' => 1, 'slug' => 'toto']); // return http://your_domain/home/user-1-toto
```

Supported only in `JetFire\Routing\Match\RoutesMatch`.

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

### Views

By default `JetFire\Router` check all view templates in `Views` directory but you can change it like :

```php
$router->setConfig([
	
	// Define you custom views directory
	'viewPath' => 'my_view_path'

	// Other configuration
	// ...
]);
```
For example if you pass `app/Block/Public/routes.php` to `addRoutes()` function, `JetFire\Router` check if `Views` directory exist in `app/Block/Public/`.

```php
// call views in app/Block/PublicBlock/Views/ if the Views directory exist
$collection->addRoutes('app/Block/PublicBlock/routes.php');
// call views in app/Block/AdminBlock/Views/ if the Views directory exist
$collection->addRoutes('app/Block/AdminBlock/routes.php');
// call views in app/Block/UserBlock/Views/ if the Views directory exist
$collection->addRoutes('app/Block/UserBlock/routes.php');
```

That way you can separate your views in different blocks.

### Prefix

You can set a prefix for each routes collection like this :

```php
$collection->addRoutes('routes_file_1','prefix_1'); // all routes in routes_file_1 begin with prefix_1/
$collection->addRoutes('routes_file_2','prefix_2'); // all routes in routes_file_2 begin with prefix_2/
```
Or :
```php
$collection->addRoutes('routes_file_1'); 
$collection->addRoutes('routes_file_2'); 
$collection->setPrefix(['prefix_1','prefix_2']);
```

### Middleware

Middleware are called after the route target is defined.
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

The you have to set the middleware config file to the `RouteCollection` like this :

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

### Custom Response

If you want to handle custom 404,450... error template, you can do it like this :

```php
$router->setResponse([
	// you can use an anonymous function to handle error
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
	addRoutes($collection,$prefix = null)		// set your routes
	getRoutes($key = null)						// return all routes
	getRoutePath($name,$params = []) 			// return the url of route	
	setPrefix($prefix)							// $prefix can be a string (applied for every collection) 
												// or an array (for each collection you can specify a prefix)
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