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
        $collection->addRoutes(__DIR__.'/Config/routes.php',[
            'path' => __DIR__.'/Views',
            'namespace' => 'JetFire\Routing\Test\Controllers',
        ]);
        $collection->addRoutes(__DIR__.'/Block1/routes.php',[
            'path' => __DIR__.'/Block1/Views',
            'namespace' => 'JetFire\Routing\Test\Block1',
            'prefix' => 'block1'
        ]);
        $collection->addRoutes(__DIR__.'/Block2/routes.php',[
            'path' => __DIR__.'/Block2/',
            'namespace' => 'JetFire\Routing\Test\Block2\Controllers'
        ]);
        $this->router = new Router($collection);
    }

    public function testSmartMatchWithoutRoutes(){
        $collection = new RouteCollection(null,[
            'path' => __DIR__.'/Views',
            'namespace' => 'JetFire\Routing\Test\Controllers'
        ]);
        $this->router = new Router($collection);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/app/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/smart/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('IndexSmart');
    }

    public function testSmartMatchNamespaceWithoutRoutes(){
        $collection = new RouteCollection();
        $collection->setOption([
            ['path' => __DIR__.'/Views', 'namespace' => 'JetFire\Routing\Test\Controllers','prefix'=>'app'],
        ]);
        $this->router = new Router($collection);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/app/namespace/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('Index');
    }

    public function testSmartMatchTemplate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/smart/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/smart/index1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/smart/index2');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('SmartSmart1Smart2');
    }

    public function testSmartMatchController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/normal/contact');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/normal1/contact');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/normal2/contact');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('ContactContact1Contact2');
    }

    public function testSmartMatchNamespaceController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/namespace/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/namespace1/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/namespace2/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('IndexIndex1Index2');
    }


    public function testMatchStaticTemplate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/index');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/index1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/index2');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('HelloHello1Hello2');
    }

    public function testMatchDynamicTemplate()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/user-1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/user1-1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/user2-1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('UserUser1User2');
    }

    /**
     * @return mixed
     */
    public function testMatchNamespaceController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/home');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/home1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/home2');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('IndexIndex1Index2');
    }

    /**
     * @return mixed
     */
    public function testMatchNamespace2Controller()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/home-1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/home-2');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/home-3');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('Index1Index2Index3');
    }

    /**
     * @return mixed
     */
    public function testMatchNoNamespaceController()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/contact');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/block1/contact1');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->router->setUrl('/contact2');
        $this->assertTrue($this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('ContactContact1Contact2');
    }

    public function testResponseMethod(){
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->router->setUrl('/search');
        $this->assertTrue( $this->router->match());
        $this->router->callTarget();
        $this->assertEquals('POST', $this->router->route->getMethod());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/search');
        $this->assertFalse( $this->router->match());
        $this->assertEquals(405, $this->router->route->getResponse('code'));
    }

    public function testClosureWithParameters(){
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->router->setUrl('/block1/search1-3-peter');
        $this->assertTrue( $this->router->match());
        $this->router->callTarget();
        $this->expectOutputString('Search3peter');
    }

}