<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan make:module-data LoginData Authentication
     */
    protected $signature = 'make:module-data 
                            {name : The name of the Data class (e.g. LoginData)} 
                            {module : The module where this class belongs (e.g. Authentication)}';

    protected $description = 'Create a new Spatie Data class inside a Module';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $module = Str::studly($this->argument('module'));

        // 1. Define the path: app/Modules/{Module}/Data/{Name}.php
        // Note: You can change 'Data' to 'DTO' here if you prefer that folder name
        $folderName = 'Data'; 
        $path = app_path("Modules/{$module}/{$folderName}");
        $filePath = "{$path}/{$name}.php";

        // 2. Ensure the directory exists
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // 3. Check if file already exists
        if ($this->files->exists($filePath)) {
            $this->error("Data class {$name} already exists in module {$module}!");
            return;
        }

        // 4. Create the file content
        $content = $this->getStub($name, $module, $folderName);

        $this->files->put($filePath, $content);

        $this->info("Data class [{$name}] created successfully in [{$module}/{$folderName}].");
    }

    protected function getStub($name, $module, $folderName)
    {
        $namespace = "App\\Modules\\{$module}\\{$folderName}";

        return <<<EOT
<?php

namespace {$namespace};

use Spatie\LaravelData\Data;

class {$name} extends Data
{
    public function __construct(
        // public string \$username,
        // public string \$password,
    ) {}
}
EOT;
    }
}