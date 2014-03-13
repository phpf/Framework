<?php

namespace Phpf\Filesystem;

class Command {
	
	const DEFAULT_LOG_FILE = 'phpf-fs-cmd.txt';
	
	protected $file;
	
	protected $log_file;
	
	public function __construct( $file, $log_file = null ){
		
		$file = realpath($file);
				
		if ( !file_exists($file) || !is_readable($file) ){
			throw new \InvalidArgumentException("File $file must be a readable file.");
		}
		
		$this->file = $file;
		
		if ( !isset($log_file) ){
			$log_file = self::DEFAULT_LOG_FILE;
		}
		
		$this->log_file = realpath($log_file);
	}
	
	public function run(){
		
		if ( 'WIN' === strtoupper(substr(PHP_OS, 0, 3)) ){
			$this->runWindows();
		} else {
			$this->runUnix();
		}
	}
	
	protected function runUnix(){
		$command = 'php '.$this->file.'';
		$command.= ' > "'.$this->log_file.'" 2>&1';
		exec($command);
	}
	
	protected function runWindows(){
		
		if ( PHP_VERSION >= '5.4' && !extension_loaded('com_dotnet') ){
			dl('php_com_dotnet.dll');
			#throw new \RuntimeException("You must enable the 'com_dotnet' extension.");
		}
		
		$command = '%comspec% /c '; 
		$command.= 'php '.$this->file.'';
		$command.= ' > "'.$this->log_file.'" 2>&1';
		
		try {
			$WshShell = new \COM("WScript.Shell");
		} catch (\com_exception $exception) {
			throw new \RuntimeException('Unable to create a new COM object: '. $exception->getMessage());
		}
		
		$oExec = $WshShell->Run($command, 1, false);
	}
}
