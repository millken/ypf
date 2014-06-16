<?php


namespace Ypf\Lib;

if(!defined('__ERROR_HANDLE_LEVEL__'))  define('__ERROR_HANDLE_LEVEL__', E_ALL ^ E_WARNING ^ E_NOTICE);

class ErrorHandle {

	public  function Error($code, $message, $file, $line) {

        if ( ($code & __ERROR_HANDLE_LEVEL__) !== $code) return;

        $errstr = ' ['.$code.']' . $message.' in '.$file.' on line '.$line;
        $trace          = debug_backtrace();
        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';

        $error['message']   = $message;
        $error['type']      = $code;

        $error['file']      = $file;
        $error['line']      = $line;
        $fileContent = self::getContextFileLineError($error['file'], $error['line']);
        $fileContent = highlight_string("<?php \n". $fileContent . "", true);
        $error['detail'] = $fileContent;        
        ob_start();
        debug_print_backtrace();
        $error['trace'] = ob_get_clean();   
        ob_end_clean();     
        include __APP__ . "/error_tpl.php";
        //exit;
	}
	
	public  function Exception($exception) {
        $trace = $exception->getTrace();
        $traceline = "#%s %s(%s): %s(%s) %s( %s )";

        $message = $exception->getMessage();

        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';
        if('E'==$trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        }else{
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
        }

        ob_start();
        debug_print_backtrace();
        $error['trace'] = ob_get_clean();
        ob_end_clean();   
        $error['message']   = $message;
        $error['type']      = get_class($exception);

        $fileContent = self::getContextFileLineError($error['file'], $error['line']);
        $fileContent = highlight_string("<?php \n". $fileContent . "...*/", true);
        $error['detail'] = $fileContent;

        include __APP__ . "/error_tpl.php";
        //exit;
	}
	
	//register_shutdown_function(array( new \Ypf\Lib\ErrHandler(), "Shutdown"))
	public function Shutdown() {
		if ($error = error_get_last()) {
            if ( ($error['type'] & __ERROR_HANDLE_LEVEL__) !== $error['type']) return;
            print_r($error);
			$this->Error($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}	
    public static function getContextFileLineError($filePath, $line, $includeLineNumbers = true) {
        if(!is_file($filePath)) return "";
        $fileContent = file($filePath);
        $fileContent[$line-1] = rtrim($fileContent[$line-1]) . " /* Hey, phper!, the problem is here, please fix!!!*/\n";
        $fileContent = array_slice($fileContent, ($line - 5), 10);
        $k = $line - 5;
        foreach ($fileContent as $key => $lineContent) {
            $fileContent[$key] = str_replace("\n", ' ', $fileContent[$key]);
            if ($includeLineNumbers) {
                $k++;
                if ($k == $line ) {
                    $fileContent[$key] = sprintf("%s:\t%s", $k, $lineContent);
                } else {
                    $fileContent[$key] = sprintf("%s:\t%s", $k, $lineContent);
                }
            } else {
                $fileContent[$key] = sprintf("%s", $lineContent);
            }
        }

        return implode("", $fileContent);
    }	
    /**
     * from https://github.com/chernjie/tracer/edit/master/tracer.php
     * @param stdClass $object
     * @param string $property
     * @return array
     * @todo not all objects are the same even if they are instantiated from the same class
     */
    private static function object2array($object, $_classes = array(), $_level = 0)
    {
        if (! is_object($object)) return $object;
        // $array = preg_replace('/\w+::__set_state/', '', var_export($object, true));
        // eval('$array = ' . $array . ';');
        $array = array();
        $class = get_class($object);
        array_push($_classes, $class);
        $reflected = new ReflectionClass($object);
        $props = $reflected->getProperties();
        foreach ($props as $prop)
        {
            $prop->setAccessible(true);
            $name = $prop->getName();
            $value = $prop->getValue($object);
            if (is_object($value))
            {
                $name .= ':' . get_class($value);
                $value = in_array(get_class($value), $_classes) || $_level > 10
                    ? get_class($value)
                    : self::object2array($value, $_classes, $_level + 1);
            }
            switch (true)
            {
                case $prop->isPrivate():
                    $name .= ':private';
                    break;
                case $prop->isProtected():
                    $name .= ':protected';
                    break;
                case $prop->isPublic():
                    break;
                case $prop->isStatic():
                    $name .= ':static';
                    break;
                default:
                    $name .= '?';
                    break;
            }
            $array[$name] = $value;
        }
        return $array;
    }

}
