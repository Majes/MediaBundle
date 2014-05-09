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

	public function __construct(){}
	
	public function init($filename, $destination = false, $quality = 80){
		$this->quality = $quality;
		$this->filename = $filename;
		$this->type = $this->filenameToMime();

		$this->image = $this->readImage();

		if(!$destination)
			$this->destination = 'pictures';
		else
			$this->destination = $destination;
	}
        
        public static function createImageJpegFromWebColor($color, $width, $height, $filename){
            if (strlen($color) > 1)
                if ($color[0] == '#')
                        $color = substr($color, 1);

                if (strlen($color) == 6)
                    list($r, $g, $b) = array(
                        $color[0] . $color[1],
                        $color[2] . $color[3],
                        $color[4] . $color[5]
                    );
                elseif (strlen($color) == 3)
                    list($r, $g, $b) = array(
                        $color[0] . $color[0],
                        $color[1] . $color[1],
                        $color[2] . $color[2]
                    );
                else
                    return false;

                $rgb = array(
                    'r' => hexdec($r),
                    'g' => hexdec($g),
                    'b' => hexdec($b)
                );
                if($color == '00CC00'){
                    $debug = true;
                }
                $im = imagecreate($width, $height);
                $res = imagecolorallocate($im, $rgb['r'], $rgb['g'], $rgb['b']);
                return imagejpeg($im, $filename);
        }
        
	public function filenameToMime () {
		$types = array(
		'jpg' => self::$TYPE_JPEG,
		'jpeg' => self::$TYPE_JPEG,
		'gif' => self::$TYPE_GIF,
		'png' => self::$TYPE_PNG
		);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $array = explode('/', finfo_file($finfo, $this->filename));
        $ext = next($array);

        finfo_close($finfo);
        if(isset($types[$ext])){
            return $types[$ext];
        }
        return false;
	}

	public function readImage () {

		switch ($this->type)
		{
			case self::$TYPE_JPEG:
				return imagecreatefromjpeg($this->filename);
				break;

			case self::$TYPE_GIF:
				return imagecreatefromgif($this->filename);
				break;

			case self::$TYPE_PNG:
				return imagecreatefrompng($this->filename);
				break;

			default:
				break;
		}
		return false;
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
		//echo $this->image.' '.$this->destination.$imageName.'.'.$this->type;
		if(!file_exists($this->destination.$imageName)){

			switch ($this->type)
			{
				case self::$TYPE_JPEG:
					imagejpeg($this->image,$this->destination.$imageName, $this->quality);
					return true;
					break;

				case self::$TYPE_GIF:
					imagegif($this->image,$this->destination.$imageName);
					return true;
					break;

				case self::$TYPE_PNG:
					imagealphablending($this->image, false);
					imagesavealpha($this->image,true);
					imagepng($this->image,$this->destination.$imageName);
					return true;
					break;

				default:
					break;
			}
			return false;
		}
		return false;
	}

	public function resize ($width = NULL, $height = NULL, $fit = false) {
		$fit = (bool) $fit;

		// if no bounding box supplied, then return the original image
		if ($width == NULL && $height == NULL)
		{
			return $this->image;
		}

		$origX = $newX = imagesx($this->image);
		$origY = $newY = imagesy($this->image);
		$origR = $origY / $origX;

		// if height only was specified

		if ($width == NULL && $height != NULL)
		{
			if ($origY >= $height || $fit == true)
			{
				$newY = $height;
				$newX = $newY / $origY * $origX;
			}
		}

		// if width only was specified

		if ($width != NULL && $height == NULL)
		{
			if ($origX >= $width || $fit == true)
			{
				$newX = $width;
				$newY = $newX / $origX * $origY;
			}
		}

		// if width and height specified

		if ($width != NULL && $height != NULL)
		{
			// if the image fits and $fit is off, just return the original
			// image (since it fits in the bounding box)
			
			if($fit == true)
			{
				$newX = $width;
				$newY = $height;
			}
			else
			{
			
				/*if ($origX < $width && $origY < $height && $fit == false)
				{
					return $this->image;
				}*/
            	
				$newR = $height / $width;
            	
				// we now need to work out whether to restrain by width or by height
            	
				if ($origR > $newR)
				{
					$newY = $height;
					$newX = $newY / $origY * $origX;
				}
				else
				{
					$newX = $width;
					$newY = $newX / $origX * $origY;
				}
			}
		}

		// now we know the new image's dimensions

		$new = imagecreatetruecolor($newX, $newY);
		
		
		if($this->type == self::$TYPE_GIF || $this->type == self::$TYPE_PNG)
		{
			imagealphablending($new, false);
			imagesavealpha($new, true);
			$transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);
			imagefilledrectangle($new, 0, 0, $newX, $newY, $transparent);
		}
		imagecopyresampled($new, $this->image, 0, 0, 0, 0, $newX, $newY, $origX, $origY);

		$this->image = $new;
		return true;
	}
	
	public function crop($width, $height)
	{
	
	
		$origW = imagesx($this->image);
		$origH = imagesy($this->image);
		
		$widthRatio = $width / $origW;
		$heightRatio = $height / $origH;
		
		$ratio = ($widthRatio >= $heightRatio)?$widthRatio:$heightRatio;
		
		$newW = $origW * $ratio;
		$newH = $origH * $ratio;		
		
		if($ratio == $widthRatio)
		{
			//get the y position for the crop
			$x = 0;
			$y = round(($origH - $height / $ratio) / 2);
		}else
		{
			//get the x position for the crop
			$y = 0;
			$x = round(($origW - $width / $ratio) / 2);
		}
		
		
		// now we know the new image's dimensions

		$new = imagecreatetruecolor($width, $height);
		imagealphablending($new, false);
		imagesavealpha($new, true);
		
		imagecopyresampled($new, $this->image, 0, 0, $x, $y, $newW, $newH, $origW, $origH);

		$this->image = $new;
		return true;
	}

	public function imagerotate($src_img, $angle)
            {
                $src_x = imagesx($src_img);
                $src_y = imagesy($src_img);
                if ($angle == 180)
                {
                    $dest_x = $src_x;
                    $dest_y = $src_y;
                }
                elseif ($src_x <= $src_y)
                {
                    $dest_x = $src_y;
                    $dest_y = $src_x;
                }
                elseif ($src_x >= $src_y) 
                {
                    $dest_x = $src_y;
                    $dest_y = $src_x;
                }
               
                $rotate=imagecreatetruecolor($dest_x,$dest_y);
                imagealphablending($rotate, false);
               
                switch ($angle)
                {
                    case 270:
                    	
                        for ($y = 0; $y < ($src_y); $y++)
                        {
                            for ($x = 0; $x < ($src_x); $x++)
                            {
                                $color = imagecolorat($src_img, $x, $y);
                                imagesetpixel($rotate, $dest_x - $y - 1, $x, $color);
                            }
                        }
                        break;
                    case 90:
                        for ($y = 0; $y < ($src_y); $y++)
                        {
                            for ($x = 0; $x < ($src_x); $x++)
                            {
                                $color = imagecolorat($src_img, $x, $y);
                                imagesetpixel($rotate, $y, $dest_y - $x - 1, $color);
                            }
                        }
                        break;
                    case 180:
                        for ($y = 0; $y < ($src_y); $y++)
                        {
                            for ($x = 0; $x < ($src_x); $x++)
                            {
                                $color = imagecolorat($src_img, $x, $y);
                                imagesetpixel($rotate, $dest_x - $x - 1, $dest_y - $y - 1, $color);
                            }
                        }
                        break;
                    default: $rotate = $src_img;
                };
                return $rotate;
            } 

	public function rounderCorner(){
		$corner_radius = 5;
		$angle = 0;
		$topleft = true;
		$bottomleft = true;
		$bottomright = true;
		$topright = true;

		$corner_source = imagecreatefrompng(SERVER_PATH.BASEURL.'img/admin/rounder_corner.png');

		$corner_width = imagesx($corner_source);
		$corner_height = imagesy($corner_source);

		$corner_resized = ImageCreateTrueColor($corner_radius, $corner_radius);
		ImageCopyResampled($corner_resized, $corner_source, 0, 0, 0, 0, $corner_radius, $corner_radius, $corner_width, $corner_height);

		$corner_width = imagesx($corner_resized);
		$corner_height = imagesy($corner_resized);

		$image = imagecreatetruecolor($corner_width, $corner_height);
		$image = $this->image; // replace filename with $_GET['src']

		//$size = getimagesize($images_dir . $image_file); // replace filename with $_GET['src']

		$size[0] = imagesx($this->image);
		$size[1] = imagesy($this->image);

		$white = ImageColorAllocate($image,255,255,255);
		$black = ImageColorAllocate($image,0,0,0);

		// Top-left corner
		if ($topleft == true) {
			$dest_x = 0;
			$dest_y = 0;
			imagecolortransparent($corner_resized, $black);
			imagecopymerge($image, $corner_resized, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100);
		}

		// Bottom-left corner
		if ($bottomleft == true) {
			$dest_x = 0;
			$dest_y = $size[1] - $corner_height;
			$rotated = $this->imagerotate($corner_resized, 90 );
			imagecolortransparent($rotated, $black);
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100);
		}

		// Bottom-right corner
		if ($bottomright == true) {
			$dest_x = $size[0] - $corner_width;
			$dest_y = $size[1] - $corner_height;
			$rotated = $this->imagerotate($corner_resized, 180);
			imagecolortransparent($rotated, $black);
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100);
		}

		// Top-right corner
		if ($topright == true) {
			$dest_x = $size[0] - $corner_width;
			$dest_y = 0;
			$rotated = $this->imagerotate($corner_resized, 270);
			imagecolortransparent($rotated, $black);
			imagecopymerge($image, $rotated, $dest_x, $dest_y, 0, 0, $corner_width, $corner_height, 100);
		}

		// Rotate image
		$image = $this->imagerotate($image, 0, $white);

		$this->image = $image;
	}

	public function createImg($src, $width = null, $height = null, $default = false, $save = false, $fit = false, $center = false)
	    {	

			$folders = explode('/', $src); $path = ''; $nb = count($folders); $i = 1;
			foreach($folders as $folder)
			{
				if($i < $nb)
					$path .= $folder.'/';
				$i++;
			}

			$configGeneral = Zend_Registry::get('config');
			$this->_pathroot = $configGeneral->system->path->root;
			//echo $this->_pathroot.'public'.$src;
			if(!file_exists($this->_pathroot.'public'.$src) || strlen($src) == 0 ){

				$folders = explode('/', $default); $path = ''; $nb = count($folders); $i = 1;
				foreach($folders as $folder)
				{
					if($i < $nb)
						$path .= $folder.'/';
					$i++;
				}


				$path_info = pathinfo($this->_pathroot.'public'.$default);

				$image = new Medialibrary_Library_Image($this->_pathroot.'public'.$default, $path_info['dirname'].'/');
				$image->resize($width, $height, $fit);

				if($save == true)
				{
					$image->saveImage($path_info['filename'].'_'.$width.'x'.$height);
				}
			}
			else
			{

				$path_info = pathinfo($this->_pathroot.'public'.$src);

				if(file_exists($path_info['dirname'].'/'.$path_info['filename'].'_'.$width.'x'.$height.'.'.strtolower($path_info['extension']))){


				}else{

					$image = new Medialibrary_Library_Image($this->_pathroot.'public'.$src, $path_info['dirname'].'/');
					$image->readImage();
					$image->resize($width, $height, $fit);
					if($save == true)
						$image->saveImage($path_info['filename'].'_'.$width.'x'.$height);

				}
			}

			list($widthReal, $heightReal) = getimagesize($path_info['dirname'] . '/' . $path_info['filename'] . '_' . $width . 'x' . $height . '.' .strtolower($path_info['extension']));

			$style = '';
			if($center)
				$style = "style='top: 50%; left: 50%; margin-top: -".($heightReal/2)."px; margin-left: -".($widthReal/2)."px'";

			echo "<img src='".$path.$path_info['filename']."_".$width."x".$height.".".strtolower($path_info['extension'])."' width='".$widthReal."' height='".$heightReal."' ".$style."/>";
	    
	}
        
    
}
