<?php
namespace JetFire\Routing\Test\Block2\Controllers;

class Namespace2Controller {

    public function index(){
        echo 'Index2';
    }

    public function index2($id){
        echo 'Index'.$id;
    }

} 