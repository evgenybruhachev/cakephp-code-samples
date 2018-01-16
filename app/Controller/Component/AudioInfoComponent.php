<?php

App::uses('Component', 'Controller');
App::uses('Validation', 'Utility');

class AudioInfoComponent extends Component {

    public function getDuration($file) {

        $isFileLoaded = false;
        if (Validation::url($file)) {
            $fileExtension = pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION);
            $tmpFile = TMP . uniqid() . '.' . $fileExtension;
            try {
                $fileContent = fopen($file, 'r');
                if ($fileContent == NULL) {
                    throw new Exception(__('Unable to load file'));
                }
                file_put_contents($tmpFile, $fileContent);
            } catch (Exception $exception) {
                throw $exception;
            }
            $file = $tmpFile;
            $isFileLoaded = true;
        }

        try {
            $getID3 = new getID3;
            $mediaInfo = $getID3->analyze($file);
            $duration = $mediaInfo['playtime_seconds'];
        } catch (Exception $exception) {
            if ($isFileLoaded) {
                unlink($file);
            }
            throw $exception;
        }

        if ($isFileLoaded) {
            unlink($file);
        }

        $decimalPart = NULL;
        if (count(explode('.', strval($duration))) > 1) {
            $decimalPart = explode('.', strval($duration))[1];
        }

        $durationString = sprintf('%d:%02d', floor($duration / 60), floor($duration % 60));
        if (!empty($decimalPart)) {
            $durationString .= ".$decimalPart";
        }

        return $durationString;
    }

}