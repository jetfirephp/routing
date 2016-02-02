<?php

class Normal1Controller {

    public function contact(){
        echo 'Contact1';
    }

    public function user($name = null){
        echo 'User : '.$name;
    }

}