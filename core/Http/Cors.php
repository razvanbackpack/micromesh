<?php
namespace Core\Http;

use Core\Helpers\Config;
class Cors
{
    private array $ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    private array $ALLOWED_HEADERS = ['Content-Type', 'Authorization'];
    private array $ALLOWED_ORIGINS = [];
	private bool $IS_CORS_ENABLED = true;
    private int $MAX_AGE = 86400;

    public function __construct()
    {

		$http_config = Config::get('http') ?? [];
		$cors_config = $http_config['cors'] ?? [];
		if($cors_config == []) { 
			$this->IS_CORS_ENABLED = false;
			return;
		}

		$this->IS_CORS_ENABLED = $cors_config['enabled'];
		$this->ALLOWED_METHODS = $http_config['allowed_headers'];
		$this->ALLOWED_ORIGINS = $cors_config['allowed_origins'];
    }

    public function handleCors()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && !$this->isOriginAllowed() ) {
            header('HTTP/1.1 403 Forbidden');
            exit('CORS request rejected');
        }
    }

    private function isOriginAllowed()
    {
        // Validate the origin
        $origin = $_SERVER['HTTP_ORIGIN'];
        if (!in_array($origin, $this->ALLOWED_ORIGINS) && !in_array('*', $this->ALLOWED_ORIGINS)) {
            return;
        }

        // Set CORS headers
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->ALLOWED_METHODS));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->ALLOWED_HEADERS));
        header("Access-Control-Max-Age: $this->MAX_AGE");
        header('Access-Control-Allow-Credentials: true');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 204 No Content');
            exit();
        }
    }
}
