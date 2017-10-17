<?php

namespace Square1\Laravel\Connect\Console;

use ErrorException;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use \Illuminate\Filesystem\Filesystem;
use Square1\Laravel\Connect\Model\ModelAttribute;
use Square1\Laravel\Connect\Model\ModelInspector;
use Square1\Laravel\Connect\Model\MigrationsHandler;
use Square1\Laravel\Connect\Model\MigrationInspector;
use Square1\Laravel\Connect\Model\RelationAttribute;
use Square1\Laravel\Connect\App\Routes\RoutesInspector;

class InitClient extends Command
{
    
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connect:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the default config for LaravelConnect';
    

    /**
     * Create a new migrator instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        
        $this->files = $files;
        // this will erase all previous config files
        
        //__DIR__.'/../config/connect.php'
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Initialising LaravelConnect, the command will also setup Laravel Passport for users authentication');
        
        //migrate
        $continue = $this->confirm("This will override your current connect config, proceed ? ");
        
        if ($continue) {
            $this->files->copy(__DIR__.'/../config/connect.php', config_path()."/connect.php");
        }
    }
}
