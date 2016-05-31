<?php
namespace JetFire\Routing\Block1;

class Namespace1Controller {

    public function index(){
        return ['message' => 'index'];
    }

    public function index2($id){
        if($id == 2){
            header('Location: http://localhost/JetFire/Routing/block/search1-1-tse');
            return true;
        }else {
            return 'Index-' . $id;
        }
    }

} 