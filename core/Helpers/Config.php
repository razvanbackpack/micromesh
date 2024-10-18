<?php
namespace Core\Helpers;

use Exception;
use Throwable;

class Config 
{
    public static string $CONFIG_FILE_PATH = BASEDIR.'\\config';

    public static function get(string $config_path = ""): array
    {
        // TODO: Exception handling
        try {
            if($config_path == "") return null;
            
            $config_path_parts = explode('.', $config_path);

            if(!count($config_path_parts)) return null;

            $config_file = $config_path_parts[0];
            $config_indexes = "";
            unset($config_path_parts[0]);

            array_walk($config_path_parts, function ($part, $key) use (&$config_indexes) {
                if($key == 0) return;
                $config_indexes .= "['".$part."']";
            });

        
            $config_file_data = include(self::$CONFIG_FILE_PATH.'\\'.$config_file.'.php');
            if($config_file_data === null || !$config_file_data) return "";
            $keys = [];
            $keys = str_replace(array('[', ']'), array("['", "']"), $keys); // wrapping with "'" (single qoutes)
            $result = [];
            
            eval('$result = $config_file_data' . $config_indexes . ';');
            return $result;
        } catch (Throwable $e)
        {
            dd($e);
            exit();
        }

    }
}