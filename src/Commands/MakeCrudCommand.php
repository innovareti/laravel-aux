<?php

namespace LaravelAux\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

/** @extends Command */
class MakeCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a basic CRUD (Controller, Service, Repository, Request...)';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Prepare structure
        $model = ucfirst($this->argument('model'));
        
        // Verificar e criar diretórios necessários
        $this->ensureDirectoriesExist();

        // Criar arquivos
        $this->makeMigration($model);
        $this->makeModel($model);
        $this->makeRepository($model);
        $this->makeService($model);
        $this->makeRequest($model);
        $this->makeController($model);
        $this->appendRoute($model);

        // Success Message
        $this->info('CRUD criado com sucesso!');
        
        return Command::SUCCESS;
    }

    /**
     * Verifica e cria os diretórios necessários se não existirem
     *
     * @return void
     */
    private function ensureDirectoriesExist(): void
    {
        $basePath = App::basePath();
        $directories = [
            'app/Repositories',
            'app/Services',
            'app/Http/Controllers/Api',
            'app/Http/Requests',
            'app/Models',
            'routes',
            'database/migrations'
        ];

        foreach ($directories as $directory) {
            $path = $basePath . DIRECTORY_SEPARATOR . $directory;
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("Diretório criado: {$directory}");
            }
        }
    }

    /**
     * Method to append Routes to api.php file (Laravel)
     *
     * @param string $model
     * @return void
     */
    private function appendRoute(string $model): void
    {
        $plural = strtolower(Str::plural($model));
        $route = <<<EOF

/*
|--------------------------------------------------------------------------
| {$model} Routes
|--------------------------------------------------------------------------
*/
Route::apiResource('{$plural}', \App\Http\Controllers\Api\\{$model}Controller::class);
EOF;
        File::append(App::basePath() . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php', $route . PHP_EOL);
    }

    /**
     * Method to make Controller based on passed Model
     *
     * @param string $model
     * @return void
     */
    private function makeController(string $model): void
    {
        $service = $model . 'Service';
        $request = $model . 'Request';
        $controller = <<<EOF
<?php

namespace App\Http\Controllers\Api;

use App\Services\\$service;
use App\Http\Requests\\$request;
use LaravelAux\BaseController;

class {$model}Controller extends BaseController
{
    /**
     * {$model}Controller constructor.
     *
     * @param {$service} \$service
     * @param {$request} \$request
     */
    public function __construct({$service} \$service)
    {
        parent::__construct(\$service, new {$request});
    }
}
EOF;
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . $model . 'Controller.php', $controller);
    }

    /**
     * Method to make Request based on passed Model
     *
     * @param string $model
     * @return void
     */
    private function makeRequest(string $model): void
    {
        $request = <<<EOF
<?php

namespace App\Http\Requests;

use LaravelAux\BaseRequest;

class {$model}Request extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute é obrigatório',
            'string' => ':attribute deve ser um texto',
            'max' => ':attribute não pode ter mais que :max caracteres'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'Título',
            'description' => 'Descrição'
        ];
    }
}
EOF;
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $model . 'Request.php', $request);
    }

    /**
     * Method to make Service based on passed Model
     *
     * @param string $model
     * @return void
     */
    private function makeService(string $model): void
    {
        $repository = $model . 'Repository';
        $service = <<<EOF
<?php

namespace App\Services;

use App\Repositories\\$repository;
use LaravelAux\BaseService;

class {$model}Service extends BaseService
{
    /**
     * {$model}Service constructor.
     *
     * @param {$repository} \$repository
     */
    public function __construct({$repository} \$repository)
    {
        parent::__construct(\$repository);
    }
}
EOF;
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $model . 'Service.php', $service);
    }

    /**
     * Method to make Repository based on passed Model
     *
     * @param string $model
     * @return void
     */
    private function makeRepository(string $model): void
    {
        $repository = <<<EOF
<?php

namespace App\Repositories;

use App\Models\\$model;
use LaravelAux\BaseRepository;

class {$model}Repository extends BaseRepository
{
    /**
     * {$model}Repository constructor.
     *
     * @param {$model} \$model
     */
    public function __construct({$model} \$model)
    {
        parent::__construct(\$model);
    }
}
EOF;
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR . $model . 'Repository.php', $repository);
    }

    /**
     * Method to make Eloquent Model
     *
     * @param string $model
     * @return void
     */
    private function makeModel(string $model): void
    {
        $modelContent = <<<EOF
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class {$model} extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected \$fillable = [
        'title',
        'description'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected \$casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
EOF;
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $model . '.php', $modelContent);
    }

    /**
     * Method to make Migration based on passed Model
     *
     * @param string $model
     * @return void
     */
    public function makeMigration(string $model): void
    {
        $table = strtolower(Str::plural($model));
        $migration = <<<EOF
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
            \$table->string('title');
            \$table->text('description');
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
EOF;
        $timestamp = date('Y_m_d_His');
        File::put(App::basePath() . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $timestamp . '_create_' . $table . '_table.php', $migration);
    }
}
