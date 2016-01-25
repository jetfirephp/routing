<?php

class NormalController {

    public function contact(){
        echo 'Contact';
    }

    public function search(){
       echo 'Search';
    }

     public function user($name = null){
        echo 'User : '.$name;
    }

    public function log($user,$pass){
        echo 'Log : '.$user.' '.$pass;
    }

    public static function auth($param){
        echo 'Auth : '.$param;
    }

}