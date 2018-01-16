<?php

App::uses('AppController', 'Controller');

class HTTPMethodsController extends AppController {

    public $components = array(
        'Session',
    );

    public function success() {

        $this->jsonResponse200(array(
            'status' => true,
        ));

    }

    public function unexpectedPath() {

        $this->jsonResponse405(__('Path.NotFound'));

    }

    public function unexpectedMethod() {

        $this->jsonResponse405(__('Method.Unexpected'));

    }

    public function notDeployed() {

        $this->jsonResponse405(__('Path.NotDeployed'));

    }
}