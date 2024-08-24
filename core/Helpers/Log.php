<?php
namespace Core\Helpers;

use Core\Helpers\Config;
use RuntimeException;
class Log
{
	private const LOG_DEBUG = 0;
	private const LOG_INFO = 1;
	private const LOG_WARNING = 2;
	private const LOG_ERROR = 3;
	private const LOG_CRITICAL = 4;
	private const LOG_FILE = 'micromesh';

	private static $LOG_LEVELS = [
		0   => 'DEBUG',
		1    => 'INFO',
		2 => 'WARNING',
		3   => 'ERROR',
		4 => 'CRITICAL'
	];
	
	public static function Log($message = '', int $level = self::LOG_DEBUG, $date = '')
	{
		$log_config = Config::get('app.log');
		$separate_logging = $log_config['separate_files'];
		$log_enabled = $log_config['enable'];

		if(!$log_enabled) return;
		if (!in_array($level, self::$LOG_LEVELS)) {
            $level = 0;
        }

		$level = self::$LOG_LEVELS[$level];
		
		$log_file_name = "LOG_GENERAL";
		if($separate_logging) $log_file_name = "LOG_".$level;
		$log_file_path = $log_config['path'].'/'.$log_file_name.'.log';
		self::ensureLogFileExists($log_file_path);
		if(is_null($message)) {
			$message = "Ea aliquip in esse ea occaecat velit exercitation laborum proident deserunt.";
		}

       
        $timestamp = $date;
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
		if (file_put_contents($log_file_path, $logMessage, FILE_APPEND) === false) {
            throw new RuntimeException("Failed to write to log file: {$log_file_path}");
        }
	}

	public static function Debug($message)
    {
        self::Log($message, self::LOG_DEBUG, date('Y-m-d H:i:s'));
    }

    public static function Info($message)
    {
        self::Log($message, self::LOG_INFO, date('Y-m-d H:i:s'));
    }

    public static function Warning($message)
    {
        self::Log($message, self::LOG_WARNING, date('Y-m-d H:i:s'));
    }

    public static function Error($message)
    {
        self::Log($message, self::LOG_ERROR, date('Y-m-d H:i:s'));
    }

    public static function Critical($message)
    {
        self::Log($message, self::LOG_CRITICAL, date('Y-m-d H:i:s'));
    }

	private static function ensureLogFileExists($log_file)
    {
        $dir = dirname($log_file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new RuntimeException("Failed to create log directory: $dir");
            }
        }

        if (!file_exists($log_file)) {
            if (!touch($log_file)) {
                throw new RuntimeException("Failed to create log file: {$log_file}");
            }
            chmod($log_file, 0666);
        }

        if (!is_writable($log_file)) {
            throw new RuntimeException("Log file is not writable: {$log_file}");
        }
    }
}
