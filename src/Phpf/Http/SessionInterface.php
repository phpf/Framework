<?php

namespace Phpf\Http;

interface SessionInterface {
	
	public function start();
	
	public function isStarted();
	
	public function getId();
	
	public function setId($id);
	
	public function getName();
	
	public function setName($name);
	
	public function get($var, $default = null);
	
	public function set($var, $val);
	
	public function exists($var);
	
	public function remove($var);
	
	public function destroy();
	
}
