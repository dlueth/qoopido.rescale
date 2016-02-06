<?php
namespace Rescale;

class Cache {
	protected $source;
	protected $target;
	protected $size;
	protected $modified;

	public function __construct($path, $suffix, $dimensions, $quality) {
		$this->source   = RESCALE_SOURCE . $path;
		$this->target   = RESCALE_TARGET . $path . '/'. $dimensions->width . 'x' . $dimensions->height . '.' . $quality . '.' . $suffix;
		$this->modified = filemtime($this->source);
	}

	public function get() {
		if($this->validate() === true && file_exists($this->target)) {
			touch($this->target);

			return file_get_contents($this->target);
		}

		return false;
	}

	public function set($data) {
		\Rescale\Filesystem::createFile($this->target, $data);
	}

	public function __get($property) {
		switch($property) {
			case 'modified':
				return $this->modified;

				break;
			case 'size':
				return filesize($this->target);

				break;
		}
	}

	protected function validate() {
		$directory = dirname($this->target);
		$meta      = $directory . '/.meta';
		$state     = is_file($meta) ? (int) file_get_contents($meta) : NULL;

		if($state !== NULL && $state !== $this->modified) {
			\Rescale\Filesystem::purgeDirectory($directory);

			$state = NULL;
		}

		if($state === NULL) {
			\Rescale\Filesystem::createFile($meta, $this->modified);
		}

		return $state === NULL ? false : true;
	}

	public static function purge() {
		$files = \Rescale\Filesystem::find(RESCALE_TARGET, '\.meta$', \Rescale\Filesystem::MATCH_FILES | \Rescale\Filesystem::MODE_RECURSIVE);

		// remove all thumbnails of deleted source files
		foreach($files as $file) {
			$directory = dirname($file);
			$file      = RESCALE_SOURCE . '/' . $directory;

			if(!is_file($file)) {
				\Rescale\Filesystem::removeDirectory(RESCALE_TARGET . '/' . $directory);
			}
		}

		// remove thumbnails that have not been used for the last 2 weeks
		$files = \Rescale\Filesystem::find(RESCALE_TARGET, '.*(?<!\.meta|\.gitignore)$', \Rescale\Filesystem::MATCH_FILES | \Rescale\Filesystem::MODE_RECURSIVE);

		foreach($files as $file) {
			$file = RESCALE_TARGET . '/' . $file;

			if(filemtime($file) < strtotime('-2 weeks')) {
				\Rescale\Filesystem::removeFile($file);
			}
		}
	}
}
?>