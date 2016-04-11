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
        //ob_start();
        //debug_print_backtrace();
        //$error['trace'] = ob_get_clean();   
        //ob_end_clean();
        ob_clean();   
        include 'ErrorHandle.tpl';
        exit;
	}
	
	public  function Exception($exception) {
        $trace = $exception->getTrace();
        $trace = array_reverse($trace);

        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';
        $error['file'] = $trace[0]['file'];
        $error['line'] = $trace[0]['line'];
        if(empty($error['file']) or empty($error['line'])) {
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
        }

        $error['trace'] = "";
        foreach ((array)$trace as $k => $v) {
            array_walk($v['args'], function (&$item, $key) { 
                $item = str_replace("\n", "", var_export($item, true)); 
            });
			if(isset($v['file']))
            $error['trace'] .= '#' . $k . ' ' . $v['file'] . '(' . $v['line'] . '): ' .
             (isset($v['class']) ? $v['class'] . '->' : '') . $v['function'] . '(' . implode(', ', $v['args']) . ')' . "\n"; 
        }

        $error['message']   = $exception->getMessage();
        $error['type']      = get_class($exception);

        $fileContent = self::getContextFileLineError($error['file'], $error['line']);
        $fileContent = highlight_string("<?php \n". $fileContent . "...*/", true);
        $error['detail'] = $fileContent;
        ob_clean(); 
        include 'ErrorHandle.tpl';
        exit;
	}
	
	//register_shutdown_function(array( new \Ypf\Lib\ErrHandler(), "Shutdown"))
	public function Shutdown() {
		if ($error = error_get_last()) {
            if ( ($error['type'] & __ERROR_HANDLE_LEVEL__) !== $error['type']) return;
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
