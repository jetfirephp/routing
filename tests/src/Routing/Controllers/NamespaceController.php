<?php
namespace JetFire\Routing\Test\Controllers;

class NamespaceController {

    public function index(){
        echo 'Index';
    }
    public function index2($id){
        echo 'Index'.$id;
    }

} 