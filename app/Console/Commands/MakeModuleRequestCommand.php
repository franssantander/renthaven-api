<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example usage: php artisan make:module-request UpdateProfileRequest User
     */
    protected $signature = 'make:module-request 
                            {name : The name of the request class} 
                            {module : The module where this request belongs}';

    protected $description = 'Create a new Form Request class inside a Module';

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

        // 1. Define the path: app/Modules/{Module}/Http/Requests/{Name}.php
        $path = app_path("Modules/{$module}/Http/Requests");
        $filePath = "{$path}/{$name}.php";

        // 2. Ensure the directory exists
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // 3. Check if file already exists
        if ($this->files->exists($filePath)) {
            $this->error("Request {$name} already exists in module {$module}!");
            return;
        }

        // 4. Create the file content
        $content = $this->getStub($name, $module);

        $this->files->put($filePath, $content);

        $this->info("Request [{$name}] created successfully in [{$module}/Http/Requests].");
    }

    protected function getStub($name, $module)
    {
        $namespace = "App\\Modules\\{$module}\\Http\\Requests";

        return <<<EOT
<?php

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class {$name} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'email' => ['required', 'email'],
            // 'password' => ['required', 'min:8'],
        ];
    }

    /**
     * Optional: Override failed validation to return JSON immediately.
     * (Useful if your API middleware doesn't automatically handle this)
     */
    protected function failedValidation(Validator \$validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Please check the highlighted fields and try again.',
            'errors'  => \$validator->errors(),
        ], 422));
    }
}
EOT;
    }
}