<?php

namespace JetFire\Routing\Test;
use JetFire\Routing\RouteCollection;
use JetFire\Routing\Router;
use PHPUnit_Framework_TestCase;

/**
 * Class Router
 * @package JetFire\Routing\Test
 */
class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $router;

    public function setUp()
    {
        $collection = new RouteCollection();
        $collection->addRoutes(__DIR__.'/routes.php');
        $collection->addRoutes([
            '/'
        ]);
        $this->router = new Router($collection);
    }

    public function testMatchStaticTemplate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/index');
        $this->assertTrue($this->router->match());
    }

    public function testMatchDynamicTemplate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/user-1');
        $this->assertTrue($this->router->match());
    }

    public function testMatchNamespaceController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/home');
        $this->assertTrue($this->router->match());
        return $this->router;
    }

    public function testMatchNoNamespaceController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/contact');
        $this->assertTrue($this->router->match());
        return $this->router;
    }

    /**
     * @depends testMatchNamespaceController
     * @param Router $router
     */
    public function testResponseNamespaceMvc(Router $router)
    {
        $this->expectOutputString('Index');
        $router->callTarget();
    }

    /**
     * @depends testMatchNoNamespaceController
     * @param Router $router
     */
    public function testResponseNoNamespaceMvc(Router $router)
    {
        $this->expectOutputString('Contact');
        $router->callTarget();
    }

    public function testResponseMethod(){
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->router->setUrl('/search');
        $this->assertTrue( $this->router->match());
        $this->router->callTarget();
        $this->assertEquals('POST', $this->router->route->getMethod());
    }

} 