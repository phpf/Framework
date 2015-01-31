<?php

namespace xpl\Framework\Application;

class Factory implements \xpl\Bundle\ProviderInterface
{
	
	const BASE_CLASS = 'xpl\Framework\Application\App';
	
	protected $path;
	protected $class;
	protected $allow_custom_class = true;
	protected $apps;
	
	/**
	 * Constructor.
	 * 
	 * @param string $apps_path Filesystem path to applications directory.
	 * @param string $class [Optional] Default application class.
	 */
	public function __construct($apps_path, $class = self::BASE_CLASS) {
		
		if (! $realpath = realpath($apps_path)) {
			throw new \RuntimeException("Apps directory does not exist: '$apps_path'.");
		}
		
		if (! $this->isValidClass($class)) {
			throw new \RuntimeException("App class '$class' must implement ".static::BASE_CLASS);
		}
		
		$this->path = $realpath.DIRECTORY_SEPARATOR;
		$this->class = $class;
		$this->apps = array();
	}
		
	public function isValidClass($class) {
		return trim($class, '\\') === static::BASE_CLASS || is_subclass_of($class, static::BASE_CLASS);
	}
	
	public function allowCustomClass($value = null) {
		
		if (! isset($value)) {
			return $this->allow_custom_class;
		}
		
		$this->allow_custom_class = (bool)$value;
	}
	
	public function exists($name) {
		return isset($this->apps[$name]);
	}
	
	public function get($name) {
		return $this->exists($name) ? get_app($name) : null;
	}
	
	/**
	 * Implements \xpl\Bundle\ProviderInterface
	 * 
	 * @param string $type Bundle type (must be "app").
	 * @param string $name Bundle name (app name).
	 * @return \xpl\Bundle\Application
	 */
	public function provideBundle($type, $name) {
		
		if (isset($this->apps[$name])) {
			throw new \LogicException("Application already exists: '$name'.");
		}
		
		if (! is_dir($app_path = $this->path.$name.'/')) {
			throw new \RuntimeException("Invalid directory for application '$name': '$app_path'.");
		}
		
		$config = $this->configure($name, $app_path);
		$class = $this->getAppClass($config);
		
		// Set first booted app as primary
		if (empty($this->apps)) {
			$config->set('is_primary', true);
		}
		
		$this->apps[$name] = $class;
		
		return new $class($config);
	}

	protected function configure($name, $path) {
		
		$config = new Config($name, $path);
		
		$__file = $config->getPath().'config/config.php';
		
		if (is_readable($__file)) {
			
			$vars = include $__file;
			
			if (isset($vars['dirs'])) {
				
				foreach($vars['dirs'] as $dirname => $dirpath) {
					$config->setPath($dirname, $dirpath);
				}
				
				unset($vars['dirs']);
			}
			
			foreach($vars as $key => $value) {
				$config->set($key, $value);
			}
		}
		
		return $config;
	}

	protected function getAppClass(Config $config) {
		
		// Use a custom App class?
		if ($this->allow_custom_class) {
		
			$__file = $config->get('class_file') ?: $config->getPath().'Application.php';
			
			if (is_readable($__file)) {
				
				include $__file;
				
				if ($config->has('namespace')) {
					$class = $config->get('namespace').'\\Application';
				} else {
					$class = ucfirst($name).'Application';
				}
				
				if (class_exists($class, false) && $this->isValidClass($class)) {
					return $class;
				}
			}
		}
		
		return $this->class;
	}
	
}

