<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 */

App::uses('Controller', 'Controller');
App::uses('AppControllerInterface', 'Controller');
App::uses('UsersSession', 'Model');
App::uses('Customer', 'Model');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller implements AppControllerInterface {

    public $helpers = array(
        'Html',
        'Form',
    );

    public $components = array(
        'Session',
        'DataValidator',
        'RequestHandler',
    );

    protected $adminActions = array(
    );

    protected function jsonResponse200($response = null) {
        return $this->jsonResponse(200, $response);
    }

    protected function jsonResponse201($response) {
        return $this->jsonResponse(201, $response);
    }

    protected function jsonResponse400($message) {
        return $this->jsonResponse(400, array(
            'message' => $message
        ));
    }

    protected function jsonResponse405($message) {
        return $this->jsonResponse(405, array(
            'message' => $message
        ));
    }

    protected function inParameters($fields, $parameters, &$error, $prefix = '') {

        foreach ($fields as $field) {

            if (!isset($parameters[$field])) {
                $error = "'$prefix$field' expected";
                return false;
            } elseif (!is_array($parameters[$field]) && strlen($parameters[$field]) == 0) {
                $error = "'$prefix$field' expected";
                return false;
            }

        }

        return true;

    }

    protected function copyFromArray($fields, $parameters, $fill_as_empty = false) {

        $result = array();

        foreach ($fields as $field) {

            if (isset($parameters[$field]) && !is_array($parameters[$field]) && strlen($parameters[$field]) != 0) {
                $result[$field] = $parameters[$field];
            } else {
                if ($fill_as_empty) {
                    $result[$field] = null;
                }
            }

        }

        return $result;

    }

    public function datetime($format = "Y-m-d H:i:s") {

        return date($format);

    }

    function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $d && $d->format('Y-m-d H:i:s') === $date;
    }

    protected function getRequestData() {
        if ($this->request->is('get')) {
            return $this->request->query;
        } else {
            return $this->request->input('json_decode', true);
        }
    }

    protected function inputDataValidationError($inputData, $requirements) {
        $validateErrors = $this->DataValidator->validateData($inputData, $requirements);
        if (!is_null($validateErrors)) {
            $errorText = implode(PHP_EOL, $validateErrors);
            return $errorText;
        }
        return NULL;
    }

}
