<?php


namespace Ypf\Lib;

if(!defined('__ERROR_HANDLE_LEVEL__'))  define('__ERROR_HANDLE_LEVEL__', E_ALL ^ E_WARNING ^ E_NOTICE);

class ErrorHandle {

	public  function Error($type, $message, $file, $line) {

        if ( ($type & __ERROR_HANDLE_LEVEL__) !== $type) return;

        $errstr = ' ['.$type.']' . $message.' in '.$file.' on line '.$line;
        $trace          = debug_backtrace();
        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';

        $error['message']   = $message;
        $error['type']      = self::FriendlyErrorType($type);

        $error['file']      = $file;
        $error['line']      = $line;
        $fileContent = self::getContextFileLineError($error['file'], $error['line']);
        $fileContent = highlight_string("<?php \n". $fileContent . "...*/", true);
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
        $error['message']   = $exception->getMessage();
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

    public static function FriendlyErrorType($type) {
        switch($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_CORE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_CORE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    } 
}
