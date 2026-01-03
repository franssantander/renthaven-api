<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     * * Usage: 
     * php artisan make:module-model Product Inventory
     * php artisan make:module-model Product Inventory -m
     */
    protected $signature = 'make:module-model 
                            {name : The name of the model class} 
                            {module : The module where this model belongs}
                            {--m|migration : Create a new migration file for the model}';

    protected $description = 'Create a new Eloquent Model class inside a Module';

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
        
        // 1. Define Paths
        $modelPath = app_path("Modules/{$module}/Models");
        $filePath = "{$modelPath}/{$name}.php";

        // 2. Ensure Models Directory Exists
        if (! $this->files->isDirectory($modelPath)) {
            $this->files->makeDirectory($modelPath, 0755, true);
        }

        // 3. Check if Model exists
        if ($this->files->exists($filePath)) {
            $this->error("Model {$name} already exists in module {$module}!");
            return;
        }

        // 4. Create Model File
        $content = $this->getStub($name, $module);
        $this->files->put($filePath, $content);
        $this->info("Model [{$name}] created successfully in [{$module}/Models].");

        // 5. Handle Migration (Optional)
        if ($this->option('migration')) {
            $this->createMigration($name, $module);
        }
    }

    protected function getStub($name, $module)
    {
        $namespace = "App\\Modules\\{$module}\\Models";
        
        // Auto-guess table name: Product -> products
        $tableName = Str::snake(Str::pluralStudly($name));

        return <<<EOT
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$name} extends Model
{
    use HasFactory, SoftDeletes;

    protected \$table = '{$tableName}';

    protected \$guarded = [];

    protected \$casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
EOT;
    }

    protected function createMigration($name, $module)
    {
        $tableName = Str::snake(Str::pluralStudly($name));
        $migrationName = "create_{$tableName}_table";
        
        // Path relative to Laravel root for the artisan command
        $migrationPath = "app/Modules/{$module}/Database/Migrations";

        // Ensure migration directory exists
        if (! $this->files->isDirectory(base_path($migrationPath))) {
            $this->files->makeDirectory(base_path($migrationPath), 0755, true);
        }

        // Call the standard Laravel make:migration command
        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => $migrationPath,
        ]);
    }
}