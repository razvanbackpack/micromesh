<?php
namespace Core\Helpers;

class Asset 
{
    public static string $ASSETS_BASE_PATH = BASEDIR.'\\assets\\';

    public static function get(string $resource_path = "")
    {
        //TODO: make an array with all available config files for better checks and to avoid warnings
        $manifest = self::getManifest();
        return self::getAssetPath($resource_path, $manifest);
    }

    private static function getManifest(): array
    {
        $manifest = [];

        if(file_exists(self::$ASSETS_BASE_PATH))
            $content = file_get_contents(self::$ASSETS_BASE_PATH);
        else $content = false;

        if(!$content) return $manifest;
        $manifest = json_decode($content, true);

        return $manifest;
    }

    private static function getAssetPath($resource_path, $manifest)
    {
        $resource_path_src = self::$ASSETS_BASE_PATH.$resource_path;
        $final_include_path = $resource_path_src;
        if($manifest != [])
        {
            $manifestResource = $manifest[$resource_path_src] ?? [];
            if($manifestResource != [])
            {
                $final_include_path = $_ENV['APP_URL'] .'/'. $manifestResource['file'];
                return $final_include_path;
            }
        }  else {
            $resource_path = $_ENV['APP_URL'] .'/public/assets/'.$resource_path;
        }
        
        return $resource_path;
    }
}