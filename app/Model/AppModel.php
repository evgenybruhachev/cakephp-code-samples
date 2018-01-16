<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 */

App::uses('Model', 'Model');
App::uses('EmailQueue', 'Model');
App::uses('Account', 'Model');
App::uses('Validation', 'Utility');
App::uses('ClassRegistry', 'Utility');
App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

    public $actsAs = array('Containable');

    private static $passwordMinLength = 8;

    public static $dtoTypeString = 'string';
    public static $dtoTypeInt = 'int';
    public static $dtoTypeFloat = 'float';
    public static $dtoTypeLocalized = 'localized';
    public static $dtoTypeBoolean = 'boolean';
    public static $dtoTypeDateTime = 'datetime';
    public static $dtoTypeDictionaryNote = 'dictionary_note';
    public static $dtoTypeAuthor = 'author';
    public static $dtoTypeRemoteJson = 'remote_json';
    public static $dtoTypeRemotePlainText = 'remote_plain_text';

    public function defaults() {
        return array();
    }

    public function validationErrorMessage() {

        if (empty($this->validationErrors)) {
            return NULL;
        }

        return json_encode($this->validationErrors);
    }

    public function countActive($filter, $joins = NULL, $group = NULL) {

        $conditions = array_merge(array(
            $this->alias . '.is_deleted' => false,
        ), $this->filterToConditions($filter));

        if ($joins == NULL) {
            $joins = array();
        }

        $notesCount = $this->find('count', compact('conditions', 'joins', 'group'));

        return $notesCount;
    }

    public function getAllActive($filter, $fields = NULL, $contain = false, $order = NULL, $joins = NULL, $group = NULL) {

        if ($fields == NULL) {
            $fields = array(
                $this->alias . '.id',
            );
        }

        $conditions = array_merge(array(
            $this->alias . '.is_deleted' => false,
        ), $this->filterToConditions($filter));

        if ($order == NULL) {
            $order = array(
                $this->alias . '.id' => 'ASC',
            );
        }

        $limit = $this->filterToPagingSize($filter);
        $offset = $this->filterToPagingOffset($filter);

        if ($joins == NULL) {
            $joins = array();
        }

        $notes = $this->find('all', compact('contain', 'joins', 'fields', 'conditions', 'order', 'limit', 'offset', 'group'));

        return $notes;
    }

    public function getActiveById($id, $fields = NULL, $conditions = array(), $contain = false, $joins = NULL) {

        if ($fields == NULL) {
            $fields = array(
                $this->alias . '.id',
            );
        }

        if ($conditions == NULL) {
            $conditions = array();
        }
        $conditions = array_merge(array(
            $this->alias . '.id' => $id,
            $this->alias . '.is_deleted' => false,
        ), $conditions);

        if ($joins == NULL) {
            $joins = array();
        }

        $note = $this->find('first', compact('contain', 'joins', 'fields', 'conditions'));

        return $note;
    }

    protected function isNoteExistAndActive($id) {

        $note = $this->getActiveById($id);
        return ($note != NULL);
    }

    protected function encodeLocalized($data) {
        return json_encode($data);
    }

    protected function decodeLocalized($encoded) {
        return json_decode($encoded, true);
    }

    protected function formatList($entities, $displayType) {

        $entityToDtoConvertFunction = function ($entity) use ($displayType) {
            return $this->formatSingle($entity, $displayType);
        };
        $formattedNotes = Hash::map($entities, "{n}", $entityToDtoConvertFunction);

        return $formattedNotes;
    }

    protected function formatSingle($entity, $displayType) {
        return $entity;
    }

    protected function entityToDTO($entity, $fieldsMap) {

        $dto = array();

        foreach ($fieldsMap as $dtoPath => $entityParameters) {
            if (!isset($entityParameters['type']) || !isset($entityParameters['path'])) {
                continue;
            }
            $type = $entityParameters['type'];
            $entityValuePath = $entityParameters['path'];

            $unformattedValue = Hash::get($entity, $entityValuePath);

            $allowNull = isset($entityParameters['allow_null']) ? boolval($entityParameters['allow_null']) : true;
            if ($allowNull && $unformattedValue === NULL) {
                $dto = Hash::insert($dto, $dtoPath, NULL);
                continue;
            }
            switch ($type) {
                case AppModel::$dtoTypeString:
                    $formattedValue = strval($unformattedValue);
                    break;
                case AppModel::$dtoTypeInt:
                    $formattedValue = intval($unformattedValue);
                    break;
                case AppModel::$dtoTypeFloat:
                    $formattedValue = floatval($unformattedValue);
                    break;
                case AppModel::$dtoTypeLocalized:
                    $formattedValue = $this->decodeLocalized($unformattedValue);
                    break;
                case AppModel::$dtoTypeBoolean:
                    $formattedValue = boolval($unformattedValue);
                    break;
                case AppModel::$dtoTypeDateTime:
                    $formattedValue = strval($unformattedValue);
                    break;
                case AppModel::$dtoTypeDictionaryNote:
                    $formattedValue = $this->formatDictionaryNoteFromEntity($unformattedValue);
                    break;
                case AppModel::$dtoTypeAuthor:
                    $formattedValue = $this->formatAuthorFromEntity($unformattedValue);
                    break;
                case AppModel::$dtoTypeRemoteJson:
                    $formattedValue = $this->getRemoteJson($unformattedValue);
                    break;
                case AppModel::$dtoTypeRemotePlainText:
                    $formattedValue = $this->getRemotePlainText($unformattedValue);
                    break;
                default:
                    $formattedValue = $unformattedValue;
                    break;
            }
            $dto = Hash::insert($dto, $dtoPath, $formattedValue);
        }

        return $dto;
    }

    private function formatDictionaryNoteFromEntity($entity) {

        return array(
            'id' => isset($entity['id']) ? intval($entity['id']) : NULL,
            'code' => isset($entity['code']) ? strval($entity['code']) : NULL,
            'title' => isset($entity['title']) ? $this->decodeLocalized($entity['title']) : NULL,
        );
    }

    private function formatAuthorFromEntity($entity) {

        $Account = ClassRegistry::init('Account');
        $entity = $Account->replaceDifferentNameFieldsWithComputed($entity);

        return array(
            'id' => isset($entity['id']) ? intval($entity['id']) : NULL,
            'name' => !empty($entity['name']) ? strval($entity['name']) : NULL,
            'image_url' => !empty($entity['AccountProfile']['image_url']) ? strval($entity['AccountProfile']['image_url']) : NULL,
            'substrate_url' => !empty($entity['AccountProfile']['substrate_url']) ? strval($entity['AccountProfile']['substrate_url']) : NULL,
        );
    }

    private function getRemoteJson($entity) {

        if (empty($entity) || !Validation::url($entity)) {
            return NULL;
        }
        $jsonContent = file_get_contents($entity);
        return json_decode($jsonContent, true);
    }

    private function getRemotePlainText($entity) {

        if (empty($entity) || !Validation::url($entity)) {
            return NULL;
        }
        return file_get_contents($entity);
    }

    protected function createByMap($data, $map) {

        $formattingErrors = NULL;
        $entity = $this->dtoToEntity($data, $map, $formattingErrors);
        if ($formattingErrors != NULL) {
            throw new Exception($formattingErrors);
        }

        $dataSource = $this->getDataSource();
        $dataSource->begin();

        $this->clear();
        $saved = $this->save($entity);
        if (!$saved) {
            throw new Exception($this->validationErrorMessage());
        }

        $id = intval($this->id);

        $dataSource->commit();

        return $id;
    }

    protected function saveAssociatedByMap($data, $map) {

        $formattingErrors = NULL;
        $entity = $this->dtoToEntity($data, $map, $formattingErrors);
        if ($formattingErrors != NULL) {
            throw new Exception($formattingErrors);
        }

        $dataSource = $this->getDataSource();
        $dataSource->begin();

        $this->clear();
        $saved = $this->saveAssociated($entity);
        if (!$saved) {
            throw new Exception($this->validationErrorMessage());
        }

        $id = intval($this->id);

        $dataSource->commit();

        return $id;
    }

    protected function updateByMap($data, $map) {

        $formattingErrors = NULL;
        $entity = $this->dtoToEntity($data, $map, $formattingErrors);
        if ($formattingErrors != NULL) {
            throw new Exception($formattingErrors);
        }

        $dataSource = $this->getDataSource();
        $dataSource->begin();

        $this->clear();
        $saved = $this->save($entity);
        if (!$saved) {
            throw new Exception($this->validationErrorMessage());
        }

        $dataSource->commit();
    }

    public function markAsDeleted($id) {

        $note = $this->getActiveById($id);
        if ($note == NULL) {
            throw new Exception(__('Request.Fail.SimpleNotFound', array(
                $this->alias,
            )));
        }

        $dataSource = $this->getDataSource();
        $dataSource->begin();

        $this->clear();
        $saved = $this->save(array(
            'id' => $id,
            'is_deleted' => true,
        ));
        if (!$saved) {
            $dataSource->rollback();
            throw new Exception($this->validationErrorMessage());
        }

        $dataSource->commit();
    }

    protected function sendEmail($to, $templateName, $data, $attachedFiles = array()) {

        $EmailQueue = ClassRegistry::init('EmailQueue');
        $EmailQueue->enqueue($to, $templateName, $data, $attachedFiles);
    }

    private static $fieldsMapDTOToEntityExample = array(
        'Account.username' => array(
            'path' => 'Account.username',
            'type' => 'string',
        ),
        'Account.email' => array(
            'path' => 'Account.email',
            'type' => 'email',
            'allow_empty' => false,
        ),
        'Account.password' => array(
            'path' => 'Account.password',
            'type' => 'password',
            'allow_empty' => false,
        ),
        'Account.messages_request_type' => array(
            'path' => 'AccountProfile.messages_requests_type_id',
            'type' => 'messages_requests_type',
        ),
    );
    public static $entityFieldTypeInt = 'int';
    public static $entityFieldTypeString = 'string';
    public static $entityFieldTypeFloat = 'float';
    public static $entityFieldTypeBoolean = 'boolean';
    public static $entityFieldTypeEmail = 'email';
    public static $entityFieldTypeUrl = 'url';
    public static $entityFieldTypePassword = 'password';
    public static $entityFieldTypeAccessLevel = 'access_level';

    protected function dtoToEntity($dto, $fieldsMap, &$error) {

        $entity = array();
        $errors = array();

        foreach ($fieldsMap as $fieldPath => $valueParameters) {
            if (!isset($valueParameters['type']) || !isset($valueParameters['path'])) {
                continue;
            }

            $isHasManyRelationshipType = (strpos($fieldPath, '{n}') !== false);
            $allowSkip = isset($valueParameters['allow_skip']) ? $valueParameters['allow_skip'] : true;
            if (!$isHasManyRelationshipType && !$this->isFieldSet($dto, $fieldPath)) {
                if (!$allowSkip) {
                    array_push($errors, __('DataValidator.Fail.FieldCouldNotBeEmpty', array(
                        $fieldPath
                    )));
                }
                continue;
            }

            $type = $valueParameters['type'];

            $allowEmpty = isset($valueParameters['allow_empty']) ? $valueParameters['allow_empty'] : true;

            $entityValuePath = $valueParameters['path'];

            if ($isHasManyRelationshipType) {

                $dtoPathParts = explode('{n}', $fieldPath);
                $dtoPathToArray = trim(array_shift($dtoPathParts), '.');
                $lastPartOfDtoPath = trim(implode('{n}', $dtoPathParts), '.');

                $entityPathParts = explode('{n}', $entityValuePath);
                $entityPathToArray = trim(array_shift($entityPathParts), '.');
                $lastPartOfEntityPath = trim(implode('{n}', $entityPathParts), '.');

                $subDataArray = Hash::get($dto, $dtoPathToArray);

                $formattedArray = array();
                if (!is_array($subDataArray)) {
                    $entity = Hash::insert($entity, $entityValuePath, $formattedArray);
                    continue;
                }

                foreach ($subDataArray as $key => $subValue) {

                    $entitySubPathToArray = $entityPathToArray;
                    if (!empty($entitySubPathToArray)) {
                        $entitySubPathToArray .= '.';
                    }
                    $entitySubPathToArray .= strval($key);

                    $formattingErrors = NULL;
                    $formattedArrayValue = $this->dtoToEntity($subValue, array(
                        $lastPartOfDtoPath => array(
                            'type' => $type, 'path' => $lastPartOfEntityPath, 'allow_empty' => $allowEmpty, 'allow_skip' => $allowSkip,
                        ),
                    ), $formattingErrors);
                    if ($formattingErrors != NULL) {
                        array_push($errors, $formattingErrors);
                        continue;
                    }

                    $currentFormattedArrayValue = Hash::get($entity, $entitySubPathToArray, array());
                    $entity = Hash::insert($entity, $entitySubPathToArray,
                        array_merge_recursive($currentFormattedArrayValue, $formattedArrayValue));
                }

                continue;
            }

            $value = Hash::get($dto, $fieldPath);

            if (!$allowEmpty && empty($value)) {
                array_push($errors, __('DataValidator.Fail.FieldCouldNotBeEmpty', array(
                    $fieldPath
                )));
                continue;
            }

            $fieldError = NULL;
            if (!$this->validateValue($value, $type, $fieldPath, $fieldError)) {
                array_push($errors, $fieldError);
                continue;
            }

            $value = $this->prepareValue($value, $type);

            $entity = Hash::insert($entity, $entityValuePath, $value);
        }

        if (!empty($errors)) {
            $error = implode(PHP_EOL, $errors);
            return NULL;
        }

        return $entity;
    }

    protected function isStringAllowedToBeAPassword ($string) {

        return is_string($string) && !empty($string) && strlen($string) >= AppModel::$passwordMinLength;
    }

    protected function filterToConditions($filter) {

        $conditions = array();

        return $conditions;
    }

    protected function filterToPagingSize($filter) {

        if (isset($filter['paging_size'])) {
            $pagingSize = intval($filter['paging_size']);
        } else {
            $pagingSize = NULL;
        }

        return $pagingSize;
    }

    protected function filterToPagingOffset($filter) {

        if (isset($filter['paging_offset'])) {
            $pagingOffset = intval($filter['paging_offset']);
        } else {
            $pagingOffset = NULL;
        }

        return $pagingOffset;
    }

    private function prepareValue($value, $type) {

        if ($type == AppModel::$entityFieldTypePassword) {
            $passwordHasher = new BlowfishPasswordHasher();
            return $passwordHasher->hash($value);
        }

        return $value;
    }

    private function validateValue($value, $type, $path, &$error) {

        if ($value == NULL) {
            return true;
        }

        $typesToModelsMap = array(
            AppModel::$entityFieldTypeAccessLevel => 'AccessLevel',
        );

        if ($type == AppModel::$entityFieldTypeString) {
            if (!is_string($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'string',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeInt) {
            if (!is_integer($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'number',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeFloat) {
            if (!is_numeric($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'number',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeBoolean) {
            if (!is_bool($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'boolean',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeEmail) {
            if (!Validation::email($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'email',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeUrl) {
            if (!Validation::url($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'URL',
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypePassword) {
            if (!$this->isStringAllowedToBeAPassword($value)) {
                $error = __('Account.Password.IsIncorrect', array(
                    AppModel::$passwordMinLength,
                ));
                return false;
            }
            return true;
        }

        if ($type == AppModel::$entityFieldTypeBpm) {
            if (!is_integer($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'number',
                ));
                return false;
            }

            $Settings = ClassRegistry::init('Setting');
            $bpmLimits = $Settings->getBPMInfo();
            if ($value < $bpmLimits['BPMInfo']['min']) {
                $error = __('Setting.Error.ValueIsLessThanMinimum', array(
                    $path, $bpmLimits['BPMInfo']['min'],
                ));
                return false;
            }
            if ($value > $bpmLimits['BPMInfo']['max']) {
                $error = __('Setting.Error.ValueIsHigherThanMaximum', array(
                    $path, $bpmLimits['BPMInfo']['max'],
                ));
                return false;
            }

            return true;
        }

        if ($type == AppModel::$entityFieldTypeVolume) {
            if (!is_integer($value)) {
                $error = __('DataValidator.Fail.FieldShouldBeTypeOf', array(
                    $path, 'number',
                ));
                return false;
            }

            if ($value < 0) {
                $error = __('Setting.Error.ValueIsLessThanMinimum', array(
                    $path, 0,
                ));
                return false;
            }
            if ($value > 100) {
                $error = __('Setting.Error.ValueIsHigherThanMaximum', array(
                    $path, 100,
                ));
                return false;
            }

            return true;
        }



        if (array_key_exists($type, $typesToModelsMap)) {
            $modelName = $typesToModelsMap[$type];
            $Model = ClassRegistry::init($modelName);
            if (!method_exists($Model, 'isNoteExistAndActive')) {
                $error = __('AppModel.Fail.NoteCheckerNoteDefined', array(
                    $modelName,
                ));
                return false;
            }
            if (!$Model->isNoteExistAndActive($value)) {
                $error = __('Request.Fail.SimpleNotFound', array(
                    $modelName,
                ));
                return false;
            }
            return true;
        }

        $error = __('AppModel.Fail.TypeNoteDefined', array(
            $type,
        ));
        return false;
    }

    private function isFieldSet($data, $field) {

        if (strpos($field, '{n}')) {
            return $this->isFieldSetForArray($data, $field);
        }

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

    private function isFieldSetForArray($data, $field) {
        return true;
    }

}
