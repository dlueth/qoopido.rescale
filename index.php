<?php
date_default_timezone_set('Europe/Berlin');
ignore_user_abort(true);

if(defined('RESCALE_SOURCE') === false) {
	define('RESCALE_SOURCE', dirname(dirname(__FILE__)));
}

if(defined('RESCALE_TARGET') === false) {
	define('RESCALE_TARGET', dirname(__FILE__) . '/temp');
}

if(defined('RESCALE_QUALITY_JPEG') === false) {
	define('RESCALE_QUALITY_JPEG', 70);
}

if(defined('RESCALE_QUALITY_PNG') === false) {
	define('RESCALE_QUALITY_PNG', 5);
}

if(defined('RESCALE_QUALITY_GIF') === false) {
	define('RESCALE_QUALITY_GIF', 192);
}

if(defined('RESCALE_PURGE_RATE') === false) {
	define('RESCALE_PURGE_RATE', 1);
}

function autoloader($class) {
	if(preg_match('/^Rescale\\\/', $class) === 1) {
		$file = preg_replace('/^Rescale\\\/', dirname(__FILE__) . '/lib/', $class) . '.php';
		$file = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $file);

		require($file);
	}
}

function normalizeDimensions($targetWidth, $targetHeight, $sourceWidth, $sourceHeight) {
	$dimensions = new \stdClass();
	$ratio      = $targetWidth / $targetHeight;

	$dimensions->width  = $targetWidth;
	$dimensions->height = $targetHeight;

	if($targetWidth > $sourceWidth) {
		$dimensions->width  = $sourceWidth;
		$dimensions->height = round($dimensions->width / $ratio);
	}

	if($targetHeight > $sourceHeight) {
		$dimensions->height = $sourceHeight;
		$dimensions->width  = round($dimensions->height * $ratio);
	}

	return $dimensions;
}

spl_autoload_register('autoloader');

try {
	$parameter = $_GET['resize'];

	if(isset($parameter) && is_array($parameter)) {
		$path   = (isset($parameter['file']) && !empty($parameter['file'])) ? (string) $parameter['file'] : NULL;
		$width  = (isset($parameter['width']) && !empty($parameter['width']) && is_numeric($parameter['width'])) ? (int) $parameter['width'] : NULL;
		$height = (isset($parameter['height']) && !empty($parameter['height']) && is_numeric($parameter['height'])) ? (int) $parameter['height'] : NULL;
		$dpr    = (isset($parameter['dpr']) && !empty($parameter['dpr']) && is_numeric($parameter['dpr'])) ? (float) $parameter['dpr'] : NULL;
		$source = ($path !== NULL) ? RESCALE_SOURCE . $path : NULL;

		if($source !== NULL && is_file($source) && $width !== NULL && $height !== NULL && $dpr !== NULL) {
			$type       = strtolower(preg_replace('/^.+\.(jp(e?)g|png|gif)$/i', '\1', $path));
			$type       = ($type === 'jpg') ? 'jpeg' : $type;
			$dpr        = min(2, round($dpr) / 100);
			$image      = new \Rescale\Image($source);
			$dimensions = normalizeDimensions($width * $dpr, $height * $dpr, $image->width, $image->height);
			$cache      = new \Rescale\Cache($path, $type, $dimensions);

			if(($data = $cache->get()) === false) {
				$image
					->resize(array($dimensions->width, $dimensions->height))
					->crop($dimensions->width, $dimensions->height);

				switch($type) {
					case 'jpeg':
						$data = $image->get('jpeg', true, RESCALE_QUALITY_JPEG);
						break;
					case 'png':
						$data = $image->get('png', true, RESCALE_QUALITY_PNG);
						break;
					case 'gif':
						$data = $image->get('gif', true, RESCALE_QUALITY_GIF);
						break;
				}

				$cache->set($data);
			}

			header('Accept-Ranges: bytes');
			header('Cache-Control: max-age=315360000');
			header('Cache-Control: public', false);
			header('Content-Length: ' . $cache->size);
			header('Content-Type: image/' . $type);
			header('Expires: ' . gmdate('r', strtotime('+10 years')) . ' GMT');
			header('Last-Modified: ' . gmdate('r', $cache->modified) . ' GMT');
			header('pragma: public');

			echo $data;

			flush();

			if(mt_rand(0, 100) <= RESCALE_PURGE_RATE) {
				\Rescale\Cache::purge();
			}

			die();
		}
	}
} catch(\Exception $exception) {}

header('HTTP/1.1 404 Not Found');
die();
?>