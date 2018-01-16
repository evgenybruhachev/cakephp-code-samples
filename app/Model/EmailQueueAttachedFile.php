<?php

App::uses('AppModel', 'Model');

class EmailQueueAttachedFile extends AppModel {

    public $useTable = 'email_queue_attached_files';

    public $actsAs = array('Containable');

    public function create_by_file_url_and_queue_item_id($file_url, $filename, $queue_item_id) {
        $data = array(
            'email_queue_id' => $queue_item_id,
            'file_url' => $file_url,
            'filename' => $filename,
        );

        $this->create();
        $this->save($data);
    }

    public function get_for_email_queue_item($queue_item_id) {
        $attachments_list = $this->findAllByEmailQueueId($queue_item_id);
        return $attachments_list;
    }

}