<?php

App::uses('QueueExtendedTask', 'Console/Command/Task');
App::uses('CakeEmail', 'Network/Email');

class QueueEmailSenderTask extends QueueExtendedTask {

    public $uses = array(
        'EmailQueue',
    );

    protected static $ACTIONS = array(
        'send' => 'sendEmail',
    );

    public function sendEmail($data) {

        if (empty($data['email_queue_id'])) {
            throw new Exception(__('Email queue ID not provided'));
        }
        $queueId = $data['email_queue_id'];
        $emailQueueItem = $this->EmailQueue->findFirstById($queueId);
        if ($emailQueueItem == NULL) {
            throw new Exception(__('Email queue item not found'));
        }

        try {

            $email = new CakeEmail($emailQueueItem['EmailQueue']['config']);

            $email->from($emailQueueItem['EmailQueue']['from_email'], $emailQueueItem['EmailQueue']['from_name']);
            $email->to($emailQueueItem['EmailQueue']['to']);
            $email->subject($emailQueueItem['EmailQueue']['subject']);

            $email->template($emailQueueItem['EmailQueue']['template'], $emailQueueItem['EmailQueue']['layout']);
            $email->emailFormat($emailQueueItem['EmailQueue']['format']);
            $email->viewVars($emailQueueItem['EmailQueue']['template_vars']);

            $sent = $email->send();
            if ($sent) {
                $this->EmailQueue->success($queueId);
            } else {
                $this->EmailQueue->fail($queueId);
                throw new Exception(__('Email was not sent: %s', array(
                    json_encode($email->transportClass()->getLastResponse()),
                )));
            }

        } catch (Exception $exception) {
            throw $exception;
        }
    }

}