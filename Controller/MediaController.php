<?php

namespace Majes\MediaBundle\Controller;

use Majes\CoreBundle\Controller\SystemController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Response;

use Majes\MediaBundle\Entity\Media;
use Majes\MediaBundle\Library\Image;


class MediaController extends Controller implements SystemController
{

    public function loadAction($id, $crop, $width, $height)
    {
        /*
         * The action's view can be rendered using render() method
         * or @Template annotation as demonstrated in DemoController.
         *
         */
        $request = $this->getRequest();

        $prefix = $crop ? 'crop.' : '';

        $em = $this->getDoctrine()->getManager();
        $media = $em->getRepository('MajesMediaBundle:Media')
            ->findOneById($id);

        $file = $media->getAbsolutePath();
        $destination = $media->getCachePath();

        $lib_image = new Image();
        if(is_file($destination.$prefix.$width.'x'.$height.'_'.$media->getPath())){
            $lib_image->init($destination.$prefix.$width.'x'.$height.'_'.$media->getPath(), $destination);
        }else{
            $lib_image->init($file, $destination);

            if($crop)
                $lib_image->crop($width, $height);
            else
                $lib_image->resize($width, $height);
            $lib_image->saveImage($prefix.$width.'x'.$height.'_'.$media->getPath());
        }
       
        $lib_image->writeImage();
        return new Response();
    }

}
