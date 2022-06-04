<?php
/**
 * Resize image class will allow you to resize an image
 *
 * Can resize to exact size
 * Max width size while keep aspect ratio
 * Max height size while keep aspect ratio
 * Automatic while keep aspect ratio
 */
class ResizeImage
{
	private $imagick;
	private $ext;
	private $image;
	private $newImage;
	private $origWidth;
	private $origHeight;
	private $resizeWidth;
	private $resizeHeight;
	/**
	 * Class constructor requires to send through the image filename
	 *
	 * @param string $filename - Filename of the image you want to resize
	 */
	public function __construct( $filename )
	{
		if(file_exists($filename))
		{
			$this->imagick = new \Imagick();
			$this->imagick->readImage($filename);

			$this->autoRotateImage();
			
			$this->origWidth = $this->imagick->getImageWidth();
	    	$this->origHeight = $this->imagick->getImageHeight();
		} else {
			throw new Exception('Image ' . $filename . ' can not be found, try another image.');
		}
	}


	private function autoRotateImage() 
	{
		$orientation = $this->imagick->getImageOrientation();
	
		switch($orientation) {
			case \Imagick::ORIENTATION_BOTTOMRIGHT: 
				$this->imagick->rotateImage("#000", 180); // rotate 180 degrees
				
				break;
			case \Imagick::ORIENTATION_RIGHTTOP:
				$this->imagick->rotateImage("#000", 90); // rotate 90 degrees CW
				
				break;
			case \Imagick::ORIENTATION_LEFTBOTTOM: 
				$this->imagick->rotateImage("#000", -90); // rotate 90 degrees CCW
				
				break;
		}

		$this->imagick->stripImage();
	
		// Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!
		$this->imagick->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
	}

	
	/**
	 * Save the image as the image type the original image was
	 *
	 * @param  String[type] $savePath     - The path to store the new image
	 * @param  int $imageQuality 	  - The quality level of image to create
	 *
	 * @return Saves the image
	 */
	public function saveImage($savePath, $imageQuality= 100)
	{
		/*Get Extension */

		$path = explode('.', $savePath);
		
		$ext = strtolower($path[1]);


		switch($ext)
		{
			case 'pdf':
			case 'doc':		
			case 'docx':
			case 'xls':
			case 'xlsx':
			case 'jpeg' :
			case 'png':
				$ext = 'jpg'; 
				break;
		}

		//$this->imagick->setBackgroundColor(new ImagickPixel('transparent'));
		//$this->imagick->paintTransparentImage($imagick->getImageBackgroundColor(), 0, 10000);
		//$this->imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);

		if($ext === 'jpg')
		{
			$this->imagick->setImageBackgroundColor('white');
		}

		$this->imagick->setCompressionQuality($imageQuality);
		$this->imagick->setImageFormat($ext);

		if($ext === 'webp')
		{
			$this->imagick->setOption('webp:lossless', 'true');
		}
		
		$this->imagick->stripImage();
		$this->imagick->writeImage($path[0] . '.' . $ext);
	}

