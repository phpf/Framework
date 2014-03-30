<?php

namespace Phpf\Package;

use Phpf\Filesystem\Filesystem;
use Phpf\Event\Container as Events;

class Loader {
	
	protected $app;
	
	protected $config;
	
	protected $default_config = array(
		'dirs' => array(
			'views' => 'views',
			'assets' => 'public',
		),
		'files' => array(
			'config/routes.php',
			'config/tables.php',
		),
	);
	
	public function __construct(\Phpf\App\App &$app){
			
		$this->app =& $app;
		
		$this->configure($app->get('config')->get('packages-config'));
	}
	
	/**
	 * Loads the package.
	 */
	public function load( iPackage $package ){
			
		$app = $this->app;
	
		$pPath = rtrim($package->getPath(), '/\\') . '/';
		$pName = basename($package->getPath());
		
		$pkgFile = $pPath.$pName.'.php';
		
		// Search in base directory for a file with the same name
		if (file_exists($pkgFile)){
			
			$include = function ($__file__){
				require $__file__;
			};
			
			$include($pkgFile);
		}
		
		$includeWithObject = function ($_file) use($pPath, $app) {
			$_file = ltrim($_file, '/\\');
			if (file_exists($pPath.$_file)) {
				require $pPath.$_file;
			}
		};
		
		$addFilesystemPaths = function ($_dir, $_group) use($pPath, $app) {
			$_dir = trim($_dir, '/\\');
			if (is_dir($pPath.$_dir)){
				$this->app->get('filesystem')->add($pPath.$_dir.'/', $_group);
			}
		};
		
		foreach($this->config['files'] as $file) {
			$includeWithObject($file);
		}
		
		foreach($this->config['dirs'] as $group => $dir) {
			$addFilesystemPaths($dir, $group);
		}
		
		$package->setLoaded(true);
		
		return true;
	}
	
	protected function configure( $config ) {
		
		if (isset($config['dirs'])) {
			$dirs = $config['dirs'];
		} else {
			$dirs = $this->default_config['dirs'];
		}
		
		if (isset($config['files'])) {
			$files = $config['files'];
		} else {
			$files = $this->default_config['files'];
		}
		
		$this->config = array(
			'dirs' => $dirs, 
			'files' => $files
		);
	}
	
}
