<?php
namespace Ypf\Log\Filter;

use Ypf\Exception;

class File extends Filter {
	private $logFilePath;
	private $fileHandle;
	private $logLineCount = 0;

    protected $options = array (
        'flushFrequency' => false,
    );

	public function __construct(string $filepath, $option=[]) {
       $this->logFilePath = $filepath;
       $this->options += $option;
       $this->logLineCount = 0;
        if(file_exists($this->logFilePath)) {
            if(is_writable($this->logFilePath)) {
                $fp = fopen($this->logFilePath , 'r');  
                if($fp){  
                    while(stream_get_line($fp,8192,"\n")){  
                        $this->logLineCount++;
                    }
                    fclose($fp);
                }             
            }else {
                throw new Exception("The file '{$this->logFilePath}' could not be written to. Check that appropriate permissions have been set.");
            }
        }
        $this->setFileHandle('a');
       	if ( ! $this->fileHandle) {
            throw new Exception("The file '{$this->logFilePath}' could not be opened. Check permissions.");
        }
	}

	public function writer($level, $message) {
		if (!is_resource($this->fileHandle)) {
            $this->setFileHandle('a');
        }
        if (null !== $this->fileHandle) {
            if (fwrite($this->fileHandle, $message. PHP_EOL) === false) {
                throw new Exception('The file could not be written to. Check that appropriate permissions have been set.');
            } else {
                $this->logLineCount++;
                if ($this->options['flushFrequency'] && $this->logLineCount % $this->options['flushFrequency'] === 0) {
                    fflush($this->fileHandle);
                }
            }
        }
	}

    public function setFileHandle($writeMode) {
        $this->fileHandle = fopen($this->logFilePath, $writeMode);
    }

    public function __destruct() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }
}

