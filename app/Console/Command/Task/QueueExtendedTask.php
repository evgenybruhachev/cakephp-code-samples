<?php

App::uses('QueueTask', 'Queue.Console/Command/Task');

class QueueExtendedTask extends QueueTask {

    public $uses = array(
    );

    protected static $ACTIONS = array(
    );

    public $failureMessage = '';

    public function run($data, $id = null) {

        $this->QueuedTask = ClassRegistry::init('Queue.QueuedTask');

        if (!isset($data['action']) || !array_key_exists($data['action'], static::$ACTIONS)) {
            $this->out(__('Action is not defined'));
            $this->QueuedTask->markJobFailed($id, __('Action is not defined'));
            return false;
        }

        try {
            $methodName = static::$ACTIONS[$data['action']];
            $this->{$methodName}($data);
        } catch (Exception $exception) {
            $this->failureMessage = $exception->getMessage();
            return false;
        }

        return true;
    }

}
