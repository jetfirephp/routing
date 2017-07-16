<?php

namespace JetFire\Routing\Test;

use JetFire\Routing\Matcher\ArrayMatcher;
use JetFire\Routing\Matcher\UriMatcher;
use JetFire\Routing\RouteCollection;
use JetFire\Routing\Router;
use PHPUnit_Framework_TestCase;

/**
 * Class Router
 * @package JetFire\Routing\App
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var
     */
    protected $router;

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
        $this->router = new Router($collection);
        $this->router->addMatcher(new ArrayMatcher($this->router));
        $this->router->addMatcher(new UriMatcher($this->router));
    }

    /**
     * @return array
     */
    public function uriMatchWithoutRoutesProvider()
    {
        return array(
            array(ROOT . '/Views', 'JetFire\Routing\App\Controllers', '/app/index', 'Index', ''),
            array(ROOT . '/Views', 'JetFire\Routing\App\Controllers', '/smart/index', 'Smart', ''),
            array(ROOT . '/Block1/Views', 'JetFire\Routing\App\Block1', '/smart/index1', 'Smart1', ''),
            array(ROOT . '/Block1/Views', 'JetFire\Routing\App\Block1', '/block1/namespace1/index', 'Index1', 'block1'),
            array(ROOT . '/Block2', 'JetFire\Routing\App\Block2\Controllers', '/smart/index2', 'Smart2', ''),
            array(ROOT . '/Block2', 'JetFire\Routing\App\Block2\Controllers', '/normal2/contact', 'Contact2', ''),
            array(ROOT . '/Views', 'JetFire\Routing\App\Controllers', '/app/namespace/index', 'Index', 'app'),
        );
    }

    /**
     * @dataProvider uriMatchWithoutRoutesProvider
     * @param $path
     * @param $namespace
     * @param $url
     * @param $output
     * @param $prefix
     */
    public function testUriMatchWithoutRoutes($path, $namespace, $url, $output, $prefix)
    {
        $collection = new RouteCollection(null, [
            'view_dir' => $path,
            'ctrl_namespace' => $namespace,
            'prefix' => $prefix
        ]);
        $this->router = new Router($collection);
        $this->router->addMatcher(new UriMatcher($this->router));
        $this->router->setUrl($url);
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString($output);
    }

    /**
     * @return array
     */
    public function uriMatchTemplate()
    {
        return array(
            array('/smart/index', 'Smart'),
            array('/block1/smart/index1', 'Smart1'),
            array('/smart/index2', 'Smart2'),
        );
    }

    /**
     * @dataProvider uriMatchTemplate
     * @param $url
     * @param $output
     */
    public function testUriMatchTemplate($url, $output)
    {
        $this->router->setUrl($url);
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString($output);
    }

    /**
     * @return array
     */
    public function uriMatchController()
    {
        return array(
            array('/normal/contact', 'Contact'),
            array('/block1/normal1/contact', 'Contact1'),
            array('/normal2/contact', 'Contact2'),
            array('/namespace/index', 'Index'),
            array('/namespace1/index', 'Index1'),
            array('/namespace2/index', 'Index2'),
        );
    }

    /**
     * @dataProvider uriMatchController
     * @param $url
     * @param $output
     */
    public function testUriMatchController($url, $output)
    {
        $this->router->setUrl($url);
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString($output);
    }

    /**
     * @return array
     */
    public function matchTemplate()
    {
        return array(
            array('/index', 'Hello'),
            array('/block1/index1', 'Hello1'),
            array('/index2', 'Hello2'),
            array('/user-1', 'User'),
            array('/block1/user1-1', 'User1'),
            array('/user2-1', 'User2'),
        );
    }

    /**
     * @dataProvider matchTemplate
     * @param $url
     * @param $output
     */
    public function testMatchTemplate($url, $output)
    {
        $this->router->setUrl($url);
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString($output);
    }

    /**
     * @return array
     */
    public function matchController()
    {
        return array(
            array('/home', 'Index'),
            array('/block1/home1', 'Index1'),
            array('/home2', 'Index2'),
            array('/home-1', 'Index1'),
            array('/block1/home-2', 'Index2'),
            array('/home-3', 'Index3'),
            array('/contact', 'Contact'),
            array('/block1/contact1', 'Contact1Contact1'),
            array('/block1/log', 'JetFire'),
            array('/log2', 'JetFire'),
            array('/contact2', 'Contact2'),
        );
    }

    /**
     * @dataProvider matchController
     * @param $url
     * @param $output
     */
    public function testMatchController($url, $output)
    {
        $this->router->setUrl($url);
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString($output);
    }

    /**
     *
     */
    public function testPostResponseMethod()
    {
        $collection = new RouteCollection();
        $collection->addRoutes(ROOT . '/Config/routes.php', [
            'view_dir' => ROOT . '/Views',
            'ctrl_namespace' => 'JetFire\Routing\App\Controllers',
        ]);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $router = new Router($collection);
        $router->addMatcher(new ArrayMatcher($router));
        $router->setUrl('/search');
        $this->assertTrue($router->match());
        $router->callTarget();
        $router->response->sendContent();
        $this->assertEquals('POST', $router->route->getMethod());
    }

    /**
     *
     */
    public function testGetResponseMethod()
    {
        $collection = new RouteCollection();
        $collection->addRoutes(ROOT . '/Config/routes.php', [
            'view_dir' => ROOT . '/Views',
            'ctrl_namespace' => 'JetFire\Routing\App\Controllers',
        ]);
        $router = new Router($collection);
        $router->addMatcher(new ArrayMatcher($router));
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $router->setUrl('/search');
        $this->assertFalse($router->match());
        $this->assertEquals(405, $router->response->getStatusCode());
    }

    /**
     *
     */
    public function testClosureWithParameters()
    {
        $this->router->setUrl('/block1/search1-3-peter');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString('Search3peter');
    }

    /**
     *
     */
    public function testClosure()
    {
        $this->router->setUrl('/log');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->response->sendContent();
        $this->expectOutputString('yes');
    }

    /**
     *
     */
    public function testApi()
    {
        $this->router->setUrl('/api/users');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->assertEquals(500, $this->router->response->getStatusCode());

        $this->router->response->setStatusCode(200);
        $this->router->setUrl('/api/users/1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->assertEquals('application/json', $this->router->response->headers['Content-Type']);
        $this->router->response->sendContent();
        $this->expectOutputString('{"name":"Peter"}');
    }

    /**
     *
     */
    public function testResponse()
    {
        $this->router->setResponses([
            '404' => function () {
                echo '404';
            },
        ]);
        $this->router->setUrl('/notfound');
        $this->assertFalse($this->router->match());
    }

}