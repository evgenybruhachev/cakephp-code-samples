<?php

class IntFieldsContainerBehavior extends ModelBehavior {

    public function setup(Model $Model, $settings = array()) {
        $this->settings[$Model->alias] = $settings;
    }

    public function afterFind(Model $Model, $results, $primary = false){
        $modelAlias = $Model->alias;
        $intFields = $this->settings[$modelAlias];

        foreach ($results as $key => $value) {
            foreach ($intFields as $intField) {
                if (isset($value[$modelAlias][$intField])) {
                    $results[$key][$modelAlias][$intField] = intval($value[$modelAlias][$intField]);
                }
            }
        }
        return $results;
    }

}