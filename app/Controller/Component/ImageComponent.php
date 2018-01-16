<?php

App::uses('AppComponent', 'Controller/Component');

class ImageComponent extends AppComponent {

    const TEMPLATE_DIRECTORY = APP . 'View' . DS . 'Images';

    const SUBSTRATE_TEMPLATE = 'blur.psd';
    const SUBSTRATE_TEMPLATE_IMAGE_INDEX = 1;
    const SUBSTRATE_S3_PATH = 'Profiles' . DS . 'Images' . DS . 'Substrate';
    const SUBSTRATE_IMAGE_FORMAT = 'png';
    const SUBSTRATE_CONTENT_TYPE = 'image/png';
    const SUBSTRATE_WIDTH = 500;
    const SUBSTRATE_HEIGHT = 500;
    const SUBSTRATE_BLUR_DEGREE = 32;

    public $components = array(
        'S3Uploader',
    );

    public function getSubstrateByImageUrl($imageUrl) {

        try {

            $image = new Imagick(static::TEMPLATE_DIRECTORY . DS . static::SUBSTRATE_TEMPLATE);

            $picture = $this->getBlurredImagick($imageUrl, static::SUBSTRATE_WIDTH, static::SUBSTRATE_HEIGHT);

            $image->setImageIndex(static::SUBSTRATE_TEMPLATE_IMAGE_INDEX);
            $image->setImage($picture);
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageFormat(static::SUBSTRATE_IMAGE_FORMAT);

            $fileName = static::SUBSTRATE_S3_PATH . DS . $this->generateFileName(static::SUBSTRATE_IMAGE_FORMAT);
            $substrateUrl = $this->S3Uploader->uploadImagick($image, $fileName, static::SUBSTRATE_CONTENT_TYPE);

        } catch (Exception $exception) {
            throw $exception;
        }

        return $substrateUrl;
    }

    private function getBlurredImagick($imageUrl, $width, $height) {

        try {
            $picture = new Imagick($imageUrl);

            $picture->cropThumbnailImage($width, $height);
            $picture->blurImage(static::SUBSTRATE_BLUR_DEGREE, static::SUBSTRATE_BLUR_DEGREE);
            $picture->blurImage(static::SUBSTRATE_BLUR_DEGREE, static::SUBSTRATE_BLUR_DEGREE);
            $picture->blurImage(static::SUBSTRATE_BLUR_DEGREE, static::SUBSTRATE_BLUR_DEGREE);
            $picture->blurImage(static::SUBSTRATE_BLUR_DEGREE, static::SUBSTRATE_BLUR_DEGREE);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $picture;
    }

}