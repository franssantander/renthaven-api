<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeActionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example usage: php artisan make:action LoginAction Authentication
     */
    protected $signature = 'make:action {name : The name of the action class} {module : The module where this action belongs}';

    protected $description = 'Create a new Action class inside a Module';

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

        // 1. Define the path: app/Modules/{Module}/Actions/{Name}.php
        $path = app_path("Modules/{$module}/Actions");
        $filePath = "{$path}/{$name}.php";

        // 2. Ensure the directory exists
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // 3. Check if file already exists
        if ($this->files->exists($filePath)) {
            $this->error("Action {$name} already exists in module {$module}!");

            return;
        }

        // 4. Create the file content
        $content = $this->getStub($name, $module);

        $this->files->put($filePath, $content);

        $this->info("Action [{$name}] created successfully in Module [{$module}].");
    }

    protected function getStub($name, $module)
    {
        return <<<EOT
<?php

namespace App\Modules\\{$module}\Actions;

class {$name}
{
    public function __invoke()
    {
        //
    }
}
EOT;
    }
}
