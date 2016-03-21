<?php

class Normal1Controller {

    public function contact(){
        echo 'Contact1';
    }

    public function user($name = null){
        return 'User : '.$name;
    }

    public function log(){
        return ['name' => 'JetFire'];
    }
}