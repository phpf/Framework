<?php

namespace Phpf\Http;

class Mime {
	
    const JSON	= 'application/json';
    const JS	= 'text/javascript';
    const XML	= 'text/xml';
    const HTML	= 'text/html';
    const XHTML	= 'application/html+xml';
    const CSV	= 'text/csv';
    const TEXT	= 'text/plain';
    const FORM	= 'application/x-www-form-urlencoded';
    const UPLOAD = 'multipart/form-data';
    
    /**
	* Map short name for a mime type
	* to a full proper mime type
	*/
    public static $mimes = array(
        'json'		=> self::JSON,
        'jsonp'		=> self::JS,
        'js'		=> self::JS,
        'javascript'=> self::JS,
        'xml'		=> self::XML,
        'html'		=> self::HTML,
        'xhtml'		=> self::XHTML,
        'csv'		=> self::CSV,
        'plain'		=> self::PLAIN,
        'text'		=> self::PLAIN,
        'form'		=> self::FORM,
        'upload'	=> self::UPLOAD,
    );

    /**
	* Get the full Mime Type name from a "short name".
	*
	* @param string common name for mime type (e.g. json)
	* @return string full mime type (e.g. application/json)
	*/
    public static function type($name){
        return array_key_exists($name, self::$mimes) ? self::$mimes[$name] : null;
    }

    /**
	* @param string $name
	* @return bool
	*/
    public static function isValid($name){
        return array_key_exists($name, self::$mimes);
    }

}