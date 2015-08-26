<?php

namespace Majes\MediaBundle\Services;

use Doctrine\ORM\EntityManager;
use Majes\MediaBundle\Entity\Media;
use Majes\MediaBundle\Library\Image;
use Majes\MediaBundle\Library\ImageFallback;

class MediaService {

    private $_mime_types;
    private $_em;
    private $_setup;

    public function __construct($em, $setup = null) {
        $this->_em = $em;
        $this->_setup = $setup;

        $this->_mime_types = array(
            'image/jpeg' => 'image',
            'image/jpg' => 'image',
            'image/gif' => 'image',
            'image/png' => 'image',
            'video/flv' => 'video',
            'video/x-flv' => 'video',
            'video/quicktime' => 'video',
            'video/mp4' => 'video',
            'video/x-msvideo' => 'video',
            'video/x-ms-wmv' => 'video',
            'video/webm' => 'video',
            'video/ogg' => 'video',
            'video/x-mpegurl' => 'video',
            'video/mp2t' => 'video',
            'embed' => 'embed'
        );
    }

    public function teelMediaLoad($media, $width = null, $height = null, $crop = false, $default = null) {

        if (is_int($media)) {
            $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($media);
        }

        if (is_null($media)) {
            if (is_object($default) && $default instanceof Media)
                $media = $default;
            else if (is_int($default))
                $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($default);
            else if (file_exists($default))
                return $default;
            else
                return 'Media not found';
        }

        $width = is_null($width) ? 0 : $width;
        $height = is_null($height) ? 0 : $height;

        //Get file type
        if ($media->getType() != 'embed') {
            if (!file_exists($media->getAbsolutePath()))
                return 'Media not found';

            $mime_type = mime_content_type($media->getAbsolutePath());

            if (!isset($this->_mime_types[$mime_type]))
                $this->_mime_types[$mime_type] = 'document';
        }else {
            $mime_type = 'embed';
        }


        if ($height == 0 || $width == 0) {
            $crop = false;
        }

        $crop = $crop ? 1 : 0;

        $mediaSrc = '';
        switch ($this->_mime_types[$mime_type]) {
            case 'image':
                //TODO: if public, check if thumb exists, else create it, then get url
                if ($media->getIsProtected() == 0) {
                    //check if cached file exist
                    $width = $width <= 0 ? null : $width;
                    $height = $height <= 0 ? null : $height;

                    $prefix = $crop ? 'crop.' : '';

                    $file = $media->getAbsolutePath();
                    $destination = $media->getCachePath();

                    if(!class_exists("Imagick"))
                        $lib_image = new ImageFallback();
                    else
                        $lib_image = new Image();


                    if (!is_file($destination . $prefix . $width . 'x' . $height . '_' . $media->getPath())) {
                        $lib_image->init($file, $destination);

                        if ($crop)
                            $lib_image->crop($width, $height);
                        else
                            $lib_image->resize($width, $height);

                        $lib_image->saveImage($prefix . $width . 'x' . $height . '_' . $media->getPath());
                    }

                    $src = '/' . $media->getWebCacheFolder() . $prefix . $width . 'x' . $height . '_' . $media->getPath();

                    $mediaSrc = $src;

                }

                //TODO: if private use media/load url to generate img
                else if ($media->getIsProtected() == 1) {
                    $mediaSrc = '/media/load/' . $media->getId() . '/' . $crop . '/' . $width . '/' . $height;
                }
                break;

            case 'video':
               $mediaSrc = $media->getWebPath();
                break;

            default:
                $mediaSrc = '/media/download/' . $media->getId();
                break;
        }


        //TODO: if media is not picture, then do what should be done to display it (video, embed, document to download)

        return $mediaSrc;
    }

    public function createSizesCache($media, $sizes = null){

        if (is_int($media)) {
            $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($media);
        }

        if(empty($media)) return false;
        if ($media->getType() != 'picture') return false;

        $sizes = array();

        if(!class_exists("Imagick"))
            $lib_image = new ImageFallback();
        else
            $lib_image = new Image();

        $file = $media->getAbsolutePath();
        $destination = $media->getCachePath();

        if(!is_null($sizes))
            $this->_setup['sizes'] = $sizes;

        foreach($this->_setup['sizes'] as $key => $size){

            $lib_image->init($file, $destination);

            list($width_origin, $height_origin) = getimagesize($media->getAbsolutePath());
            $ratio = $height_origin/$width_origin;

            if($size['ratio'] == 2){
                $width = $size['width'] * 2;
                $height = $width * $ratio;

                $suffix = '@2x';

                $lib_image->resize($width, $height);
                $lib_image->saveImage($key . $suffix . '.' . $media->getPath() );
                $sizes[$key.'@2x'] = '/' . $media->getWebCacheFolder() . $key . $suffix. '.'  . $media->getPath();
            }

            $height = $size['width'] * $ratio;
            $suffix = '';

            $lib_image->resize($size['width'], $height);
            $lib_image->saveImage($key . $suffix . '.' . $media->getPath() );
            $sizes[$key] = '/' . $media->getWebCacheFolder() . $key . $suffix. '.'  . $media->getPath();


        }



        return $sizes;

    }

}
