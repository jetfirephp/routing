<?php

namespace JetFire\Routing;


interface ResponseInterface {

    public function __construct($content = '', $status = 200, $headers = array());

    public function sendContent();
    public function send();
    public function setHeaders($headers);
    public function setContent($content);
    public function getContent();
    public function setStatusCode($code, $text = null);
    public function getStatusCode();
    public function setCharset($charset);
    public function getCharset();

}
