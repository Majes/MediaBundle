<?php
namespace Majes\MediaBundle\Twig;

use Majes\MediaBundle\Entity\Media;
use Majes\MediaBundle\Library\Image;
use Majes\MediaBundle\Library\ImageFallback;
use nwtn\Respimg as Respimg;

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
            new \Twig_SimpleFunction('teelMedia', array($this, 'teelMedia')),
            new \Twig_SimpleFunction('textToSeo', array($this, 'textToSeo'))
        );
    }

    public function teelMediaLoad($media, $width = null, $height = null, $crop = false, $default = null, $options = array()){
        if (is_int($media)) {
            $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($media);
        }

        if (is_null($media)) {
            if (is_object($default) && $default instanceof Media)
                $media = $default;
            else if (is_int($default))
                $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($default);
            else if (!is_null($default))
                return '<img src="'.$default.'" width="'.$width.'" height="'.$height.'"/>';
            else
                return "Media not found";

        }

        $css_class = isset($options['class']) ? ' class="'.$options['class'].'"' : '';
        $attribute_id = isset($options['id']) ? ' id="'.$options['id'].'"' : '';
        $attribute_data = '';
        $style = isset($options['style']) ? ' style="'.$options['style'].'"' : '';
        $title = isset($options['title']) ? $options['title'] : $media->getTitle();

        $path = isset($options['path']) ? $options['path'] : null;
        $mediaPath = is_null($path) ? $media->getPath() : $path;


        if (isset($options['data'])){
            foreach ($options['data'] as $data_name => $data_value)
                $attribute_data .= 'data-'.$data_name.'='.$data_value;
        }

        //Get file type
        if($media->getType() != 'embed'){
            if(!file_exists($media->getAbsolutePath()))
                return '';

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

                $width = is_null($width) ? 0 : $width;
                // Preserve aspect ratio on with auto parameter on height
                if ( $height == "auto" && !is_null($width) ) {
                    list($width_origin, $height_origin) = getimagesize($media->getAbsolutePath());
                    $height = $width*$height_origin/$width_origin;
                }

                $height = is_null($height) ? 0 : $height;
                // Preserve aspect ratio on with auto parameter on width
                if ( $width == "auto" && !is_null($height) ) {
                    list($width_origin, $height_origin) = getimagesize($media->getAbsolutePath());
                    $width = $height*$width_origin/$height_origin;
                }

                //TODO: if public, check if thumb exists, else create it, then get url
                if($media->getIsProtected() == 0){
                    //check if cached file exist

                    if(isset($options['optimize']) && !$crop){

                        if(!class_exists("Imagick"))
                            $lib_image = new ImageFallback();
                        else
                            $lib_image = new Image();

                        if(is_null($width) && is_null($height))
                            $futureFile = 'restimg.';
                        else
                            $futureFile = 'restimg.'.$width.'x'.$height.'_';

                        $input_filename = $media->getAbsolutePath();
                        $output_filename = $media->getCachePath().$futureFile.$mediaPath;
                        $old_filename = $media->getCachePath().$width.'x'.$height.'_'.$mediaPath;

                        if(is_file($old_filename))
                            unlink($old_filename);

                        if(!is_file($output_filename) || isset($options['emptycache'])){
                            $image = new Respimg($input_filename);
                            $image->smartResize($width, 0, false);
                            $image->writeImage($output_filename);
                        }

                        $lib_image->init($output_filename);
                    }else{

                        $width = $width <= 0 ? null : $width;
                        $height = $height <= 0 ? null : $height;

                        $prefix = $crop ? 'crop.' : '';

                        $file = $media->getAbsolutePath();
                        $destination = $media->getCachePath();

                        if(!class_exists("Imagick"))
                            $lib_image = new ImageFallback();
                        else
                            $lib_image = new Image();


                        if(is_null($width) && is_null($height))
                            $futureFile = '';
                        else
                            $futureFile = $prefix.$width.'x'.$height.'_';

                        if(!is_file($destination.$futureFile.$mediaPath) || isset($options['emptycache'])){

                            if(is_file($destination.$futureFile.$mediaPath))
                                unlink($destination.$futureFile.$mediaPath);

                            $lib_image->init($file, $destination);

                            if($crop)
                                $lib_image->crop($width, $height);
                            elseif(!is_null($width) && !is_null($height))
                                $lib_image->resize($width, $height);

                            $lib_image->saveImage($futureFile.$mediaPath);
                        }else
                        {
                            $lib_image->init($destination.$futureFile.$mediaPath);
                        }

                    }

                    if(isset($options['src']) && $options['src'])
                        return '/'.$media->getWebCacheFolder().$futureFile.$mediaPath;
                    //Get width and height
                    $sizes = $lib_image->getSize();
                    $mediaSrc = '/'.$media->getWebCacheFolder().$futureFile.$mediaPath;

                    $mediaTag = '<img width="'.$sizes['width'].'" height="'.$sizes['height'].'" src="'.$mediaSrc.'" title="'.$title.'" alt="'.$title.'"'.$css_class.$attribute_id.$attribute_data.$style.'/>';
                }

                //TODO: if private use media/load url to generate img
                else if($media->getIsProtected() == 1){
                    $mediaTag = '<img src="/media/load/'.$media->getId().'/'.$crop.'/'.$width.'/'.$height.'" width="'.$width.'" height="'.$height.'" title="'.$title.'" alt="'.$title.'"'.$css_class.$attribute_id.$attribute_data.' onerror=\'this.src="/bundles/majesmedia/img/icon-document.png"\'/>';
                }
                break;

            case 'video':

                $mediaTag = '<div class="flowplayer is-splash" data-flashfit="true" style="width: '.$width.'px; height: '.$height.'px">
                                <video>
                                    <source type="'.$mime_type.'" src="/'.$media->getWebPath().'">
                                    <source type="video/flash" src="/'.$media->getWebPath().'">
                                </video>
                            </div>';
                $mediaSrc = '/'.$media->getWebCacheFolder().$media->getPath();
                break;

            case 'embed':
                $mediaTag = $media->getEmbedded();
                $mediaSrc = '';
                break;

            case 'document':
                $mediaTag = '<a href="/media/download/'.$media->getId().'" target="_blank" title="'.$media->getTitle().'"'.$css_class.$attribute_id.$attribute_data.'>Download file</a>';
                $mediaSrc = '/'.$media->getWebPath();
                break;

            case '':
                $mediaTag = '';
                $mediaSrc = '';
                break;

            default:
                $mediaTag = '<a href="/media/download/'.$media->getId().'" target="_blank" title="'.$media->getTitle().'"'.$css_class.$attribute_id.$attribute_data.'>Download file</a>';
                $mediaSrc = '';
                break;
        }


        //TODO: if media is not picture, then do what should be done to display it (video, embed, document to download)
        if (isset($options['src'])){
            if($options['src'])
                if(isset($options['nocache']) && $options['nocache'])
                    return '/'.$media->getWebPath();
                else
                    return $mediaSrc;

        }else{
            return $mediaTag;
        }

    }

    public function teelMedia($media)
    {
        if (is_int($media)) {
            $media = $this->_em->getRepository('MajesMediaBundle:Media')->findOneById($media);
            return '/'.$media->getWebPath();
        }else{
            return "Media not found";
        }

    }

    public function textToSeo($string, $separator = '-'){

        $accents = array('Š' => 'S', 'š' => 's', 'Ð' => 'Dj','Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss','à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f');
        $string = strtr($string, $accents);
        $string = strtolower($string);
        $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
        $string = preg_replace('{ +}', ' ', $string);
        $string = trim($string);
        $string = str_replace(' ', $separator, $string);

        return $string;

    }

    public function getName()
    {
        return 'majesmedia_extension';
    }
}