	/**
	 * Resize the image to these set dimensions
	 *
	 * @param  int $width        	- Max width of the image
	 * @param  int $height       	- Max height of the image
	 * @param  string $resizeOption - Scale option for the image
	 *
	 * @return Save new image
	 */
	public function resizeTo( $width, $height, $resizeOption = 'default' )
	{
		switch(strtolower($resizeOption))
		{
			case 'bounds':
				$ratio = (float)$height / (float)$width;

				if ($this->origWidth > $this->origHeight || $this->origWidth === $this->origHeight) {
					$this->resizeHeight = $this->resizeHeightByWidth($width);
					$this->resizeWidth  = $width;
			   	} elseif( $this->origWidth < $this->origHeight ) {
				   $this->resizeWidth  = $this->resizeWidthByHeight($height);
				   $this->resizeHeight = $height;
				}
				   
				while($this->resizeWidth > $width || $this->resizeHeight > $height)
				{
					if ($this->origWidth > $this->origHeight ) {
						$this->resizeHeight = $this->resizeHeightByWidth($this->resizeWidth - 1);
						$this->resizeWidth  = $this->resizeWidth - 1;
					} elseif( $this->origWidth < $this->origHeight ) {
					   $this->resizeWidth  = $this->resizeWidthByHeight($this->resizeHeight - 1);
					   $this->resizeHeight = $this->resizeHeight - 1;
					}
				}

			    break;
			case 'exact':
				$this->resizeWidth = $this->origWidth;
				$this->resizeHeight = $this->origHeight;

				while($this->resizeWidth > $width && $width < $this->origWidth && $this->resizeHeight > $height && $height < $this->origHeight)
				{
					if ($this->origWidth >= $this->origHeight ) 
					{
						$this->resizeHeight = $this->resizeHeightByWidth($this->resizeWidth - 1);
						$this->resizeWidth  = $this->resizeWidth - 1;
					} 
					elseif($this->origWidth < $this->origHeight) 
					{
					   $this->resizeWidth  = $this->resizeWidthByHeight($this->resizeHeight - 1);
					   $this->resizeHeight = $this->resizeHeight - 1;
					}
				}

				

			    break;
			case 'maxwidth':
				$this->resizeWidth  = $width;
				$this->resizeHeight = $this->resizeHeightByWidth($width);
			    break;
			case 'maxheight':
				$this->resizeWidth  = $this->resizeWidthByHeight($height);
				$this->resizeHeight = $height;
				break;
			case 'padratio':
				$ratio = (float)$height / (float)$width;

				
				$newHeight = $this->origWidth * $ratio;
				$newWidth  = $this->origWidth;
				
				while($newHeight < $this->origHeight)
				{
					$newWidth++;

					$newHeight = $newWidth * $ratio;

					
				}


				//$this->imagick->setBackgroundColor(new ImagickPixel('transparent'));

				$this->imagick->extentImage($newWidth, $newHeight, (int)(($this->origWidth - $newWidth) / 2), (int)(($this->origHeight - $newHeight) / 2));



				//$this->imagick->cropImage($newWidth, $newHeight, abs(($newWidth - $this->origWidth)) / 2, abs(($newHeight - $this->origHeight)) / 2);
				$this->origWidth = $this->imagick->getImageWidth();
				$this->origHeight = $this->imagick->getImageHeight();
				
				$this->resizeWidth  = $width;
				$this->resizeHeight = $this->resizeHeightByWidth($width);

				break;
            case 'ratio':
                $ratio = (float)$height / (float)$width;
                
                for($newWidth = $this->origWidth; $newWidth >= 1; $newWidth--)
                {
                    $newHeight = (int)round($ratio * (float)$newWidth, 0);


                    if($newWidth <= $this->origWidth && $newHeight <= $this->origHeight)
                    {
                        break;
                    }
                }


                $this->imagick->cropImage($newWidth, $newHeight, (int)(abs(($newWidth - $this->origWidth)) / 2), (int)(abs(($newHeight - $this->origHeight)) / 2));
                $this->origWidth = $this->imagick->getImageWidth();
	    		$this->origHeight = $this->imagick->getImageHeight();
                
                $this->resizeWidth  = $width;
				$this->resizeHeight = $this->resizeHeightByWidth($width);

                break;
			default:
				if($this->origWidth > $width || $this->origHeight > $height)
				{
					if ( $this->origWidth > $this->origHeight ) {
				    	 $this->resizeHeight = $this->resizeHeightByWidth($width);
			  			 $this->resizeWidth  = $width;
					} elseif( $this->origWidth < $this->origHeight ) {
						$this->resizeWidth  = $this->resizeWidthByHeight($height);
						$this->resizeHeight = $height;
					}
					else
					{
						$this->resizeWidth = $width;
		            	$this->resizeHeight = $height;
					}
				} 
				else 
				{
		            $this->resizeWidth = $width;
		            $this->resizeHeight = $height;
				}
				
			    break;
		}

		if($this->resizeWidth < 2000 && $this->resizeHeight < 2000)
		{
			$this->imagick->resizeImage($this->resizeWidth, $this->resizeHeight, \Imagick::FILTER_LANCZOS, 1);
		}

		switch(strtolower($resizeOption))
		{
			case 'exact':
				


				if($this->imagick->getImageWidth() !== $width || $this->imagick->getImageHeight() !== $height)
				{
					if($this->imagick->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_ACTIVATE || $this->imagick->getImageAlphaChannel()  === true)
					{
						$this->imagick->setImageBackgroundColor('transparent');
					}
					else
					{
						$this->imagick->setImageBackgroundColor('white');
					}
					
					$this->imagick->cropImage(
						($width < $this->imagick->getImageWidth() ? $width : $this->imagick->getImageWidth()), 
						($height < $this->imagick->getImageHeight() ? $height : $this->imagick->getImageHeight()),
						($width < $this->imagick->getImageWidth() ? (int)(abs(($width - $this->imagick->getImageWidth())) / 2) : 0), 
						($height < $this->imagick->getImageHeight() ? (int)(abs(($height - $this->imagick->getImageHeight())) / 2) : 0));
					
				}

				if($this->imagick->getImageWidth() !== $width || $this->imagick->getImageHeight() !== $height)
				{
					if($this->imagick->getImageAlphaChannel() === \Imagick::ALPHACHANNEL_ACTIVATE || $this->imagick->getImageAlphaChannel()  === true)
					{
						$this->imagick->setImageBackgroundColor('transparent');
					}
					else
					{
						$this->imagick->setImageBackgroundColor('white');
					}

					$this->imagick->extentImage($width, $height, 0 - (int)(abs((int)($width - $this->imagick->getImageWidth())) / 2), 0 - (int)(abs((int)($height - $this->imagick->getImageHeight())) / 2));
				}

				break;
		}
	}
	/**
	 * Get the resized height from the width keeping the aspect ratio
	 *
	 * @param  int $width - Max image width
	 *
	 * @return Height keeping aspect ratio
	 */
	private function resizeHeightByWidth($width)
	{
		return floor(($this->origHeight/$this->origWidth)*$width);
	}
	/**
	 * Get the resized width from the height keeping the aspect ratio
	 *
	 * @param  int $height - Max image height
	 *
	 * @return Width keeping aspect ratio
	 */
	private function resizeWidthByHeight($height)
	{
		return floor(($this->origWidth/$this->origHeight)*$height);
	}
}
?>