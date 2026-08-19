<?php
namespace Core\Helpers;

use Exception;
use Throwable;

class Config 
{
    public static string $CONFIG_FILE_PATH = BASEDIR . DIRECTORY_SEPARATOR . 'config';

    public static function get(string $config_path = ""): array|string|null
    {
        try {
            if($config_path == "") return null;
            
            $config_path_parts = explode('.', $config_path);

            if(!count($config_path_parts)) return null;

            // Load the config file
            $config_file = $config_path_parts[0];
            $config_file_data = include(self::$CONFIG_FILE_PATH . DIRECTORY_SEPARATOR . $config_file . '.php');
            
            if($config_file_data === null || !$config_file_data) return "";

            // Traverse nested keys safely without eval()
            return self::getNestedValue($config_file_data, array_slice($config_path_parts, 1));
        } catch (Throwable $e)
        {
            dd($e);
            exit();
        }
    }

    /**
     * Safely traverse nested array using key path
     * Example: getNestedValue($config, ['http', 'cors', 'enabled'])
     * 
     * @param array $data The array to traverse
     * @param array $keys The key path to follow
     * @return mixed The value at the key path, or null if not found
     */
    private static function getNestedValue(array $data, array $keys): mixed
    {
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }
}