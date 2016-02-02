<?php
namespace JetFire\Routing\App\Controllers;

class NamespaceController {

    public function index(){
        echo 'Index';
    }
    public function index2($id){
        echo 'Index'.$id;
    }

} 