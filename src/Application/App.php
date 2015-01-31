<?php

namespace xpl\Framework\Application;

use xpl\Routing\RoutableInterface;
use xpl\Routing\RouteInterface as Route;
use xpl\Foundation\RequestInterface as Request;
use xpl\Foundation\ResponseInterface as Response;
use xpl\Foundation\ControllerInterface as Controller;

class App extends \xpl\Bundle\Application implements RoutableInterface 
{
	
	// Called on construct
	protected function onInit() {
		$this->autoloadInit();
		$this->loadIncludes();
	}
	
	/**
	 * Called when one of the application's routes is matched by the router.
	 * 
	 * This method is called when the "route" event is triggered.
	 * 
	 * Note: the controller has not yet been instantiated at this point.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @param \xpl\Foundation\RequestInterface $request
	 */
	public function onRoute(Route $route, Request $request) {
	}
	
	/**
	 * Called prior to the invoking the controller action for the route.
	 * 
	 * This method is called on the "dispatch" event.
	 * 
	 * @param \xpl\Routing\RouteInterface $route
	 * @param \xpl\Foundation\RequestInterface $request
	 */
	public function onDispatch(Route $route, Request $request) {
	}
	
	/**
	 * Called before response is sent when this is the primary app.
	 * 
	 * @param \xpl\Web\Response $response
	 */
	public function onRespond(Response $response) {
	}
	
	/**
	 * Checks whether the app is the primary app.
	 * 
	 * @return boolean
	 */
	public function isPrimary() {
		return (bool)$this->config->get('is_primary');
	}
	
	/**
	 * Sets a named app directory path.
	 * 
	 * @param string $name Name used to identify the directory.
	 * @param string $path Relative path to directory from application root.
	 * @return $this
	 */
	public function setPath($name, $path) {
		$this->config->setPath($name, $path);
		return $this;
	}
	
	/**
	 * Returns a directory path by name, or the root path if given null.
	 * 
	 * @param string $name Name of directory, or NULL for the root path.
	 * @return string Directory path if found, otherwise null.
	 */
	public function getPath($name = null) {
		return $this->config->getPath($name);
	}
	
	/**
	 * Returns an array of all the app's directory paths.
	 * 
	 * @return array Associative array of directories.
	 */
	public function getPaths() {
		return $this->config->get('paths');
	}
	
	/** Alias of getName() */
	public function getId() {
		return $this->getName();
	}
	
	/**
	 * Returns a collection of routes.
	 * 
	 * @return \xpl\Routing\Route\Collection
	 */
	public function getRoutes() {
		
		$this->registerControllers();
		
		$cache = env('routes.cache');
		
		if ($cache && $cached = cache_get("app.{$this->getName()}", 'routes')) {
			return unserialize($cached);
		}
		
		$file = $this->getPath().'config/routes.php';
		
		if (! file_exists($file)) {
			throw new \RuntimeException("Missing routes.php file in application config/ directory.");
		}
		
		$collection = require $file;
		
		if ($cache) {
			cache_set("app.{$this->getName()}", serialize($collection), 'routes', 43200);
		}
		
		trigger('routes.init', $collection, $this);
		
		return $collection;
	}
	
	/**
	 * Registers the controller classes (set in config.php) with the DI container.
	 */
	public function registerControllers() {
		if ($classes = $this->getConfig('controllers')) {
			array_walk($classes, array($this, 'registerController'));
		}
	}
	
	/**
	 * Registers a controller class with the DI container.
	 * 
	 * @param string $class Controller class name.
	 */
	public function registerController($class) {
		
		$class = trim($class, '\\');
		
		register($class, function (Request $request, Response $response, Route $route, RoutableInterface $app) use($class) {
			return new $class($request, $response, $route, $app);
		});
	}
	
	protected function autoloadInit() {
		
		$namespace = $this->getNamespace();
		$autoload = $this->getConfig('autoload');
		
		if (! $namespace || ! $autoload) {
			return;
		}
		
		$dir = $this->getConfig('autoload_dir') ?: 'classes';
		
		if ($path = $this->getPath($dir)) {
			
			$namespace = rtrim($namespace, '\\').'\\';
			
			if ('psr-0' === strtolower($autoload)) {
				di('autoloader')->addPsr0($namespace, $path);
			} else {
				di('autoloader')->addPsr4($namespace, $path);
			}
		}
	}
	
	protected function loadIncludes() {
		
		$__path = $this->getPath();
		
		$includes = (array)$this->getConfig('include');
		
		$includes[] = 'config/functions.php';
		$includes[] = 'config/events.php';
		
		foreach($includes as $__include) {
			if (file_exists($__path.$__include)) {
				include_once $__path.$__include;
			}
		}
	}
	
}
