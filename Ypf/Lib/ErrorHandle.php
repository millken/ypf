<?php

namespace Ypf\Lib;

class ErrorHandle {

	public  function Error($code, $message, $file, $line) {
        ob_end_clean();       
        $errstr = ' ['.$code.']' . $message.' in '.$file.' on line '.$line;
		$this->halt($errstr);
	}

    private function halt($error) {
        if (!is_array($error)) {
            $traceline = "#%s %s(%s): %s(%s) %s( %s )";
            $trace          = debug_backtrace();
            $e['message']   = $error;
            $e['file']      = $trace[0]['file'];
            $e['class']     = isset($trace[0]['class'])?$trace[0]['class']:'';
            $e['function']  = isset($trace[0]['function'])?$trace[0]['function']:'';
            $e['line']      = $trace[0]['line'];
            $traceInfo      = '';
            $time = date('y-m-d H:i:m');
            $showParameters = 1;
            $cnt = 0;
            foreach ($trace as $key => $stackPoint) {
                $cnt++;
                if ($showParameters == 1) {
                    $ret = '';
                    $keys = array_keys($stackPoint['args']);
                    $end = end($keys);
                    foreach($stackPoint['args'] as $key => $item) {
                        if (is_object($item)) {
                            $ret .= gettype($item);
                        } elseif (is_array($item)) {
                            
                                
                            try {
                                $ret .= 'array("';
                                $ret .= implode('", "', $item);
                                $ret .= '")';
                            } catch (Exception $e) {
                                die('ups!');
                            }
                        } elseif (is_numeric($item)) {
                            $ret .= $item;
                        } else {
                            $ret .= "\"$item\"";
                        }
                        if ($key != $end) {
                            $ret .= ', ';
                        }
                    }
                }
                
                $result[] = sprintf(
                    $traceline,
                    $cnt,
                    ((isset($stackPoint['file']) ? $stackPoint['file'] : '--' )),
                    ((isset($stackPoint['line']) ? $stackPoint['line'] : '--' )),
                    ((isset($stackPoint['class']) ? $stackPoint['class'] : '' )),
                    ((isset($stackPoint['type']) ? $stackPoint['type'] : '' )),
                    ((isset($stackPoint['function']) ? $stackPoint['function'] : '--')),
                    $ret
                );
            }
            $result = array_reverse($result);        
            $traceInfo = implode("\n", $result);
            $e['trace']     = $traceInfo;
        } else {
            $e              = $error;
        }

        include __APP__ . "/error_tpl.php";
        exit;
    }
	
	public  function Exception($exception) {
        $traceline = "#%s %s(%s): %s(%s) %s( %s )";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s";
        $message = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $trace = $exception->getTrace();
        //array_shift($trace);
        $class    =   isset($trace[0]['class'])?$trace[0]['class']:'';
        $function =   isset($trace[0]['function'])?$trace[0]['function']:'';
        $file     =   $trace[0]['file'];
        $line     =   $trace[0]['line'];
        $fileContent           =   file($file);
        $traceInfo      =   '';
        $time = date('y-m-d H:i:m');
        $result = array();
        $cnt=0;
        $showParameters = 1;
        foreach ($trace as $key => $stackPoint) {
            $cnt++;
            if ($showParameters == 1) {
                $ret = '';
                $keys = array_keys($stackPoint['args']);
                $end = end($keys);
                foreach($stackPoint['args'] as $key => $item) {
                    if (is_object($item)) {
                        $ret .= gettype($item);
                    } elseif (is_array($item)) {
                        
                            
                        try {
                            $ret .= 'array("';
                            $ret .= implode('", "', $item);
                            $ret .= '")';
                        } catch (Exception $e) {
                            die('ups!');
                        }
                    } elseif (is_numeric($item)) {
                        $ret .= $item;
                    } else {
                        $ret .= "\"$item\"";
                    }
                    if ($key != $end) {
                        $ret .= ', ';
                    }
                }
            }
            
            $result[] = sprintf(
                $traceline,
                $cnt,
                ((isset($stackPoint['file']) ? $stackPoint['file'] : '--' )),
                ((isset($stackPoint['line']) ? $stackPoint['line'] : '--' )),
                ((isset($stackPoint['class']) ? $stackPoint['class'] : '' )),
                ((isset($stackPoint['type']) ? $stackPoint['type'] : '' )),
                ((isset($stackPoint['function']) ? $stackPoint['function'] : '--')),
                $ret
            );
        }
        $result = array_reverse($result);        
        $traceInfo = implode("\n", $result);
        $error = array();
        $error['message']   = $message;
        //$error['type']      = $type;

        $error['class']     =   $class;
        $error['function']  =   $function;
        $error['file']      = $file;
        $error['line']      = $line;
        $error['trace']     = $traceInfo;

        $this->halt($error);
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

}
