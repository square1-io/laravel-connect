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

class InstallClient extends Command
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
    protected $signature = 'connect:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pending migrations, sets up Laravel Passport and generates client keys and secrets for login ';
    

    /**
     * Create a new migrator instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface             $resolver
     * @param  \Illuminate\Filesystem\Filesystem                            $files
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
        
        $settings = config("connect");
        
        if (!isset($settings)) {
            $this->error(" Missing connect configuration, have you run connect init ?");
            return;
        }
     
        
        //migrate
        $continue = $this->confirm("About to check if any migration before setting up Laravel Passport, proceed ? ");
        
        if ($continue == true) {
            $this->call("migrate");
            //migrate
            $this->call("passport:install");
            $this->call("passport:keys");
        
            $continue = $this->confirm("Do you want to create a new  Laravel Passport Client ?");
            
            if ($continue == true) {
                $this->call("passport:client");
            }
            $this->info("LaravelConnect  completed");
        } else {
            $this->info("LaravelConnect setup not completed");
        }
    }
}
