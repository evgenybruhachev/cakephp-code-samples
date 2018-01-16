<?php

App::uses('SchemaShell', 'Console/Command');
App::uses('CakeSchemeExtended', 'Model');

class SchemaExtendedShell extends SchemaShell {

    public function startup() {
        $this->_welcome();
        $this->out('Cake Schema Shell');
        $this->hr();

        Configure::write('Cache.disable', 1);

        $name = $path = $connection = $plugin = null;
        if (!empty($this->params['name'])) {
            $name = $this->params['name'];
        } elseif (!empty($this->args[0]) && $this->args[0] !== 'snapshot') {
            $name = $this->params['name'] = $this->args[0];
        }

        if (strpos($name, '.')) {
            list($this->params['plugin'], $splitName) = pluginSplit($name);
            $name = $this->params['name'] = $splitName;
        }
        if ($name && empty($this->params['file'])) {
            $this->params['file'] = Inflector::underscore($name);
        } elseif (empty($this->params['file'])) {
            $this->params['file'] = 'schema.php';
        }
        if (strpos($this->params['file'], '.php') === false) {
            $this->params['file'] .= '.php';
        }
        $file = $this->params['file'];

        if (!empty($this->params['path'])) {
            $path = $this->params['path'];
        }

        if (!empty($this->params['connection'])) {
            $connection = $this->params['connection'];
        }
        if (!empty($this->params['plugin'])) {
            $plugin = $this->params['plugin'];
            if (empty($name)) {
                $name = $plugin;
            }
        }
        $name = Inflector::camelize($name);
        $this->Schema = new CakeSchemeExtended(compact('name', 'path', 'file', 'connection', 'plugin'));
    }

}