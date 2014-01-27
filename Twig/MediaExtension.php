<?php 
namespace Majes\MediaBundle\Twig;

use Majes\MediaBundle\Entity\Media;

class MediaExtension extends \Twig_Extension
{

    public function __construct(){

    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('teelMediaLoad', array($this, 'teelMediaLoad')),
        );
    }

    public function teelMediaLoad($media, $width = null, $height = null, $crop = false){
        
        if(is_null($media))
            return 'Error';

        $width = is_null($width) ? 0 : $width;
        $height = is_null($height) ? 0 : $height;

        if($height == 0 || $width == 0){
            $crop = false;
        }

        $crop = $crop ? 1 : 0;

        //TODO: if public, check if thumb exists, else create it, then get url
        $img = '<img src="/media/load/'.$media->getId().'/'.$crop.'/'.$width.'/'.$height.'" />';
        return $img;

        //TODO: if private use media/load url to generate img
        
        
        //TODO: if media is not picture, then do what should be done to display it (video, embed, document to download)
        

    }


    public function getName()
    {
        return 'majesmedia_extension';
    }
}