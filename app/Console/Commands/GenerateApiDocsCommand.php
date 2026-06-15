<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateApiDocsCommand extends Command
{
    protected $signature = 'docs:generate
                            {--fresh : Remove previously generated spec files before regenerating}';

    protected $description = 'Generate the OpenAPI specification from #[OA\\...] attributes across app/OpenApi and all modules';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $docsPath = config('l5-swagger.defaults.paths.docs', storage_path('api-docs'));

            foreach (glob($docsPath . '/api-docs.*') ?: [] as $file) {
                unlink($file);
                $this->components->info(sprintf('Removed stale spec: %s', basename($file)));
            }
        }

        $diagnostics = [];

        set_error_handler(
            static function (int $severity, string $message) use (&$diagnostics): bool {
                $diagnostics[] = $message;

                return true;
            },
            E_USER_NOTICE | E_USER_WARNING,
        );

        try {
            $exitCode = Artisan::call('l5-swagger:generate', [], $this->output);
        } finally {
            restore_error_handler();
        }

        foreach ($diagnostics as $diagnostic) {
            $this->components->warn(sprintf('swagger-php: %s', $diagnostic));
        }

        if ($exitCode !== self::SUCCESS) {
            $this->components->error('OpenAPI generation failed; see output above.');

            return $exitCode;
        }

        $specPath = config('l5-swagger.defaults.paths.docs', storage_path('api-docs'))
            . '/'
            . config('l5-swagger.documentations.default.paths.docs_json', 'api-docs.json');

        $spec = json_decode((string) file_get_contents($specPath), true);

        if (! is_array($spec) || empty($spec['paths'])) {
            $this->components->error('Generated spec is empty or unreadable.');

            return self::FAILURE;
        }

        $operations = 0;

        foreach ($spec['paths'] as $methods) {
            $operations += count($methods);
        }

        $this->components->info(sprintf(
            'OpenAPI spec generated: %d paths, %d operations → %s',
            count($spec['paths']),
            $operations,
            $specPath,
        ));

        $this->components->info(sprintf(
            'Swagger UI: %s/%s',
            rtrim(config('app.url'), '/'),
            config('l5-swagger.documentations.default.routes.api', 'api/documentation'),
        ));

        return self::SUCCESS;
    }
}
