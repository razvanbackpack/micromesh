<?php
namespace Core\Helpers;

class Resource 
{
    public static string $RESOURCES_BASE_PATH = BASEDIR.'\\resources\\';
    public static string $BUILD_PATH = BASEDIR.'\\public\\';
    public static string $MANIFEST_PATH_FOLDER = BASEDIR.'\\public\\.vite\\manifest.json';
    public static ?int $PORT = null;

    public static function get(string $resource_path = "")
    {
        //TODO: make an array with all available config files for better checks and to avoid warnings
        $manifest = self::getManifest();
        return self::getResourcePath($resource_path, $manifest);
    }

    private static function getManifest(): array
    {
        $manifest = [];

        if(file_exists(self::$MANIFEST_PATH_FOLDER))
            $content = file_get_contents(self::$MANIFEST_PATH_FOLDER);
        else $content = false;

        if(!$content) return $manifest;
        $manifest = json_decode($content, true);

        return $manifest;
    }

    private static function getResourcePath($resource_path, $manifest)
    {
        // dd($manifest);

        $resource_path_src = '../resources/'.$resource_path;
        $final_include_path = $resource_path_src;
        if($manifest != [])
        {
            $manifestResource = $manifest[$resource_path_src] ?? [];
            if($manifestResource != [])
            {
                $final_include_path = $_ENV['APP_URL'].'/'. $manifestResource['file'];
                return $final_include_path;
            }
        } 
        
        return $resource_path_src;
    }
}