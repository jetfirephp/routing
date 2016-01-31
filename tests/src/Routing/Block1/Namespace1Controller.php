<?php
namespace JetFire\Routing\Test\Block1;

class Namespace1Controller {

    public function index(){
        echo 'Index1';
    }

    public function index2($id){
        echo 'Index'.$id;
    }

} 