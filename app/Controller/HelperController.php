<?php

App::uses('AppController', 'Controller');

class HelperController extends AppController {

    public $uses = array(
        // DEV
        'Genre',
    );

    public $components = array(
        'S3Uploader',
    );

    public function uploadImage() {

        $ROUTES_ADDITIONAL_INFO = array(
            '/image/upload' => array(
                'url_path' => 'Account.image_url',
                's3_directory' => 'Profiles' . DS . 'Images' . DS . 'Original',
            ),
            '/admin/image/upload' => array(
                'url_path' => 'Admin.image_url',
                's3_directory' => 'Admins' . DS . 'Images',
            ),
        );

        if (!isset($ROUTES_ADDITIONAL_INFO[$this->request->here])) {
            return $this->jsonResponse400(__('Additional parameters not defined for route %s', array(
                $this->request->here,
            )));
        }

        if (!isset($_FILES['image'])) {
            return $this->jsonResponse400(__('Error.FileNotFound'));
        }
        if ($_FILES['image']['error'] != 0) {
            return $this->jsonResponse400(__('Error.ImageTooLarge'));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['image']['tmp_name']);
        $availableImageContentType = Configure::read('available_image_content_types');

        $isMimeTypeAllowed = in_array($mimeType, $availableImageContentType);
        if (!$isMimeTypeAllowed) {
            return $this->jsonResponse400(__('Uploader.Fail.MimeTypeIsNotAllowed', array(
                $mimeType,
            )));
        }

        $filename = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $tempFilename = uniqid() . '-' . $this->datetime("Ymd-His") . '.' . $fileExtension;

        $uploaderMessage = NULL;
        $filePath = $ROUTES_ADDITIONAL_INFO[$this->request->here]['s3_directory'];
        $fileUrl = $this->S3Uploader->uploadFileByPath($uploaderMessage, $_FILES['image']['tmp_name'], $filePath . DS . $tempFilename, $mimeType);

        if ($fileUrl == NULL) {
            return $this->jsonResponse400($uploaderMessage);
        }

        $responseArray = array();
        $responseArray = Hash::insert($responseArray, $ROUTES_ADDITIONAL_INFO[$this->request->here]['url_path'], $fileUrl);

        return $this->jsonResponse200($responseArray);
    }

}