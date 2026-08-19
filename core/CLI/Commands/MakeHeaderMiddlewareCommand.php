<?php

namespace Core\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeHeaderMiddlewareCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:header-middleware')
            ->setDescription('Create application middleware for a required request header')
            ->addArgument('name', InputArgument::REQUIRED, 'Middleware class name')
            ->addOption(
                'header',
                null,
                InputOption::VALUE_REQUIRED,
                'Header name to require',
                'X-Request-ID'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $this->normalizeClassName($input->getArgument('name'));
        $header = trim($input->getOption('header'));

        if ($name === '' || $header === '') {
            $io->error('The middleware name and header name are required.');
            return Command::INVALID;
        }

        $projectRoot = dirname(__DIR__, 3);
        $directory = $projectRoot . DIRECTORY_SEPARATOR . 'app/Http/Middleware';
        $file = $directory . DIRECTORY_SEPARATOR . $name . '.php';

        if (file_exists($file)) {
            $io->error("Middleware already exists: {$file}");
            return Command::FAILURE;
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $io->error("Unable to create directory: {$directory}");
            return Command::FAILURE;
        }

        $contents = $this->buildMiddleware($name, $header);

        if (file_put_contents($file, $contents) === false) {
            $io->error("Unable to write middleware: {$file}");
            return Command::FAILURE;
        }

        $io->success("Created {$file}");
        $io->text([
            'Use it globally in routes/web.php or routes/api.php:',
            "use App\\Http\\Middleware\\{$name};",
            "Route::addGlobalMiddleware(new {$name}());",
            '',
            'Or use it on one route:',
            "Route::get('/path', \$handler, [new {$name}()]);",
        ]);

        return Command::SUCCESS;
    }

    private function normalizeClassName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?? '';
        $name = ucfirst($name);

        if ($name === '' || !preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name)) {
            return '';
        }

        return str_ends_with($name, 'Middleware') ? $name : $name . 'Middleware';
    }

    private function buildMiddleware(string $name, string $header): string
    {
        $contents = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Core\Helpers\Request;
use Core\Http\Route;

class __CLASS_NAME__
{
    public function __invoke(): ?string
    {
        $headers = array_change_key_case(
            Request::$REQUEST_DATA['headers'] ?? [],
            CASE_LOWER
        );

        if (!isset($headers[strtolower(__HEADER_NAME__)])) {
            return Route::error(400, 'Missing header: ' . __HEADER_NAME__);
        }

        return null;
    }
}
PHP;

        return str_replace(
            ['__CLASS_NAME__', '__HEADER_NAME__'],
            [$name, var_export($header, true)],
            $contents
        );
    }
}
