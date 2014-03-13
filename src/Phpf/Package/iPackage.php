<?php

namespace Phpf\Package;

interface iPackage {
	
	public function getType();
	
	public function getId();
	
	public function getPath();
	
	public function getUid();
	
	public function isLoaded();
	
}
