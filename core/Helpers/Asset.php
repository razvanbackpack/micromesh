<?php
namespace Core\Helpers;

class Asset 
{
    public static string $BUILD_PATH = BASEDIR.'\\public\\';

    public static function get(string $resource_path = "")
    {
        //TODO: make an array with all available config files for better checks and to avoid warnings
        return self::getAssetPath($resource_path);
    }



    private static function getAssetPath($resource_path)
    {
        // dd($manifest);

        $resource_path_src = self::$BUILD_PATH.'..//assets//'.$resource_path;
        $final_include_path = $resource_path_src;
       
        return $resource_path_src;
    }
}