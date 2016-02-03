<?php
namespace Rescale;

class Filesystem {
	const MATCH_FILES       = 1;
	const MATCH_DIRECTORIES = 2;
	const MODE_RECURSIVE    = 4;
	const MODE_ABSOLUTE     = 8;
	const MATCH_ALL         = 3;
	const MODE_ALL          = 12;

	public static function purgeDirectory($directory) {
		if(is_dir($directory)) {
			$children = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

			foreach($children as $child) {
				if($child->isDir()) {
					rmdir($child->getRealPath());
				} else {
					unlink($child->getRealPath());
				}
			}
		}
	}

	public static function removeDirectory($directory) {
		if(is_dir($directory)) {
			self::purgeDirectory($directory);

			rmdir($directory);
		}
	}

	public static function createDirectory($directory) {
		if(!is_dir($directory)) {
			mkdir($directory, 0770, true);
		}
	}

	public static function removeFile($file) {
		if(is_file($file)) {
			unlink($file);
		}
	}

	public static function createFile($file, $content) {
		self::createDirectory(dirname($file));

		file_put_contents($file, $content, LOCK_EX);
	}

	public static function find($directory, $pattern = NULL, $flags = self::MATCH_ALL) {
		$return    = array();
		$iterator  = ($flags & self::MODE_RECURSIVE) ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
		$iterator  = ($pattern !== NULL) ? new \RegexIterator($iterator, '/' . $pattern . '/i') : $iterator;
		$directory = preg_quote($directory . '/', '/');

		foreach($iterator as $path) {
			if((($flags & self::MATCH_FILES) && $path->isFile() === true) || (($flags & self::MATCH_DIRECTORIES) && $path->isDir() === true)) {
				$return[] = ($flags & self::MODE_ABSOLUTE) ? $path->getPathname() : preg_replace('/^' . $directory . '/', '', $path->getPathname());
			}
		}

		sort($return);

		return $return;
	}
}
?>