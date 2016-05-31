<?php

use JetFire\Routing\RouteCollection;

require __DIR__.'/Autoload.php';

$autoload =  new Autoload();
$autoload->addNamespace('JetFire\Routing',[
    __DIR__.'/src/Routing',
    __DIR__.'/',
]);
$autoload->addClass('Normal1Controller',__DIR__.'/Block1/Normal1Controller.php');
$autoload->register();


// Create RouteCollection instance
$collection = new RouteCollection();
$collection->addRoutes(__DIR__.'/Block1/routes.php',['view_dir'=>__DIR__.'/Block1/Views','prefix'=>'block']);
$router = new \JetFire\Routing\Router($collection);
$matcher = new \JetFire\Routing\Matcher\ArrayMatcher($router);

$router->addMatcher($matcher);
$router->setResponses([
    // you can use a closure to handle error
    '404' => function(){
        return '404';
    },
    '405' => function(){
        return '405';
    }
]);
// Run it!
$router->run();