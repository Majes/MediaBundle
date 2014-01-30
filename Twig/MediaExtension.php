<?php 
namespace Majes\MediaBundle\Twig;

use Majes\MediaBundle\Entity\Media;
use Majes\MediaBundle\Library\Image;

class MediaExtension extends \Twig_Extension
{

    private $_mime_types;
    private $_em;

    public function __construct($em){
        $this->_em = $em;
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

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('teelMediaLoad', array($this, 'teelMediaLoad')),
        );
    }

    public function teelMediaLoad($media, $width = null, $height = null, $crop = false, $default = null){

        if(is_int($media)){
            $media = $this->_em->getRepository('MajesMediaBundle:Media')
                ->findOneById($media);
        }

        if(is_null($media))
            return 'No media found';

        

        $width = is_null($width) ? 0 : $width;
        $height = is_null($height) ? 0 : $height;

        //Get file type
        if($media->getType() != 'embed'){
            $mime_type = mime_content_type($media->getAbsolutePath());
            if(!isset($this->_mime_types[$mime_type]))
                $this->_mime_types[$mime_type] = 'document';
        }else{
            $mime_type = 'embed';
        }


        if($height == 0 || $width == 0){
            $crop = false;
        }

        $crop = $crop ? 1 : 0;

        $mediaTag = '';
        switch ($this->_mime_types[$mime_type]) {
            case 'image':
                    //TODO: if public, check if thumb exists, else create it, then get url
                    if($media->getIsProtected() == 0){
                        //check if cached file exist
                        $width = $width <= 0 ? null : $width;
                        $height = $height <= 0 ? null : $height;
                
                        $prefix = $crop ? 'crop.' : '';
                                
                        $file = $media->getAbsolutePath();
                        $destination = $media->getCachePath();
                
                        $lib_image = new Image();
                        if(!is_file($destination.$prefix.$width.'x'.$height.'_'.$media->getPath())){
                            $lib_image->init($file, $destination);
                
                            if($crop)
                                $lib_image->crop($width, $height);
                            else
                                $lib_image->resize($width, $height);
                            $lib_image->saveImage($prefix.$width.'x'.$height.'_'.$media->getPath());
                        }

                        $src = '/'.$media->getWebCacheFolder().$prefix.$width.'x'.$height.'_'.$media->getPath();

                        $mediaTag = '<img src="'.$src.'" />';
                    }
            
                    //TODO: if private use media/load url to generate img
                    else if($media->getIsProtected() == 1){
                        $mediaTag = '<img src="/media/load/'.$media->getId().'/'.$crop.'/'.$width.'/'.$height.'" />';
                    }
                break;

            case 'video':

                $mediaTag = '<div class="flowplayer is-splash" data-flashfit="true" style="width: '.$width.'px; height: '.$height.'px">
    <video>
        <source type="'.$mime_type.'" src="/'.$media->getWebPath().'">
        <source type="video/flash" src="/'.$media->getWebPath().'">
    </video>
</div>';
                break;

            case 'embed':

                $mediaTag = $media->getEmbedded();
                break;
            
            default:
                $mediaTag = '<img src="'.$default.'" style="width:'.$width.'px; height: '.$height.'px;"/>';
                break;
        }
        
        
        //TODO: if media is not picture, then do what should be done to display it (video, embed, document to download)
        
        return $mediaTag;
    }


    public function getName()
    {
        return 'majesmedia_extension';
    }
}