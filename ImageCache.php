<?php

/**
 * The primary class file for PHP Image Compressor & Caching
 *
 * This file is to be used in any PHP project that requires image compression
 *
 * @package PHP Image Compressor & Caching
 * @author Erik Nielsen (erik@312development.com) (http://312development.com)
 *
 */

class ImageCache {
	private $root; /** @string  */
	private $src_root; /** @string  */
	private $created_dir; /** @bool  */
	private $opts; /** @array  */
	private $base; /** @string  */

	public function __construct($filebase = '', $dir = null, $create_dir = true, $opts = array()) {
		/**
		 * @param $filebase (string) - The base URL that will be included in the final output for the image source; used if image source is an absolute URL
		 * @param $dir (string/null) - The base directory that houses the image being compressed
		 * @param $create_dir (bool) - Whether or not to create a new directory for the compressed images
		 * @param $opts (array) - An array of available options that the user can include to the overwrite default settings
		 */

		$defaults = array(
			'quality' => 90,
			'compressed_dir' => '/compressed'
		);
		if(is_null($dir))
			$dir = dirname(__FILE__);

		$this->root = $dir;
		$this->src_root = $dir;
		$this->base = $filebase;
		$this->opts = array_merge($defaults, $opts);
		if(!$create_dir)
			return $this;
		
		$this->createDirectory();
		return $this;
	}

	public function createDirectory() {
		/**
		 * 
		 * Creates a new directory, if so requested to by the constructor function
		 * 
		 * @return $this (obj) - Returns the class for continuance
		 */
		if(!is_dir($this->root . $this->opts['compressed_dir'])) {
			try {
				$pathinfo = pathinfo($this->root . $this->opts['compressed_dir']);
				chmod($pathinfo['dirname'], 0777);
				mkdir($this->root . $this->opts['compressed_dir'], 0777);
				$this->root .= $this->opts['compressed_dir'];
				$this->created_dir = true;
				return $this;
			} catch(Exception $e) {
				echo 'There was an error creating the new directory:' . "\n";
				$this->debug($e);
			}
		}
		$this->root .= $this->opts['compressed_dir'];
		$this->created_dir = true;
		return $this;
	}

	public function compress($src) {
		/**
		 * 
		 * The primary function - reads the image, the compresses, moves, and returns a cached copy
		 * 
		 * @param $src (string) - The image that is to be compressed
		 * @return $out (array) - Information on the newly compressed image, including the new source with modtime query, the height, and the width
		 */
		$src = $this->src_root . '/' . $src;
		$filename = $this->getFilename($src);
		$dest = $filename . '-compressed.jpg';
		if($out = $this->checkExists($dest))
			return $out;
		$info = getimagesize($src);
		switch($info['mime']) {
			case 'image/jpeg' :
				$image = imagecreatefromjpeg($src);
				break;
			case 'image/gif' :
				$image = imagecreatefromgif($src);
				break;
			case 'image/png' :
				$image = imagecreatefrompng($src);
				break;
		}
		if($this->created_dir)
			$dest = $this->root . '/' . $dest;

		imagejpeg($image, $dest, $this->opts['quality']);
		$info = getimagesize($dest);
		$path = pathinfo($dest);
		$src = '/' . end(explode('/', $path['dirname'])) . '/' . $path['basename'];
		$src .= '?modtime=' . filemtime($this->root . '/' . $path['basename']);
		$out = array(
			'src' => $this->base . $src,
			'width' => $info[0],
			'height' => $info[1]
		);
		return $out;
	}

	private function checkExists($img) {
		/**
		 * 
		 * Checks if the compressed version of the image already exists
		 * 
		 * @param $img (string) - The basename of the image we're checking for
		 * @return $out (array) - Information on the newly compressed image, including the new source with modtime query, the height, and the width
		 * @return false (bool) - Returns false if the image doesn't exist
		 */
		if(file_exists($this->root . '/' . $img)) {
			$info = getimagesize($this->root . '/' . $img);
			$path = pathinfo($this->root . '/' . $img);
			$src = '/' . end(explode('/', $path['dirname'])) . '/' . $path['basename'];
			$src .= '?modtime=' . filemtime($this->root . '/' . $path['basename']);
			$out = array(
				'src' => $this->base . $src,
				'width' => $info[0],
				'height' => $info[1]
			);
			return $out;
		}
		return false;
	}

	public function getFilename($file) {
		/**
		 * 
		 * Just grabs the filename without the file extension
		 * 
		 * @param $file (string) - The filename whose name we want
		 * @return $filename (string) - The filename without the extension
		 */
		$pathinfo = pathinfo($file);
		$filename = $pathinfo['filename'];
		return $filename;
	}

	private function debug($a) {
		/**
		 * 
		 * Basic debug functions
		 * 
		 */
		echo '<pre>';
		print_r($a);
		echo '</pre><hr>';
	}
}

?>