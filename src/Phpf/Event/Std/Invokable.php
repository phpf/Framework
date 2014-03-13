<?php

namespace Phpf\Event\Std;

use Phpf\Event\Event;

class Invokable extends Event {
	
	protected $call;
	
	public function onInvoke( $call ){
		
		if ( !is_callable($call) ){
			trigger_error("Cannot attach uncallable function to invokable event.");
			return null;
		}
		
		$this->call = $call;
	}
	
	public function __invoke( $args = array() ){
		
		if ( isset($this->call) ){
			return call_user_func_array($this->call, $args);
		}
		
	}
	
}
