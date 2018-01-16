<?php

App::uses('Component', 'Controller');

class S3UploaderComponent extends Component {

    public function uploadFileByPath(&$message, $filepath, $filename, $mimeType, $options = NULL) {

        $config = Configure::read('s3_credentials');

        $client = Aws\S3\S3Client::factory($config);
        $bucket = Configure::read('s3_bucket_name');

        try {
            $result = $client->putObject(array(
                'Bucket'       => $bucket,
                'Key'          => $filename,
                'SourceFile'   => $filepath,
                'ContentType'  => $mimeType,
                'ACL'          => 'public-read',
            ));

            if (!isset($result['ObjectURL']) || $result['ObjectURL'] == '') {
                $message = 'Link is empty';
                return null;
            }

            $uploadedFileUrl = $result['ObjectURL'];

            return $uploadedFileUrl;

        } catch (Exception $e) {
            $message = $e->getMessage();
            return null;
        }
    }

    /**
     * @throws Exception
     *
     * @param $message
     * @param $JSONString
     * @param $filename
     * @param null $options
     * @return mixed|null
     */
    public function uploadJSON($JSONString, $filename, $options = NULL) {

        $config = Configure::read('s3_credentials');

        $client = Aws\S3\S3Client::factory($config);
        $bucket = Configure::read('s3_bucket_name');

        // @throws Exception
        $result = $client->putObject(array(
            'Bucket'       => $bucket,
            'Key'          => $filename,
            'Body'         => $JSONString,
            'ContentType'  => 'application/json',
            'ACL'          => 'public-read',
        ));

        if (!isset($result['ObjectURL']) || $result['ObjectURL'] == '') {
            throw new Exception('Link is empty');
        }

        return $result['ObjectURL'];
    }

    /**
     * {description}
     *
     * @param   string   $content
     * @param   string   $filename
     *
     * @access  public
     * @return  mixed
     *
     * @throws  Exception
     */
    public function uploadPDF($content, $filename) {

        $config = Configure::read('s3_credentials');

        $client = Aws\S3\S3Client::factory($config);
        $bucket = Configure::read('s3_bucket_name');

        // @throws Exception
        $result = $client->putObject(array(
            'Bucket'       => $bucket,
            'Key'          => $filename,
            'Body'         => $content,
            'ContentType'  => 'application/pdf',
            'ACL'          => 'public-read',
        ));

        if (!isset($result['ObjectURL']) || $result['ObjectURL'] == '') {
            throw new Exception('Link is empty');
        }

        return $result['ObjectURL'];
    }

    public function uploadImagick($imagick, $filename, $contentType) {

        $this->performPutObject($message, $uploadedFileUrl, array(
            'Key'          => $filename,
            'Body'         => $imagick->getImageBlob(),
            'ContentType'  => $contentType,
        ));
        if ($uploadedFileUrl == NULL) {
            throw new Exception($message);
        }
        return $uploadedFileUrl;
    }

    public function uploadFileByContent($content, $filename, $mimeType, $options = NULL) {

        $this->performPutObject($message, $uploadedFileUrl, array(
            'Key'          => $filename,
            'Body'         => $content,
            'ContentType'  => $mimeType,
        ));
        if ($uploadedFileUrl == NULL) {
            throw new Exception($message);
        }
        return $uploadedFileUrl;
    }

    /*
     * Performs Aws\S3\S3Client::putObject to default bucket with read permission for everyone
     */
    private function performPutObject(&$message, &$imageUrl, $object) {
        $config = Configure::read('s3_credentials');

        $client = Aws\S3\S3Client::factory($config);
        $bucket = Configure::read('s3_bucket_name');

        try {
            $result = $client->putObject(
                array_merge(
                    $object,
                    array(
                        'Bucket' => $bucket,
                        'ACL' => 'public-read',
                    )
                ));

            if ($result == NULL) {
                $message = 'Link is empty';
                $imageUrl = NULL;
                return;
            }

            if (!isset($result['ObjectURL']) || $result['ObjectURL'] == '') {
                $message = 'Link is empty';
                $imageUrl = NULL;
                return;
            }

            $imageUrl = $result['ObjectURL'];

        } catch (Exception $e) {
            $message = $e->getMessage();
            return NULL;
        }
    }

}
