<?php

App::uses('SystemDictionaryModel', 'Model');

class AccessLevel extends SystemDictionaryModel {

    public $useTable = 'access_levels';

    const ID_PRIVATE = 1;
    const ID_PUBLIC = 2;

    const CODE_PRIVATE = 'private';
    const CODE_PUBLIC = 'public';

    public function getAllActiveFormatted() {

        $list = $this->getAllActive(NULL, array(
            'AccessLevel.id',
            'AccessLevel.code',
            'AccessLevel.title',
            'AccessLevel.is_default',
        ), false);

        $notesFormatted = array();
        $dtoToEntitiesMap = array(
            'AccessLevel.id' => array('path' => 'AccessLevel.id', 'type' => AppModel::$dtoTypeInt),
            'AccessLevel.code' => array('path' => 'AccessLevel.code', 'type' => AppModel::$dtoTypeString),
            'AccessLevel.title' => array('path' => 'AccessLevel.title', 'type' => AppModel::$dtoTypeLocalized),
            'AccessLevel.is_default' => array('path' => 'AccessLevel.is_default', 'type' => AppModel::$dtoTypeBoolean),
        );

        foreach ($list as $note) {

            $noteFormatted = $this->entityToDTO($note, $dtoToEntitiesMap);
            array_push($notesFormatted, $noteFormatted);
        }

        return $notesFormatted;
    }

    public function isPublic($id) {

        if (intval($id) == static::ID_PUBLIC) {
            return true;
        }

        return false;
    }

    public function defaults() {
        return array(
            array(
                'id' => static::ID_PRIVATE,
                'title' => $this->encodeLocalized(array(
                    'EN' => 'Private',
                    'JA' => 'Private',
                )),
                'code' => static::CODE_PRIVATE,
                'is_default' => true,
            ),
            array(
                'id' => static::ID_PUBLIC,
                'title' => $this->encodeLocalized(array(
                    'EN' => 'Public',
                    'JA' => 'Public',
                )),
                'code' => static::CODE_PUBLIC,
            ),
        );
    }

}