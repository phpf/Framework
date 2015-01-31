<?php

namespace xpl\Framework;

class SimpleLog 
{	
	protected $file;
	protected $messages = array();
	
	protected static $instance;
	
	/**
	 * @return \xpl\Framework\SimpleLog
	 */
	public static function instance() {
		if (! isset(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}
	
	/**
	 * Sets the log file.
	 * 
	 * @param string $file
	 */
	public static function setFile($file) {
		static::instance()->setLogFile($file);
	}
	
	/**
	 * Returns the log file.
	 * 
	 * @return string
	 */
	public static function getFile() {
		return static::instance()->getLogFile();
	}
	
	/**
	 * Logs a message.
	 * 
	 * @param string $message
	 * @param string $func [Optional]
	 */
	public static function log($message, $extra = null) {
		static::instance()->logMessage($message, $extra);
	}
	
	public function setLogFile($file) {
		
		// Check if writable only if file exists, since it will be created if not.
		if (file_exists($file) && ! is_writable($file)) {
			throw new \InvalidArgumentException("Given unwritable log file: '$file'.");
		}
		
		$this->file = $file;
		
		register_shutdown_function(array($this, 'write'));
	}
	
	public function getLogFile() {
		return isset($this->file) ? $this->file : null;
	}
	
	public function logMessage($message, $extra = null) {
			
		$msg = date('Y-m-d g:i:s a').' "'.$message.'"';

		if ($extra) {
			$msg .= "\t{$extra}";
		}

		$this->messages[] = $msg;
	}
	
	public function write() {
		
		if (! empty($this->messages) && isset($this->file)) {
		
			$string = '';
		
			foreach($this->messages as $msg) {
				$string .= PHP_EOL.$msg;
			}
		
			file_put_contents($this->file, $string, FILE_APPEND|LOCK_EX);
		}
	}
}
