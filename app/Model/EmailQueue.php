<?php

App::uses('AppModel', 'Model');
App::uses('CakeEmail', 'Network/Email');

/**
 * EmailQueue model
 *
 */
class EmailQueue extends AppModel {

    const TEMPLATE_RESTORE_PASSWORD = 'restore_code';
    const SUBJECTS_FOR_TEMPLATES = array(
        self::TEMPLATE_RESTORE_PASSWORD => 'コード送付致しました',
    );

    /**
     * Name
     *
     * @var string $name
     * @access public
     */
    public $name = 'EmailQueue';

    /**
     * Database table used
     *
     * @var string
     * @access public
     */
    public $useTable = 'email_queue';

    public function enqueue($to, $template, array $data, $attachedFiles = array()) {

        if (!array_key_exists($template, static::SUBJECTS_FOR_TEMPLATES)) {
            throw new Exception(__('Template %s not defined for email sending', array(
                $template,
            )));
        }

        $configName = 'smtp';

        $emailManager = new CakeEmail($configName);
        $config = $emailManager->config();
        $fromEmail = array_keys($config['from'])[0];
        $fromName = array_values($config['from'])[0];

        $email = array(
            'subject' => static::SUBJECTS_FOR_TEMPLATES[$template],
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'send_at' => gmdate('Y-m-d H:i:s'),
            'template' => $template,
            'layout' => 'default',
            'format' => 'text',
            'template_vars' => array(
                'data' => $data,
            ),
            'config' => $configName,
        );

        if (!is_array($to)) {
            $to = array($to);
        }

        try {
            foreach ($to as $t) {
                $email['to'] = $t;
                $this->create();
                $this->save($email);

                $id = $this->id;
                $this->requestEmailSending($id);
                foreach ($attachedFiles as $original_name => $file_url) {
                    ClassRegistry::init('EmailQueueAttachedFile')->create_by_file_url_and_queue_item_id(
                        $file_url, $original_name, $id
                    );
                }
            }
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Returns a list of queued emails that needs to be sent
     *
     * @param integer $size, number of unset emails to return
     * @return array list of unsent emails
     * @access public
     */
    public function getBatch($size = 10) {

        $emails = $this->find('all', array(
            'limit' => $size,
            'conditions' => array(
                'EmailQueue.sent' => false,
                'EmailQueue.send_tries <=' => 3,
                'EmailQueue.send_at <=' => gmdate('Y-m-d H:i:s'),
                'EmailQueue.locked' => false
            ),
            'order' => array('EmailQueue.created' => 'ASC')
        ));

        return $emails;
    }

    /**
     * Locks all emails in $emails
     *
     * @return void
     **/
    public function lockEmails($emails) {
        if (!empty($emails)) {
            $ids =  Hash::extract($emails, '{n}.EmailQueue.id');
            $this->setLocks($ids, TRUE);
        }
    }

    /**
     * Unlocks all emails in $emails
     *
     * @return void
     **/
    public function unlockEmails($emails) {
        if (!empty($emails)) {
            $ids =  Hash::extract($emails, '{n}.EmailQueue.id');
            $this->setLocks($ids, FALSE);
        }
    }

    /**
     * Set locks to $is_lock for all emails in $ids
     *
     * @return void
     **/
    private function setLocks($ids, $is_lock) {
        $this->updateAll(array('locked' => $is_lock), array('EmailQueue.id' => $ids));
    }

    /**
     * Releases locks for all emails in queue, useful for recovering from crashes
     *
     * @return void
     **/
    public function clearLocks() {
        $this->updateAll(array('locked' => false));
    }

    /**
     * Marks an email from the queue as sent
     *
     * @param string $id, queued email id
     * @return boolean
     * @access public
     */
    public function success($id) {
        $this->id = $id;
        return $this->saveField('sent', true);
    }

    /**
     * Marks an email from the queue as failed, and increments the number of tries
     *
     * @param string $id, queued email id
     * @return boolean
     * @access public
     */
    public function fail($id) {
        $this->id = $id;
        $tries = $this->field('send_tries');
        return $this->saveField('send_tries', $tries + 1);
    }

    /**
     * Converts array data for template vars into a json serialized string
     *
     * @param array $options
     * @return boolean
     **/
    public function beforeSave($options = array()) {
        if (isset($this->data[$this->alias]['template_vars'])) {
            $this->data[$this->alias]['template_vars'] = json_encode($this->data[$this->alias]['template_vars']);
        }

        return parent::beforeSave($options);
    }

    /**
     * Converts template_vars back into a php array
     *
     * @param array $results
     * @param boolean $primary
     * @return array
     **/
    public function afterFind($results, $primary = false) {
        if (!$primary) {
            return parent::afterFind($results, $primary);
        }

        foreach ($results as &$r) {
            if (!isset($r[$this->alias]['template_vars'])) {
                return $results;
            }
            $r[$this->alias]['template_vars'] = json_decode($r[$this->alias]['template_vars'], true);
        }

        return $results;
    }

    private function requestEmailSending($id) {

        $QueuedTask = ClassRegistry::init('Queue.QueuedTask');
        $QueuedTask->createJob('EmailSender', array(
            'action' => 'send',
            'email_queue_id' => $id,
        ));
    }

}
