<?php
namespace JetFire\Routing\Test;

use InvalidArgumentException;
use JetFire\Routing\Matcher\ArrayMatcher;
use JetFire\Routing\Matcher\UriMatcher;
use JetFire\Routing\Middleware;
use JetFire\Routing\RouteCollection;
use JetFire\Routing\Router;
use PHPUnit_Framework_TestCase;

/**
 * Class MiddlewareTest
 * @package JetFire\Routing\Test
 */
class MiddlewareTest extends PHPUnit_Framework_TestCase{

    /**
     * @var Middleware
     */
    protected $middleware;

    /**
     *
     */
    public function setUp()
    {
        $collection = new RouteCollection();
        $collection->addRoutes(ROOT . '/Config/routes.php', [
            'view_dir' => ROOT . '/Views',
            'ctrl_namespace' => 'JetFire\Routing\App\Controllers',
        ]);
        $collection->addRoutes(ROOT . '/Block1/routes.php', [
            'view_dir' => ROOT . '/Block1/Views',
            'ctrl_namespace' => 'JetFire\Routing\App\Block1',
            'prefix' => 'block1'
        ]);
        $collection->addRoutes(ROOT . '/Block2/routes.php', [
            'view_dir' => ROOT . '/Block2/',
            'ctrl_namespace' => 'JetFire\Routing\App\Block2\Controllers'
        ]);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $router = new Router($collection);
        $router->addMatcher(new ArrayMatcher($router));
        $router->addMatcher(new UriMatcher($router));
        $this->middleware = new Middleware($router);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidMiddleware(){
        $this->middleware->setCallbackAction('before', '/Config/middleware.inc.php');
    }

    /**
     *
     */
    public function testMiddleware(){
        $this->middleware->setCallbackAction('before', ROOT.'/Config/middleware.inc.php');
        $this->assertTrue(is_array($this->middleware->getMiddleware()['before']));
    }

} 