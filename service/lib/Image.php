<?php
namespace Rescale;

class Image {
	protected $image;

	public    $width;
	public    $height;
	public    $animated    = false;
	public    $transparent = false;

	public function __construct($image = NULL) {
		if(!empty($image) && is_file($image) && is_readable($image)) {
			$type = strtolower(preg_replace('/^.+\.(jpe?g|png|gif)$/i', '\1', $image));
			$type = ($type === 'jpg') ? 'jpeg' : $type;

			switch($type) {
				case 'gif':
					$this->image       = imagecreatefromgif($image);
					$this->animated    = $this->_isAnimated($image);
					$this->transparent = (imagecolortransparent($this->image) >= 0) ? true : false;
					break;
				case 'jpeg':
					$this->image = imagecreatefromjpeg($image);
					break;
				case 'png':
					$this->image = imagecreatefrompng($image);
					break;
			}

			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);

			$this->width  = imagesx($this->image);
			$this->height = imagesy($this->image);
		}
	}

	private function _isAnimated($filename) {
		$handle = fopen($filename, 'rb');
		$frames = 0;

		while(!feof($handle) && $frames < 2) {
			$chunk   = fread($handle, 1024 * 100);
			$frames += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
		}

		fclose($handle);

		return $frames > 1;
	}

	public function resize($width, $height) {
		$ratio = array($width / $this->width, $height / $this->height);

		if($ratio[0] > $ratio[1]) {
			// portrait
			$height = (int) round($this->height * $ratio[0]);
		} else {
			// landscape
			$width = (int) round($this->width * $ratio[1]);
		}

		// process
		if($width != $this->width || $height != $this->height) {
			$temp = imagecreatetruecolor($width, $height);

			imagealphablending($temp, false);
			imagesavealpha($temp, true);
			imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

			$this->image  = $temp;
			$this->width  = $width;
			$this->height = $height;
		}

		return $this;
	}

	public function crop($width, $height, $x = false, $y = false) {
		if($x === false) {
			$x = floor($this->width / 2 - $width / 2);
		}

		if($y === false) {
			$y = floor($this->height / 2 - $height / 2);
		}

		$temp = imagecreatetruecolor($width, $height);

		imagealphablending($temp, false);
		imagesavealpha($temp, true);
		imagecopy($temp, $this->image, 0, 0, $x, $y, $width, $height);

		$this->image  = $temp;
		$this->width  = $width;
		$this->height = $height;

		return $this;
	}

	public function sharpen() {
		$sharpen = array(
			array(-1, -1,  -1),
			array(-1, 24, -1),
			array(-1, -1,  -1),
		);

		imageconvolution($this->image, $sharpen, array_sum(array_map('array_sum', $sharpen)), 0);

		return $this;
	}

	public function get($type = 'png', $interlace = false, $quality = NULL, $filter = PNG_ALL_FILTERS) {
		$type = strtolower($type);

		if($interlace === true) {
			imageinterlace($this->image, 1);
		}

		ob_start();

		switch($type) {
			case 'png':
				$quality = ($quality === NULL) ? 9 : max(0, min(9, (int) $quality));

				imagepng($this->image, NULL, $quality, $filter);
				break;
			case 'jpeg':
				$quality = ($quality === NULL) ? 100 : max(0, min(100, (int) $quality));

				imagejpeg($this->image, NULL, $quality);
				break;
			case 'gif':
				$quality = ($quality === NULL) ? 255 : max(0, min(255, (int) $quality));
				$temp    = imagecreatetruecolor($this->width, $this->height);

				imagecopy($temp, $this->image, 0, 0, 0, 0, $this->width, $this->height);
				imagetruecolortopalette($temp, false, $quality);
				imagecolormatch($this->image, $temp);
				imagegif($temp);

				break;
		}

		return trim(ob_get_clean());
	}
}
?>