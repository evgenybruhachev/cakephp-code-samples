<?php

App::uses('ClassRegistry', 'Utility');

class CakeSchemeExtended extends CakeSchema {

    public $connection = 'default';

    protected function tableWithNeededDefaults() {
        return array();
    }

    protected function newTableWithNeededDefaults() {
        return array();
    }

    public function before($event = array()) {
        $db = ConnectionManager::getDataSource($this->connection);
        $db->cacheSources = false;
        return parent::before($event);
    }

    public function after($event = array()) {
        if (isset($event['create'])) {
            $tableName = $event['create'];
            $tableWithNeededDefaults = $this->tableWithNeededDefaults();
            if (isset($tableWithNeededDefaults[$tableName])) {
                $modelName = $tableWithNeededDefaults[$tableName];
                App::uses($modelName, 'Model');
                $model = ClassRegistry::init($modelName);
                $model->clear();
                $model->saveMany($model->defaults());
            }
        }
        if (isset($event['update'])) {
            $tableName = $event['update'];
            $tableWithNeededDefaults = $this->newTableWithNeededDefaults();
            if (isset($tableWithNeededDefaults[$tableName])) {
                $modelName = $tableWithNeededDefaults[$tableName];
                App::uses($modelName, 'Model');
                $model = ClassRegistry::init($modelName);
                $model->clear();
                $model->saveMany($model->defaults());
            }
        }
        parent::after($event);
    }

    public function write($object, $options = array()) {
        if (is_object($object)) {
            $object = get_object_vars($object);
            $this->build($object);
        }

        if (is_array($object)) {
            $options = $object;
            unset($object);
        }

        extract(array_merge(
            get_object_vars($this), $options
        ));

        $out = "\nApp::uses('CakeSchemeExtended', 'Model');\n\n";
        $out .= "class AppSchema extends CakeSchemeExtended {\n\n";

        if ($path !== $this->path) {
            $out .= "\tpublic \$path = '{$path}';\n\n";
        }

        if ($file !== $this->file) {
            $out .= "\tpublic \$file = '{$file}';\n\n";
        }

        if ($connection !== 'default') {
            $out .= "\tpublic \$connection = '{$connection}';\n\n";
        }

        $out .= "\tprotected function tableWithNeededDefaults() {\n\t\treturn array();\n\t}\n\n";
        $out .= "\tprotected function newTableWithNeededDefaults() {\n\t\treturn array();\n\t}\n\n";

        if (empty($tables)) {
            $this->read();
        }

        foreach ($tables as $table => $fields) {
            if (!is_numeric($table) && $table !== 'missing') {
                $out .= $this->generateTable($table, $fields);
            }
        }
        $out .= "}\n";

        $file = new File($path . DS . $file, true);
        $content = "<?php \n{$out}";
        if ($file->write($content)) {
            return $content;
        }
        return false;
    }

}
