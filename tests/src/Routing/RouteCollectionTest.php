<?php
namespace JetFire\Routing\Test;

use JetFire\Routing\RouteCollection;
use PHPUnit_Framework_TestCase;

/**
 * Class RouteCollectionTest
 * @package JetFire\Routing\Test
 */
class RouteCollectionTest extends PHPUnit_Framework_TestCase{

    protected $collection;

    public function setUp()
    {
        $this->collection = new RouteCollection();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidRoutes(){
        $this->collection->addRoutes('routes.php');
    }

    public function testCountRoutes(){
        $this->collection->addRoutes(ROOT.'/Config/routes.php',['prefix'=>'/public']);
        $this->collection->addRoutes([
            '/page-1' => 'index.html',
            '/page-2' => function(){
                return 'Hello !';
            }
        ]);
        $this->assertEquals(2,$this->collection->countRoutes);
        return $this->collection;
    }

    /**
     * @depends testCountRoutes
     * @param RouteCollection $collection
     */
    public function testGetRoutes(RouteCollection $collection){
        $this->assertArrayHasKey('view_dir_0',$collection->getRoutes());
        $this->assertEquals('/public',$collection->getRoutes('prefix_0'));
        $this->assertEquals('',$collection->getRoutes('prefix_1'));
    }

    public function testSetPrefix(){
        $this->collection->addRoutes(ROOT.'/Config/routes.php');
        $this->collection->addRoutes([
            '/page-1' => 'index.html',
            '/page-2' => function(){
                return 'Hello !';
            }
        ]);
        $this->collection->setPrefix('public');
        $this->assertEquals('/public',$this->collection->getRoutes('prefix_0'));
        $this->assertEquals('/public',$this->collection->getRoutes('prefix_1'));
        $this->collection->setPrefix(['public','user']);
        $this->assertEquals('/public',$this->collection->getRoutes('prefix_0'));
        $this->assertEquals('/user',$this->collection->getRoutes('prefix_1'));
    }
    
    /**
     * @depends testCountRoutes
     * @param RouteCollection $collection
     * @return \JetFire\Routing\RouteCollection
     */
    public function testGenerateRoutes(RouteCollection $collection){
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '';
        $this->assertTrue($collection->generateRoutesPath());
        return $collection;
    }

    /**
     * @depends testGenerateRoutes
     * @param RouteCollection $collection
     */
    public function testGetPath(RouteCollection $collection){
        $this->assertEquals('http://localhost/public/contact',$collection->getRoutePath('contact'));
        $this->assertNotEquals('http://localhost/contact',$collection->getRoutePath('search'));
    }
} 