<?php

App::uses('Component', 'Controller');

class AppComponent extends Component {

    protected function generateFileName($extension) {

        return uniqid() . '-' . $this->datetime("Ymd-His") . '.' . $extension;
    }

    protected function datetime($format = "Y-m-d H:i:s") {

        return date($format);
    }

}