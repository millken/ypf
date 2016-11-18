<?php
namespace Ypf\Log\Filter;

class File extends Filter {
	private $logFilePath;
	private $fileHandle;
	private $logLineCount = 0;

    protected $options = array (
        'flushFrequency' => false,
    );

	public function __construct($filepath, $option=[]) {
       $this->setFile($filepath);
        if(file_exists($this->logFilePath) && !is_writable($this->logFilePath)) {
            throw new Exception('The file could not be written to. Check that appropriate permissions have been set.');
        }
        $this->setFileHandle('a');
       	if ( ! $this->fileHandle) {
            throw new Exception('The file could not be opened. Check permissions.');
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

	public function setFile($filepath) {
		$this->logFilePath = $filepath;
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
