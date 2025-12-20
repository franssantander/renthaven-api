<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The module name (e.g., User)}
                                        {--force : Overwrite existing files}
                                        {--api= : Numeric API version (e.g., 1)}
                                        {--all-dir : Include all the directories}';

    protected string $apiVersion = 'v1';

    /**
     * The console command description.
     *
     *  # Only models and controllers
     *  php artisan make:module Blog --dirs=Models,Controllers
     *
     *  # Everything except Mail and Services
     *  php artisan make:module Blog --except=Mail,Services
     */
    protected $description = 'Scaffold a feature module (versioned API) with directories and stubs: Models, Actions, Data, Routes, Services, Mail, Http/Controllers/API/v{n}, Http/Requests, Providers (incl. Model, Controller, Routes, Service, Request, and ServiceProvider stubs).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nameInput = (string) $this->argument('name');
        $module = Str::studly($nameInput);
        $force = (bool) $this->option('force');

        // Validate and normalize API version option; reject non-numeric strings
        $versionOption = $this->option('api');
        if ($versionOption !== null && $versionOption !== '') {
            $versionString = (string) $versionOption;
            if (! ctype_digit($versionString)) {
                $this->error('Invalid --version. Provide a number like 1, 2, ...');

                return self::FAILURE;
            }

            $this->apiVersion = 'v'.$versionString;
        }

        $basePath = app_path('Modules/'.$module);

        // Map of selectable logical directories to their relative paths
        $directoryMap = [
            'Actions' => 'Actions',
            'Data' => 'Data',
            'Models' => 'Models',
            // 'Repositories' => 'Repositories',
            'Routes' => 'Routes',
            'Services' => 'Services',
            'Mail' => 'Mail',
            'Controllers' => 'Http/Controllers/API/'.$this->apiVersion,
            'Requests' => 'Http/Requests',
            'Resources' => 'Http/Resources',
            'Providers' => 'Providers',
            'Notifications' => 'Notifications',
            'Database' => 'Database',
        ];

        // Determine which directories to scaffold
        //        $dirsOption   = trim((string) $this->option('dirs'));
        //        $exceptOption = trim((string) $this->option('except'));
        $allDir = (bool) $this->option('all-dir');

        if ($allDir) {
            $selectedKeys = array_keys($directoryMap);
        } else {
            $choices = array_keys($directoryMap);
            $defaultDirs = ['Controllers', 'Requests', 'Resources', 'Models', 'Providers', 'Routes', 'Services'];

            // Display available + default
            $this->line('');
            $this->line('Available directories: '.implode(', ', $choices));
            $this->line('Default directories:   '.implode(', ', $defaultDirs));
            $this->line('');

            // Ask the user
            $answer = $this->ask(
                'Which directories do you want to create? (comma-separated) [press Enter for defaults]'
            );

            // Fallback to defaults
            if (empty($answer)) {
                $selectedKeys = $defaultDirs;
                $this->line('Using default directories: '.implode(', ', $selectedKeys));
            } else {
                $requested = array_filter(array_map('trim', explode(',', $answer)));
                $requestedLower = array_map('strtolower', $requested);

                $selectedKeys = [];
                foreach ($directoryMap as $key => $_) {
                    if (in_array(strtolower($key), $requestedLower, true)) {
                        $selectedKeys[] = $key;
                    }
                }

                if (empty($selectedKeys)) {
                    $this->warn('Invalid selection. Falling back to defaults.');
                    $selectedKeys = $defaultDirs;
                }
            }
        }

        // elseif ($dirsOption !== '') {
        //            $requested       = array_filter(array_map('trim', explode(',', $dirsOption)));
        //            $requestedLower  = array_map('strtolower', $requested);
        //
        //            $selectedKeys = array_values(array_filter(array_keys($directoryMap), function ($key) use ($requestedLower) {
        //                return in_array(strtolower($key), $requestedLower, true);
        //            }));
        //
        //            if (empty($selectedKeys)) {
        //                $this->error('No valid directories specified via --dirs. Available: ' . implode(',', array_keys($directoryMap)));
        //                return self::FAILURE;
        //            }
        //        } elseif ($exceptOption !== '') {
        //            $excluded      = array_filter(array_map('trim', explode(',', $exceptOption)));
        //            $excludedLower = array_map('strtolower', $excluded);
        //
        //            $selectedKeys = array_values(array_filter(array_keys($directoryMap), function ($key) use ($excludedLower) {
        //                return !in_array(strtolower($key), $excludedLower, true);
        //            }));
        //        }

        // Create the selected directories
        foreach ($selectedKeys as $logicalKey) {
            $relative = $directoryMap[$logicalKey];
            $path = $basePath.DIRECTORY_SEPARATOR.$relative;
            if (! is_dir($path) && ! mkdir($path, 0777, true) && ! is_dir($path)) {
                $this->error("Failed to create directory: {$path}");

                return self::FAILURE;
            }
            $gitkeep = $path.DIRECTORY_SEPARATOR.'.gitkeep';
            if (! file_exists($gitkeep)) {
                @file_put_contents($gitkeep, '');
            }
            $this->info("Created: {$path}");
        }

        // Convenience flags for conditional stub generation
        $includeModels = in_array('Models', $selectedKeys, true);
        $includeControllers = in_array('Controllers', $selectedKeys, true);
        $includeRoutes = in_array('Routes', $selectedKeys, true);
        $includeServices = in_array('Services', $selectedKeys, true);
        $includeRequests = in_array('Requests', $selectedKeys, true);
        $includeProviders = in_array('Providers', $selectedKeys, true);

        /** factory, database, migrations */
        $includeDatabase = in_array('Database', $selectedKeys, true);

        // Create a minimal Model stub
        if ($includeModels) {
            $modelClass = $module;
            $modelPath = $basePath.'/Models/'.$modelClass.'.php';

            // remove model stubs.
            //            if (! file_exists($modelPath) || $force) {
            //                $modelStub = $this->buildModelStub($module, $modelClass);
            //                file_put_contents($modelPath, $modelStub);
            //                $this->info("Stubbed Model: {$modelPath}");
            //            } else {
            //                $this->line("Skip (exists): {$modelPath}");
            //            }
        }

        // Create a minimal API v1 Controller stub
        if ($includeControllers) {
            // $controllerClass = $module.'Controller';

            @mkdir($basePath.'/Http/Controllers/API/'.$this->apiVersion);

            // $controllerPath = $basePath.'/Http/Controllers/API/'.$this->apiVersion.'/'.$controllerClass.'.php';
            //            if (! file_exists($controllerPath) || $force) {
            //                $controllerStub = $this->buildControllerStub($module, $controllerClass, $this->apiVersion);
            //                file_put_contents($controllerPath, $controllerStub);
            //                $this->info("Stubbed Controller: {$controllerPath}");
            //            } else {
            //                $this->line("Skip (exists): {$controllerPath}");
            //            }
        }

        // Create a Routes stub
        if ($includeRoutes) {
            $routesPath = $basePath.'/Routes/api.php';
            $controllerClass = ($controllerClass ?? ($module.'Controller'));
            if (! file_exists($routesPath) || $force) {
                $routesStub = $this->buildRoutesStub($module, $controllerClass, $this->apiVersion);
                file_put_contents($routesPath, $routesStub);
                $this->info("Stubbed Routes: {$routesPath}");
            } else {
                $this->line("Skip (exists): {$routesPath}");
            }
        }

        // Create a Service stub
        if ($includeServices) {
            $serviceClass = $module.'Service';
            $servicePath = $basePath.'/Services/'.$serviceClass.'.php';
            if (! file_exists($servicePath) || $force) {
                $serviceStub = $this->buildServiceStub($module, $serviceClass);
                file_put_contents($servicePath, $serviceStub);
                $this->info("Stubbed Service: {$servicePath}");
            } else {
                $this->line("Skip (exists): {$servicePath}");
            }
        }

        // Create a Form Request stub
        if ($includeRequests) {
            @mkdir($basePath.'/Http/Requests');
            // $requestClass = $module.'Request';
            // $requestPath = $basePath.'/Http/Requests/'.$requestClass.'.php';
            //            if (! file_exists($requestPath) || $force) {
            //                $requestStub = $this->buildRequestStub($module, $requestClass);
            //                file_put_contents($requestPath, $requestStub);
            //                $this->info("Stubbed Request: {$requestPath}");
            //            } else {
            //                $this->line("Skip (exists): {$requestPath}");
            //            }
        }

        // Create a Service Provider stub
        if ($includeProviders) {
            $providerClass = $module.'ServiceProvider';
            $providerPath = $basePath.'/Providers/'.$providerClass.'.php';
            if (! file_exists($providerPath) || $force) {
                $providerStub = $this->buildProviderStub($module);
                file_put_contents($providerPath, $providerStub);
                // Register it in bootstrap/providers.php
                $this->registerProviderInBootstrap($module);
                $this->info("Stubbed Provider: {$providerPath}");
            } else {
                $this->line("Skip (exists): {$providerPath}");
            }
        }

        // Create module database folder structure and stubs
        if ($includeDatabase) {
            $databasePath = app_path("Modules/{$module}/Database");
            $factoriesPath = "{$databasePath}/Factories";
            $seedersPath = "{$databasePath}/Seeders";
            $migrationsPath = "{$databasePath}/Migrations";

            @mkdir($factoriesPath, 0777, true);
            @mkdir($seedersPath, 0777, true);
            @mkdir($migrationsPath, 0777, true);
        }

        $this->newLine();
        $this->info("Module '{$module}' scaffolded successfully.");
        $this->line('Next steps:');
        $this->line("- Implement actions/services in app/Modules/{$module}");
        $this->line('- Load module routes (e.g., from routes/api.php include the module routes file)');

        return self::SUCCESS;
    }

    private function registerProviderInBootstrap(string $module): void
    {
        $providersFile = base_path('bootstrap/providers.php');
        $providerClass = "App\\Modules\\{$module}\\Providers\\{$module}ServiceProvider";

        if (! file_exists($providersFile)) {
            $this->error("Providers file not found: {$providersFile}");

            return;
        }

        $contents = file_get_contents($providersFile);

        if (str_contains($contents, $providerClass)) {
            $this->line('Provider already registered in bootstrap/providers.php');

            return;
        }

        $pattern = '/return\s*\[(.*?)\];/s';

        $newContents = preg_replace_callback($pattern, function ($matches) use ($providerClass) {
            // Trim whitespace/newlines around captured content
            $inner = trim($matches[1], "\n\r ");

            // Rebuild return array with controlled formatting
            return "return [\n    ".$inner."\n    {$providerClass}::class,\n];";
        }, $contents, 1);

        if ($newContents) {
            file_put_contents($providersFile, $newContents);
            $this->info("Registered {$providerClass} in bootstrap/providers.php");
        } else {
            $this->error("Failed to register {$providerClass} in bootstrap/providers.php");
        }
    }

    // Model Stub
    /**
     * Remove from the make command, only folder included.
     *
     * @deprecated
     */
    private function buildModelStub(string $module, string $modelClass): string
    {
        $namespace = "App\\Modules\\{$module}\\Models";
        $modelTable = Str::snake(Str::pluralStudly($module));
        $factoryClass = "{$modelClass}Factory";

        return <<<PHP
            <?php

            namespace {$namespace};

            use Illuminate\\Database\\Eloquent\\Model;
            use Illuminate\\Database\\Eloquent\\SoftDeletes;
            use Illuminate\\Notifications\\Notifiable;
            use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
            use App\\Modules\\$module\\Database\\Factories\\$factoryClass;

            class {$modelClass} extends Model
            {
                /** @use HasFactory<$factoryClass> */
                use HasFactory,SoftDeletes, Notifiable;

                protected \$table = '{$modelTable}';

                protected \$guarded = [];

                // Set the updated at null upon creation
                public const UPDATED_AT = null;

            }
            PHP;
    }

    private function buildControllerStub(string $module, string $controllerClass, string $apiVersion): string
    {
        $namespace = "App\\Modules\\{$module}\\Http\\Controllers\\API\\{$apiVersion}";
        $modelNamespace = "App\\Modules\\{$module}\\Models\\{$module}";
        $modelParams = Str::lower(Str::pluralStudly($module));

        return <<<PHP
        <?php

        namespace {$namespace};

        use App\\Http\\Controllers\\Controller;
        use App\\Shared\\Actions\\Crud\\ActionFactory;
        use {$modelNamespace};
        use App\\Modules\\{$module}\\Http\\Requests\\{$module}Request;
        use Illuminate\\Database\\DatabaseManager;
        use Illuminate\\Cache\\Repository as CacheRepository;
        use Illuminate\\Http\\JsonResponse;
        use Illuminate\\Http\\Request;
        use Throwable;

        class {$controllerClass} extends Controller
        {
            protected ActionFactory \$actionFactory;

            protected {$module} \${$modelParams};

            public function __construct(DatabaseManager \$databaseManager, CacheRepository \$cache)
            {
                \$this->actionFactory = new ActionFactory(\$databaseManager, \$cache);
                \$this->{$modelParams} = new {$module}();
            }

              /**
             * @throws Throwable
             */
            public function index(Request \$request): JsonResponse
            {
                \$allAction = \$this->actionFactory->allAction(\$this->{$modelParams});
                \$resources = \$allAction->execute(\$allAction);

                // Transform via Resource
                return response()->json(\$resources,200);
            }

           /**
             * @throws Throwable
             */
            public function store({$module}Request \$request): JsonResponse{
                \$validated = \$request->validated();
                \$createAction = \$this->actionFactory->createAction(\$this->{$modelParams});
                \$resource = \$createAction->execute(\$validated);

               // Transform via Resource
                return response()->json(\$resource,201);
            }

            /**
             * @throws Throwable
             */
            public function show(string|int \$id): JsonResponse {
               \$readAction = \$this->actionFactory->readAction(\$this->{$modelParams});
               \$resource = \$readAction->execute(['id'=>\$id]);
               // transform via Resource
               return response()->json(\$resource,200);
            }


            /**
             * @throws Throwable
             */
            public function update({$module}Request \$request, string \$id): JsonResponse{
                \$validated = \$request->validated();
                \$updateAction = \$this->actionFactory->updateAction(\$this->{$modelParams});
                \$resource = \$updateAction->execute(\$id,\$validated);
                // transform via Resource
                return response()->json(\$resource,200);
            }


            /**
             * asd
             * @throws Throwable
             */
            public function partialUpdate({$module}Request \$request, string \$id): JsonResponse
            {
                \$validated = \$request->validated();
                \$partialUpdateAction = \$this->actionFactory->updateAction(\$this->{$modelParams});
                \$resource = \$partialUpdateAction->execute(\$id,\$validated);

                // transform via Resource
                return response()->json(\$resource, 200);
            }

            /**
             * @throws Throwable
             */
            public function destroy(string|int \$id): JsonResponse{
                 \$destroyAction = \$this->actionFactory->deleteAction(\$this->{$modelParams});
                 \$resource = \$destroyAction->execute(\$id);
                 // transform via Resource
                return response()->json(\$resource,200);
            }
        }
        PHP;
    }

    private function buildRoutesStub(string $module, string $controllerClass, string $apiVersion): string
    {
        $kebab = Str::kebab($module);

        return <<<PHP
        <?php

        use Illuminate\\Support\\Facades\\Route;

        // If you prefer central route files, you can import them here instead of defining inline:
        // require base_path('routes/api/{$apiVersion}/{$kebab}.php');
        Route::prefix('{$apiVersion}')
            ->name('{$apiVersion}.')
            ->group(function () {
                Route::prefix('{$kebab}')
                    ->name('{$kebab}.')
                    // ->controller(ModuleController::class)
                    ->group(function () {
                        // Standard REST-ish endpoints
                        // Route::get('/', 'index')->name('index');
                        // Route::post('/', 'store')->name('store');
                        // Route::get('{id}', 'show')->name('show');
                        // Route::patch('{id}', 'update')->name('update');
                        // Route::delete('{id}', 'destroy')->name('destroy');

                        // Examples for full replace vs partial update patterns
                        // Other related route here . .
                    });
            });
        PHP;
    }

    // Service Provider Stub
    private function buildProviderStub(string $module): string
    {
        $namespace = "App\\Modules\\{$module}\\Providers";
        $kebab = Str::kebab($module);

        return <<<PHP
        <?php

        namespace {$namespace};

        use Illuminate\\Foundation\Support\\Providers\\RouteServiceProvider as ServiceProvider;
        use Illuminate\\Support\\Facades\\Route;
        use Database\Seeders\DatabaseSeeder;
        use App\\Modules\\{$module}\\Database\\Seeders\\{$module}Seeder;

        class {$module}ServiceProvider extends ServiceProvider
        {
            public function register(): void
            {
                // Bind interfaces to implementations here
            }

            public function boot(): void
            {
                // Load module routes
                parent::boot();

                if(file_exists(app_path("Modules/{$module}/Routes/api.php"))) {
                //  Map api routes
                    \$this->mapApiRoutes();
                }

                if(file_exists(app_path("Modules/{$module}/Database/Migrations"))){
                       \$this->loadMigrationsFrom(app_path("Modules/{$module}/Database/Migrations"));
                }

                // Map a view namespace for this module (resources/views/{$kebab})
                //if (is_dir(resource_path('views/{$kebab}'))) {
                //  \$this->loadViewsFrom(resource_path('views/{$kebab}'), '{$kebab}');
                //}
            }

            protected function mapApiRoutes():void{
                Route::prefix('api')
                    ->middleware('api')
                    ->group(app_path('Modules/{$module}/Routes/api.php'));
            }
        }
        PHP;
    }

    private function buildServiceStub(string $module, string $serviceClass): string
    {
        $namespace = "App\\Modules\\{$module}\\Services";

        return <<<PHP
        <?php

        namespace {$namespace};

        use Illuminate\\Database\\DatabaseManager;
        use Illuminate\\Contracts\\Cache\\Repository as CacheRepository;

        class {$serviceClass}
        {
            public function __construct(
                private DatabaseManager \$databaseManager,
                private CacheRepository \$cache
            ) {}
        }
        PHP;
    }

    /**
     * Move into built int stub of the laravel.
     *
     * @deprecated
     *
     * @removed
     */
    private function buildRequestStub(string $module, string $requestClass): string
    {
        $namespace = "App\\Modules\\{$module}\\Http\\Requests";

        return <<<PHP
        <?php

        namespace {$namespace};

        use Illuminate\\Foundation\\Http\\FormRequest;

        class {$requestClass} extends FormRequest
        {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                // Different rules per method (POST for create, PUT/PATCH for update)
                if (\$this->isMethod('post')) {
                    return [
                        // e.g. 'name' => ['required', 'string', 'max:255'],
                        // e.g. 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    ];
                }

                if (\$this->isMethod('put') || \$this->isMethod('patch')) {
                    return [
                        // e.g. 'name' => ['sometimes', 'required', 'string', 'max:255'],
                        // e.g. 'email' => ['sometimes', 'required', 'email', 'max:255'],
                        // Note: handle unique rules by ignoring current model id when applicable
                        // e.g. Rule::unique('users','email')->ignore(\$this->route('id'))
                    ];
                }
                return [];
            }
        }
        PHP;
    }

    private function buildFactoryStub(string $module, string $model): string
    {
        $namespace = "App\\Modules\\{$module}\\Database\\Factories";

        return <<<PHP
        <?php

        namespace {$namespace};

        use App\\Modules\\{$module}\\Models\\{$model};
        use Illuminate\\Database\\Eloquent\\Factories\\Factory;
        use Illuminate\\Support\\Facades\\Hash;
        use Illuminate\\Support\\Str;

        /**
         * @extends Factory<{$model}>
         */
        class {$model}Factory extends Factory
        {
            protected \$model = {$model}::class;

            public function definition(): array
            {
                return [
                    // e.g. 'name' => ['required', 'string', 'max:255'],
                ];
            }
        }
        PHP;
    }

    private function buildSeederStub(string $module, string $model): string
    {
        $namespace = "App\\Modules\\{$module}\\Database\\Seeders";

        return <<<PHP
            <?php

            namespace {$namespace};

            use Illuminate\\Database\\Seeder;
            use App\\Modules\\{$module}\\Models\\{$model};

            class {$model}Seeder extends Seeder
            {
                public function run(): void
                {
                    {$model}::factory()->count(10)->create();
                }
            }
            PHP;
    }

    private function buildMigrationStub(string $table): string
    {
        return <<<PHP
        <?php

        use Illuminate\\Database\\Migrations\\Migration;
        use Illuminate\\Database\\Schema\\Blueprint;
        use Illuminate\\Support\\Facades\\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                    \$table->timestamps();
                    \$table->softDeletes();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        };
        PHP;
    }
}
