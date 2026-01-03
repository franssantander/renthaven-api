<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Example: php artisan make:module-controller LoginController Authentication
     * Example: php artisan make:module-controller LoginController Authentication --invokable
     */
    protected $signature = 'make:module-controller 
                            {name : The name of the controller class} 
                            {module : The module where this controller belongs}
                            {--i|invokable : Generate a single-method invokable controller}';

    protected $description = 'Create a new Controller class inside a Module';

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

        // 1. Define the path: app/Modules/{Module}/Http/Controllers/{Name}.php
        $path = app_path("Modules/{$module}/Http/Controllers/API/v1");
        $filePath = "{$path}/{$name}.php";

        // 2. Ensure the directory exists
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // 3. Check if file already exists
        if ($this->files->exists($filePath)) {
            $this->error("Controller {$name} already exists in module {$module}!");
            return;
        }

        // 4. Create the file content
        $content = $this->getStub($name, $module, $this->option('invokable'));

        $this->files->put($filePath, $content);

        $this->info("Controller [{$name}] created successfully in Module [{$module}].");
    }

    protected function getStub($name, $module, $isInvokable)
    {
        // Adjust this if your base controller is located elsewhere
        $baseControllerImport = 'use App\Http\Controllers\Controller;';
        
        if ($isInvokable) {
            return <<<EOT
<?php

namespace App\Modules\\{$module}\Http\Controllers;

{$baseControllerImport}
use Illuminate\Http\Request;

class {$name} extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request \$request)
    {
        //
    }
}
EOT;
        }

        // Standard Resource Controller Stub
        return <<<EOT
<?php

namespace App\Modules\\{$module}\Http\Controllers;

{$baseControllerImport}
use Illuminate\Http\Request;

class {$name} extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request \$request)
    {
        //
    }

    public function show(\$id)
    {
        //
    }

    public function update(Request \$request, \$id)
    {
        //
    }

    public function destroy(\$id)
    {
        //
    }
}
EOT;
    }
}