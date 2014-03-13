<?php

namespace Phpf\Event\Std;

use Phpf\Event\Event;

class Error extends Event {
	
	public $exceptions;
	
	public function attachException(\Exception $exception){
		$this->exceptions[] = $exception;
	}
	
	public function getExceptions(){
		return $this->exceptions;
	}
	
}
