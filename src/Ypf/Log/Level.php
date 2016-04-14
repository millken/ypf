<?php
namespace Ypf\Log;
class Level {
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    const ALL       = 'all';
    
    protected static $loggerLevel = array(
    	self::EMERGENCY,
    	self::ALERT,
    	self::CRITICAL,
    	self::ERROR,
    	self::WARNING,
    	self::NOTICE,
    	self::INFO,
    	self::DEBUG
    	);

    public static function addLevel($name) {
    	if(!in_array($name, self::$loggerLevel)) {
    		self::$loggerLevel[] = $name;
    	}
    }

    public static function getLevels() {
    	return self::$loggerLevel;
    }
}