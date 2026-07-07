<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('make:action {name : The name of the action class}')]
#[Description('Create a new service action class')]
class MakeActionCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');

        // 1. Parse out the class name and sub-folders
        $className = class_basename($name);
        $subFolder = dirname($name) !== '.' ? dirname($name) : '';

        // 2. Format the namespace and directory path
        $subNamespace = $subFolder ? '\\' . str_replace('/', '\\', $subFolder) : '';
        $fullNamespace = "App\\Actions" . $subNamespace;

        $directoryPath = app_path('Actions/' . $subFolder);
        $filePath = $directoryPath . '/' . $className . '.php';

        // 3. Ensure the folder pathway exists
        if (!File::isDirectory($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        // 4. Don't overwrite existing classes
        if (File::exists($filePath)) {
            $this->error("Action Class '{$name}' already exists!");
            return Command::FAILURE;
        }

        // 5. Define the Class Blueprint right here
        $template = <<<PHP
<?php

namespace {$fullNamespace};

class {$className}
{
    /**
     * Execute the action.
     */
    public function handle(): mixed
    {
        // Your action logic goes here
    }
}
PHP;

        // 6. Write the file to disk
        File::put($filePath, $template);

        $this->info("Action created successfully: app/Actions/" . ltrim($name, '/') . ".php");
        return Command::SUCCESS;
    }
}