<?php
namespace Core\Http;

use Core\Helpers\Config;
class Cors
{
    private array $ALLOWED_METHODS = [];
    private array $ALLOWED_HEADERS = ['Content-Type', 'Authorization'];
    private array $ALLOWED_ORIGINS = [];
	private bool $IS_CORS_ENABLED = true;
    private bool $ALLOW_CREDENTIALS = false;
    private int $MAX_AGE = 86400;

    public function __construct()
    {

		$http_config = Config::get('http') ?? [];
		$cors_config = $http_config['cors'] ?? [];
		if($cors_config == []) { 
			$this->IS_CORS_ENABLED = false;
			return;
		}

        $this->IS_CORS_ENABLED = $cors_config['enabled'] ?? false;
        $this->ALLOWED_METHODS = $http_config['allowed_methods'] ?? [];
        $this->ALLOWED_HEADERS = $http_config['allowed_headers'] ?? [];
        $this->ALLOWED_ORIGINS = $cors_config['allowed_origins'] ?? [];
        $this->ALLOW_CREDENTIALS = $cors_config['allow_credentials'] ?? false;
    }

    public function handleCors()
    {
        if (!$this->IS_CORS_ENABLED) {
            return;
        }

        if (isset($_SERVER['HTTP_ORIGIN']) && !$this->isOriginAllowed()) {
            header('HTTP/1.1 403 Forbidden');
            exit('CORS request rejected');
        }
    }

    private function isOriginAllowed(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'];
        $allowsAnyOrigin = in_array('*', $this->ALLOWED_ORIGINS, true);

        if (!$allowsAnyOrigin && !in_array($origin, $this->ALLOWED_ORIGINS, true)) {
            return false;
        }

        header('Access-Control-Allow-Origin: ' . ($allowsAnyOrigin ? '*' : $origin));
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->ALLOWED_METHODS));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->ALLOWED_HEADERS));
        header("Access-Control-Max-Age: $this->MAX_AGE");

        if ($this->ALLOW_CREDENTIALS && !$allowsAnyOrigin) {
            header('Access-Control-Allow-Credentials: true');
        }

        if (!$allowsAnyOrigin) {
            header('Vary: Origin');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }

        return true;
    }
}
