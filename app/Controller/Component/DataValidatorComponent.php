<?php

App::uses('Validation', 'Utility');
App::uses('Component', 'Controller');

class DataValidatorComponent extends Component {

    public static $setAndNotEmpty = array(
        'set',
        'not_empty',
    );
    public static $stringSetAndNotEmpty = array(
        'set',
        'not_empty',
        'is_string',
    );
    public static $intSetAndNotEmpty = array(
        'set',
        'not_empty',
        'is_integer',
    );
    public static $emailSetAndNotEmpty = array(
        'set',
        'not_empty',
        'is_email',
    );
    public static $passwordSetAndNotEmpty = array(
        'set',
        'not_empty',
        'is_string',
        'min_string_length=8',
    );
    public static $arraySetAndNotEmpty = array(
        'set',
        'not_empty',
        'is_array',
    );
    public static $isString = array(
        'is_string',
    );
    public static $isInteger = array(
        'is_integer',
    );
    public static $isArray = array(
        'is_array',
    );

    public function fieldIsSet($data, $field_name) {
        return $this->isFieldSet($data, $field_name);
    }

    public function validateData($data, $requirements) {
        $errors = array();

        if (is_null($data)) {
            return array(
                __('DataValidator.Fail.DataCouldNotBeEmpty'),
            );
        }

        foreach ($requirements as $field => $conditions) {
            foreach ($conditions as $condition) {
                switch ($condition) {
                    case "set":
                        if (!$this->isFieldSet($data, $field)) {
                            $errorText = __('DataValidator.Fail.FieldExpected', array(
                                $field
                            ));
                            $this->addIfNotInArray($errors, $errorText);
                        }
                        break;
                    case "not_empty":
                        if ($this->isFieldSet($data, $field)) {
                            if ($this->isFieldEmpty($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldCouldNotBeEmpty', array(
                                    $field
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        } else {
                            $errorText = __('DataValidator.Fail.FieldExpected', array(
                                $field
                            ));
                            $this->addIfNotInArray($errors, $errorText);
                        }
                        break;
                    case "is_string":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldString($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'String'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    case "is_integer":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldInteger($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'Integer'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    case "is_date":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldDate($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'Date and valid'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    case "is_boolean":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldBoolean($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'Boolean'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    case "is_email":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldEmail($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'email'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    case "is_array":
                        if ($this->isFieldSetAndNotNull($data, $field)) {
                            if (!$this->isFieldArray($data, $field)) {
                                $errorText = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                                    $field,
                                    'array'
                                ));
                                $this->addIfNotInArray($errors, $errorText);
                            }
                        }
                        break;
                    default:
                        break;
                }
                if (strpos($condition, 'max_string_length=') !== FALSE) {
                    if ($this->isFieldSetAndNotNull($data, $field) && $this->isFieldString($data, $field)) {
                        $maxStringLength = (int)str_replace('max_string_length=', '', $condition);
                        if (!$this->isFieldNotLongerThan($data, $field, $maxStringLength)) {
                            $errorText = __('DataValidator.Fail.StringTooLong', array(
                                $field,
                                $maxStringLength,
                            ));
                            $this->addIfNotInArray($errors, $errorText);
                        }
                    }
                }
                if (strpos($condition, 'min_string_length=') !== FALSE) {
                    if ($this->isFieldSetAndNotNull($data, $field) && $this->isFieldString($data, $field)) {
                        $minStringLength = (int)str_replace('min_string_length=', '', $condition);
                        if (!$this->isFieldNotShorterThan($data, $field, $minStringLength)) {
                            $errorText = __('DataValidator.Fail.StringTooShort', array(
                                $field,
                                $minStringLength,
                            ));
                            $this->addIfNotInArray($errors, $errorText);
                        }
                    }
                }
            }
        }

        if (empty($errors)) {
            return NULL;
        } else {
            return $errors;
        }
    }

    private function isFieldSet($data, $field) {
        $pathItems = explode('.', $field);
        $currentSubobject = $data;
        foreach ($pathItems as $pathItem) {
            if (!array_key_exists($pathItem, $currentSubobject)) {
                return false;
            }
            $currentSubobject = $currentSubobject[$pathItem];
        }
        return true;
    }

    private function isFieldSetAndNotNull($data, $field) {
        return Hash::check($data, $field);
    }

    private function isFieldEmpty($data, $field) {
        $fieldValue = Hash::get($data, $field);
        if (is_bool($fieldValue)) {
            return FALSE;
        } else {
            return empty($fieldValue);
        }
    }

    private function isFieldString($data, $field) {
        $fieldValue = Hash::get($data, $field);
        return is_string($fieldValue);
    }

    private function isFieldInteger($data, $field) {
        $fieldValue = Hash::get($data, $field);
        return is_integer($fieldValue);
    }

    private function isFieldDate($data, $field) {
        $fieldValue = Hash::get($data, $field);
        $format = 'Y-m-d';
        $date = DateTime::createFromFormat($format, $fieldValue);
        return $date && $date->format($format) == $fieldValue;
    }

    private function isFieldBoolean($data, $field) {
        $fieldValue = Hash::get($data, $field);
        return is_bool($fieldValue);
    }

    private function isFieldEmail($data, $field) {
        $fieldValue = Hash::get($data, $field);
        return Validation::email($fieldValue);
    }

    private function isFieldArray($data, $field) {
        $fieldValue = Hash::get($data, $field);
        return is_array($fieldValue);
    }

    private function isFieldNotLongerThan($data, $field, $maxLength) {
        $fieldValue = Hash::get($data, $field);
        if (is_string($fieldValue)) {
            return (strlen($fieldValue) <= $maxLength);
        }
        return FALSE;
    }

    private function isFieldNotShorterThan($data, $field, $minLength) {
        $fieldValue = Hash::get($data, $field);
        if (is_string($fieldValue)) {
            return (strlen($fieldValue) >= $minLength);
        }
        return FALSE;
    }

    private function addIfNotInArray(&$array, $value) {
        if (!in_array($value, $array)) {
            array_push($array, $value);
        }
    }

}