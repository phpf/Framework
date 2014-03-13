<?php

namespace Phpf\Package;

use Phpf\Filesystem\Filesystem;
use Phpf\Event\Container as Events;

class Loader {
	
	protected $routes_file = '/config/routes.php';
	
	protected $tables_file = '/config/tables.php';
	
	protected $views_dir = '/Views/';
	
	protected $assets_dir = '/Public/';
	
	protected $filesystem;
	
	public function __construct( Filesystem &$filesystem ){
		$this->filesystem =& $filesystem;
	}
	
	/**
	 * Loads the package.
	 * 
	 * 1. Loads PHP file in base directory with same name as package.
	 * 2. Loads /config/routes.php if it exists.
	 * 3. Loads /config/tables.php if it exists.
	 * 4. Adds /Views/ directory to filesystem under "views" group if it exists.
	 * 5. Adds /Public/ directory to fs under "assets" group if it exists.
	 */
	public function load( iPackage $package ){
		
		$pkgPath = $package->getPath();
		$pkgName = basename($pkgPath);
		
		$fpackage = $pkgPath.'/'.$pkgName.'.php';
		$froutes = $pkgPath.$this->routes_file;
		$ftables = $pkgPath.$this->tables_file;
		$dviews = $pkgPath.$this->views_dir;
		$dassets = $pkgPath.$this->assets_dir;
		
		// Search in base directory for a file with the same name
		if (file_exists($fpackage)){
			
			$include = function ($__file__){
				require $__file__;
			};
			
			$include($fpackage);
		}
		
		// routes
		if (file_exists($froutes)){
				
			$include = function ($__file__) {
					
				$router = \Router::instance();
					
				require $__file__;
			};
			
			$include($froutes);
		}
		
		// tables
		if (file_exists($ftables)){
				
			$include = function ($__file__) {
					
				$database = \Database::instance();
				
				require $__file__;
			};
			
			$include($ftables);
		}
		
		// views
		if (is_dir($dviews)){
			$this->filesystem->add($dviews, 'views');
		}
		
		// assets
		if (is_dir($dassets)){
			$this->filesystem->add($dassets, 'assets');
		}
		
		$package->setLoaded(true);
		
		return true;
	}
	
	/**
	 * Whether the package has a /config/routes.php file.
	 */
	public function hasRoutes(iPackage $package){
			
		return file_exists($package->getPath().$this->routes_file);
	}
	
	/**
	 * Whether the package has a /config/tables.php file.
	 */
	public function hasTables(iPackage $package){
			
		return file_exists($package->getPath().$this->tables_file);
	}
	
	/**
	 * Whether the package has a /Views/ directory.
	 */
	public function hasViews(iPackage $package){
			
		return is_dir($package->getPath().$this->views_dir);
	}
	
	/**
	 * Whether the package has a /Public/ directory.
	 */
	public function hasAssets(iPackage $package){
		
		return is_dir($package->getPath().$this->assets_dir);
	}
	
}
