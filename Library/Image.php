<?php
namespace Majes\MediaBundle\Library;

class Image
{
	static $TYPE_JPEG = 'jpg';
	static $TYPE_GIF = 'gif';
	static $TYPE_PNG = 'png';

	public $type;
	public $quality;
	protected $filename;
	protected $image;
	protected $imagick;
	protected $destination;
	
	public $_pathroot;

	public function getType(){						return stripslashes($this->type);}
	public function getFilename(){					return stripslashes($this->filename);}
	public function getImage(){						return stripslashes($this->image);}
	public function getDestination(){						return $this->destinaton;}

	public function setType($type){					if(in_array($type,array('jpg','gif','png'))) $this->type = $type;}
	public function setFilename($filename){			$this->filename = $filename;}
	public function setImage($image){				$this->image = $image;}
	public function setDestination($dest){				$this->destination = $dest;}

	public function __construct(){
		$this->imagick = new \Imagick();
	}
	
	public function init($filename, $destination = false, $quality = 80){
		$this->quality = $quality;
		$this->filename = $filename;
		$this->imagick->readImage($this->filename);
		$format = $this->imagick->getImageFormat(); 
		if ($format == 'GIF')
			$this->imagick = $this->imagick->coalesceImages();
		if(!$destination)
			$this->destination = 'pictures';
		else
			$this->destination = $destination;
	}
    
    
    public function getImageAsString(){
        ob_start();
        $this->writeImage();
        return ob_get_flush();
    }
	public function writeImage () {
		switch ($this->type)
		{
			case self::$TYPE_JPEG:
				imagejpeg($this->image,null,$this->quality);
				break;

			case self::$TYPE_GIF:
				imagegif($this->image,null);
				break;

			case self::$TYPE_PNG:
				imagepng($this->image,null);
				break;

			default:
				break;
		}
	}

	public function saveImage ($imageName) {
		$format = $this->imagick->getImageFormat();
		if(!file_exists($this->destination.$imageName)){
			if ($format == 'GIF')
				$this->imagick->writeImages($this->destination.$imageName, true);
			else
				$this->imagick->writeImage($this->destination.$imageName);
			return true;
		}
		return false;
	}

	public function resize ($width = NULL, $height = NULL, $fit = false) {
		$fit = (bool) $fit;
		$format = $this->imagick->getImageFormat();
		// if no bounding box supplied, then return the original image
		if ($width == NULL && $height == NULL){
			return $this->imagick;
		}else{
			if ($format == 'GIF')
				foreach ($this->imagick as $frame) 
					$frame->adaptiveResizeImage($width, $height, true); 
			else
				$this->imagick->adaptiveResizeImage($width, $height, true);
			return true;
		}
		
		
	}
	
	public function crop($width, $height)
	{
		if ($format == 'GIF')
			foreach ($this->imagick as $frame)
				$frame->cropImage($width, $height, ($this->imagick->getImageWidth()-$width)/2,($this->imagick->getImageHeight()-$height)/2); 	   
		else
			$this->imagick->cropImage($width, $height, ($this->imagick->getImageWidth()-$width)/2,($this->imagick->getImageHeight()-$height)/2);
		return true;
	}

	public function rotate($angle)
    {
        $this->imagick->rotateImage(new ImagickPixel('none'), $angle); 
        return true;
    } 

	public function rounderCorner(){
		$this->imagick->roundCorners(40,40); 
        return true;
	}

    public function mirror(){
    	$this->imagick->flipImage();
		return true;
    }   
    
}
