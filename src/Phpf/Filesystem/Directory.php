<?php

namespace Phpf\Filesystem;

use InvalidArgumentException;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Directory extends RecursiveDirectoryIterator {
	
	/**
	 * glob() consts
	 */
	const NOSORT = 32;
	const SLASH = 8;
	const BRACE = '128';
	const DIRS = 1073741824;
	
	/**
	 * Real directory path.
	 * @var string
	 */
	protected $path;
	
	/**
	 * Filesystem handle.
	 * @var resource
	 */
	protected $handle;
	
	/**
	 * Whether we're on Windows
	 * @var boolean
	 */
	protected $is_win;
	
	/**
	 * Root directories
	 * @var array
	 */
	protected $dirs;
	
	/**
	 * Root files
	 * @var array
	 */
	protected $files;
	
	/**
	 * Recursion depth used in find()
	 * @var int
	 */
	protected $recursion_depth = 5;
	
	protected $directory;
	
	protected $iterator;
	
	public function __construct($path) {
		
		$path = rtrim($path, '/\\');
	#	if (! is_dir($path = realpath($path))) {
	#		throw new InvalidArgumentException("Given path is not a directory - $path");
	#	}
		
		$this->path = $path;
		
		$this->is_win = '\\' === DIRECTORY_SEPARATOR;
		
		$this->flags = 
			  FilesystemIterator::KEY_AS_PATHNAME 
			| FilesystemIterator::CURRENT_AS_FILEINFO 
			| FilesystemIterator::SKIP_DOTS 
			| FilesystemIterator::UNIX_PATHS;
		
		parent::__construct($this->path, $this->flags);
	}
	
	public function getIterator() {
		
		if (! isset($this->iterator)) {
			$this->iterator = new RecursiveIteratorIterator($this);
		}
		
		return $this->iterator;
	}
	
	public function iterateRegex($pattern = '/^.+\.php$/i') {
		return new \RegexIterator($this->getIterator(), $pattern, \RecursiveRegexIterator::MATCH, $this->flags);
	}
	
	public function iterateGlob($pattern) {
		return new \GlobIterator($this->path.$pattern, $this->flags);
	}
	
	public function __get($var) {
		return $this->$var;
	}
	
	/*
	public function open() {
		$this->handle = opendir($this->path);
		return $this;
	}
	
	public function close() {
		closedir($this->handle);
		return $this;
	}
	
	public function read() {
		return readdir($this->handle);
	}
	
	public function rewind() {
		rewinddir($this->handle);
		return $this;
	}
	*/
	
	public function __destruct() {
		if (is_resource($this->handle)) {
			$this->close();
		}
	}
	
	public function dirs() {
		
		if (! isset($this->dirs)) {
			$this->addRootItems();
		}
		
		return $this->dirs;
	}
	
	public function files($extension = null) {
		
		if (! isset($this->files)) {
			$this->addRootItems();
		}
		
		if (null === $extension) {
			return $this->files;
		}
		
		$files = array();
		
		foreach($this->files as $name => $path) {
			if (endswith($path, $extension)) {
				$files[$name] = $path;
			}
		}
		
		return $files;
	}
	
	public function findOne($pattern, $recursive = true) {
		
		$flags = $this->is_win ? FNM_NOESCAPE : 0;
		
		if (false === $recursive) {
			foreach($this->getRootItems() as $name => $item) {
				if (fnmatch($pattern, $item, $flags)) {
					return $item;
				}
			}
			return null;
		}
		
		if (! isset($this->glob)) {
			$this->glob = glob_recursive($this->path, $this->recursion_depth);
		}
		
		foreach($this->glob as $path) {
			if (fnmatch($pattern, $path, $flags)) {
				return $path;
			}
		}
		
		return null;
	}
	
	public function findMany($pattern, $recursive = true) {
		
		$return = array();
		$flags = $this->is_win ? FNM_NOESCAPE : 0;
		
		if (false === $recursive) {
			
			foreach($this->getRootItems() as $name => $item) {
				if (fnmatch($pattern, $item, $flags)) {
					$return[] = $item;
				}
			}
			
			return $return;
		}
		
		if (! isset($this->glob)) {
			$this->glob = glob_recursive($this->path, $this->recursion_depth);
		}
		
		foreach($this->glob as $path) {
			if (fnmatch($pattern, $path, $flags)) {
				$return[] = $path;
			}
		}
		
		return $return;
	}
	
	public function findFile($file_pattern) {
		return $this->findOne('*'.ltrim($file_pattern, '*'), true);
	}
	
	public function setRecusionDepth($val) {
		$this->recursion_depth = intval($val);
		return $this;
	}
	
	public function getRecursionDepth() {
		return $this->recursion_depth;
	}
	
	protected function addRootItems() {
		foreach(scandir($this->path) as $item) {
			if (false === strpos($item, '.')) {
				$this->dirs[$item] = $this->path.DIRECTORY_SEPARATOR.$item;
			} elseif ('.' !== $item && '..' !== $item) {
				$this->files[$item] = $this->path.DIRECTORY_SEPARATOR.$item;
			}
		}
	}
	
	protected function getRootItems() {
		
		if (! isset($this->dirs)) {
			$this->addRootItems();
		}
		
		return array_merge($this->files, $this->dirs);
	}
	
}
