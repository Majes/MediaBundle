<?php
namespace Majes\MediaBundle\Library;

class Image {
	static $TYPE_JPG = 'jpg';
	static $TYPE_JPEG = 'jpeg';
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

	public function setType($type){					if(in_array($type,array('jpg','gif','png', 'jpeg'))) $this->type = $type;}
	public function setFilename($filename){			$this->filename = $filename;}
	public function setImage($image){				$this->image = $image;}
	public function setDestination($dest){				$this->destination = $dest;}

	public function __construct(){
		$this->imagick = new \Imagick();
	}

	public function init($filename, $destination = false, $quality = 90){
		$this->quality = $quality;
		$this->filename = $filename;

		// Init imagick objet with image file
		$this->imagick->readImage($this->filename);
		$this->setType(strtolower($this->imagick->getImageFormat()));

		if ($this->type == self::$TYPE_GIF)
			$this->imagick = $this->imagick->coalesceImages();
		//else
		//	$this->imagick->setImageColorspace(13);

		//Set orientation
		$this->imagick = $this->autoRotateImage($this->imagick);

		if(!$destination)
			$this->destination = 'pictures';
		else
			$this->destination = $destination;
	}


	function autoRotateImage($image) {
	    $orientation = $image->getImageOrientation();

	    switch($orientation) {
	        case \Imagick::ORIENTATION_BOTTOMRIGHT:
	            $image->rotateimage("#000", 180); // rotate 180 degrees
	        break;

	        case \Imagick::ORIENTATION_RIGHTTOP:
	            $image->rotateimage("#000", 90); // rotate 90 degrees CW
	        break;

	        case \Imagick::ORIENTATION_LEFTBOTTOM:
	            $image->rotateimage("#000", -90); // rotate 90 degrees CCW
	        break;
	    }

	    // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
	    $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
		return $image;
	}

    public function getImageAsString(){
        ob_start();
        $this->writeImage();
        return ob_get_flush();
    }
	public function writeImage () {
		switch ($this->type) {
			case self::$TYPE_JPG:
			case self::$TYPE_JPEG:
				header("Content-Type: image/jpeg");
				echo $this->imagick;
				break;

			case self::$TYPE_GIF:
				header('Content-Type: image/gif');
				echo $this->imagick;
				break;

			case self::$TYPE_PNG:
				header('Content-Type: image/png');
				echo $this->imagick;
				break;

			default:
				break;
		}
	}

	public function saveImage ($imageName) {
		if(!file_exists($this->destination.$imageName)){
			if ($this->type == self::$TYPE_GIF)
				$this->imagick->writeImages($this->destination.$imageName, true);
			else{
				$this->imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $this->imagick->setImageCompressionQuality($this->quality);
				//$this->imagick->setInterlaceScheme(\Imagick::INTERLACE_NO);
				//$this->imagick->setImageDepth(8);
				$this->imagick->writeImage($this->destination.$imageName);
			}
			return true;
		}
		return false;
	}

	public function resize ($width = NULL, $height = NULL, $fit = false) {
		$fit = (bool) $fit;
		// if no bounding box supplied, then return the original image
		if ($width == NULL && $height == NULL){
			return $this->imagick;
		}else{
			if ($this->type == self::$TYPE_GIF)
				foreach ($this->imagick as $frame)
					$frame->adaptiveResizeImage($width, $height, true);
			else{
				$d = $this->imagick->getImageGeometry();
				$w = $d['width'];
				$h = $d['height'];

				$ratioW = $width/$w;
				$ratioH = $height/$h;

				if($ratioW >= $ratioH && $width >= $w*$ratioW && $height >= $h*$ratioW) $ratio = $ratioW;
				else $ratio = $ratioH;

				if($ratio > 1) $ratio = 1;

				$this->imagick->resizeImage($w*$ratio, $h*$ratio, \Imagick::FILTER_TRIANGLE, 1);
			}
			return true;
		}
	}

	public function getSize(){
		$W = $this->imagick->getImageWidth();
		$H = $this->imagick->getImageHeight();

		return array('width' => $W, 'height' => $H);
	}

	public function crop($width, $height) {
		// $format = $this->imagick->getImageFormat();
		// if ($format == 'GIF')
		// 	foreach ($this->imagick as $frame)
		// 		$frame->cropImage($width, $height, ($this->imagick->getImageWidth()-$width)/2,($this->imagick->getImageHeight()-$height)/2);
		// else
		// 	$this->imagick->cropImage($width, $height, ($this->imagick->getImageWidth()-$width)/2,($this->imagick->getImageHeight()-$height)/2);
		// return true;

		$origW = $this->imagick->getImageWidth();
		$origH = $this->imagick->getImageHeight();

		$widthRatio = $origW / $width;
		$heightRatio = $origH / $height;

		$ratio = ($widthRatio >= $heightRatio)?$heightRatio:$widthRatio;

		$this->imagick->resizeImage($origW/$ratio, $origH/$ratio, \Imagick::FILTER_TRIANGLE, 1);

		// $newW = $origW * $ratio;
		// $newH = $origH * $ratio;

		// if($ratio == $widthRatio)
		// {
		// 	//get the y position for the crop
		// 	$x = 0;
		// 	$y = round(($origH - $height / $ratio) / 2);
		// }else
		// {
		// 	//get the x position for the crop
		// 	$y = 0;
		// 	$x = round(($origW - $width / $ratio) / 2);
		// }


		// now we know the new image's dimensions

		$this->imagick->cropImage($width, $height, ($this->imagick->getImageWidth()-$width)/2, ($this->imagick->getImageHeight()-$height)/2);
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
