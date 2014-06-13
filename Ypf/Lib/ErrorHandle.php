<?php

namespace Ypf\Lib;

class ErrorHandle {

	public  function Error($code, $message, $file, $line) {
        ob_end_clean();       
        $errstr = ' ['.$code.']' . $message.' in '.$file.' on line '.$line;
        $trace          = debug_backtrace();
        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';

        $files           =   file($file);

        $error['message']   = $message;
        $error['type']      = $code;
        $error['detail']    =   ($line-2).': '.$files[$line-3];
        $error['detail']   .=   ($line-1).': '.$files[$line-2];
        $error['detail']   .=   '<font color="#FF6600" >'.($line).': <strong>'.$files[$line-1].'</strong></font>';
        $error['detail']   .=   ($line+1).': '.$files[$line];
        $error['detail']   .=   ($line+2).': '.$files[$line+1];
        $error['class']     =   $class;
        $error['function']  =   $function;
        $error['file']      = $file;
        $error['line']      = $line;
        include __APP__ . "/error_tpl.php";
	}
	
	public  function Exception($exception) {
        $trace = $exception->getTrace();
        $traceline = "#%s %s(%s): %s(%s) %s( %s )";

        $message = $exception->getMessage();

        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';
        $file     =   $trace[0]['file'];
        $line     =   $trace[0]['line'];
        $files           =   file($file);
        $traceInfo      =   '';
        $time = date('y-m-d H:i:m');
        foreach($trace as $t) {
            if(isset($t['file']) && isset($t['line']))
            $traceInfo .= $t['file'].' ('.$t['line'].') ';
            if(isset($t['class']) && isset($t['line']))
            $traceInfo .= $t['class'].$t['type'].$t['function'].'(';
            $traceInfo .= print_r( self::object2array($t['args']) , true);
            $traceInfo .=")<br />";
        }
        $error['message']   = $message;
        $error['type']      = get_class($exception);
        $error['detail']    =   ($line-2).': '.$files[$line-3];
        $error['detail']   .=   ($line-1).': '.$files[$line-2];
        $error['detail']   .=   '<font color="#FF6600" >'.($line).': <strong>'.$files[$line-1].'</strong></font>';
        $error['detail']   .=   ($line+1).': '.$files[$line];
        $error['detail']   .=   ($line+2).': '.$files[$line+1];
        $error['class']     =   $class;
        $error['function']  =   $function;
        $error['file']      = $file;
        $error['line']      = $line;
        $error['trace']     = $traceInfo;
        include __APP__ . "/error_tpl.php";
	}
	
	//register_shutdown_function(array( new \Ypf\Lib\ErrHandler(), "Shutdown"))
	public function Shutdown() {
		if ($error = error_get_last()) {
			$this->Error($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}	
    public static function getContextFileLineError($filePath, $line, $includeLineNumbers = true) {
        $fileContent = file($filePath);
        $fileContent = array_slice($fileContent, ($line - 3), 6);
        $fileContent[2] = str_replace("\n", ' ', $fileContent[2]);
        $fileContent[2] .= " // <<---- Hey, wake up!, the problem is here!!!\n";

        $k = $line - 3;
        foreach ($fileContent as $key => $lineContent) {
            $fileContent[$key] = str_replace("\n", ' ', $fileContent[$key]);
            if ($includeLineNumbers) {
                $k++;
                if ($k == $line ) {
                    $fileContent[$key] = sprintf("[%s]\t%s", $k, $lineContent);
                } else {
                    $fileContent[$key] = sprintf("%s\t%s", $k, $lineContent);
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
