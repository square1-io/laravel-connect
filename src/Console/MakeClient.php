<?php

namespace Square1\Laravel\Connect\Console;

use ErrorException;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use \Illuminate\Filesystem\Filesystem;
use Square1\Laravel\Connect\Clients\Android\AndroidClientWriter;
use Square1\Laravel\Connect\Clients\iOS\iOSClientWriter;
use Square1\Laravel\Connect\Model\ModelInspector;
use Square1\Laravel\Connect\Model\MigrationsHandler;
use Square1\Laravel\Connect\App\Routes\RoutesInspector;

class MakeClient extends Command
{
    
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $files;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connect:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the LaravelConnect Client ';
    
    /**
     *
     * Map Database table list of parameters to the table name
     * @var type array
     */
    public $tableMap;
    
    /**
     *
     * Map of modelInspectors and class name
     *
     * @var type array
     */
    public $classMap;
    

    /**
     *
     * Map of modelInspectors and the table name used to store this model in the database
     *
     * @var type array
     */
    public $tableInspectorMap;
    
    
    /**
     *
     * the path to the folder where all the models files are stored
     *
     * @var type string
     */
    private $modelFolder;
    
    
    /**
     *
     * @var type
     */
    private $migrationsHandler;




    public $baseTmpPath;

    public $baseBuildPath;
    
    public $baseRepositoriesPath;

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
        $this->classMap = [];
        $this->tableMap = [];
        $this->tableInspectorMap = [];
        $this->modelFolder = config('connect.model_classes_folder');
        
        $this->baseTmpPath = base_path('tmp');
        $this->baseBuildPath = config('connect.clients.build_path');
        $this->baseRepositoriesPath = app_path()."/repositories/connect";
        
        $this->migrationsHandler = new MigrationsHandler($this->files, $this);
       
        
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Building the Laravel Client code');
       
        $settings = config("connect");
        
        if (!isset($settings)) {
            $this->error(" Missing connect configuration, have you run connect init ?");
            return;
        }
        
        $this->prepareStorage();

        //loop over the migrations to see what parameters to add to each model opbject
         $this->tableMap = $this->migrationsHandler->process();
        
        //dd($this->tableMap);
        //list of classes extending Model
        $this->processModelSubclasses();
        
        //removing hidden fields from tables
        $this->removeHiddenFields();
 
        
        //check for routes and requests that need to be exposed
        $routesInspector = new RoutesInspector($this->files, $this);
        $routesInspector->inspect();

        foreach ($routesInspector->routes as $route) {
            $modelClass = $route['model'];
            
            if (isset($this->classMap[$modelClass])) {
                if (!isset($this->classMap[$modelClass]["routes"])) {
                    $this->classMap[$modelClass]["routes"] = [];
                }
                $this->classMap[$modelClass]["routes"][] = $route;
            }
        }
       
        $this->dumpObject('classMap', $this->classMap);
        $this->dumpObject('tableMap', $this->tableMap);
        
        $this->generateConfig();

        $this->outputClient();
    }
    
    private function processModelSubclasses()
    {
        $files = $this->files->allFiles($this->modelFolder);
        foreach ($files as $file) {
            require_once($file);
        }
        
        $classes = get_declared_classes();
        
        foreach ($classes as $class) {
            //discard framework classes
            if (is_subclass_of($class, '\Illuminate\Database\Eloquent\Model')) {
                //
                $this->info("-----------------------------------------", 'vvv');
                $this->info("-                                       ", 'vvv');
                $this->info("-                 $class                ", 'vvv');
                $this->info("-                                       ", 'vvv');
                $this->info("-----------------------------------------", 'vvv');
                
                $inspector = new ModelInspector($class, $this->files, $this);
                
                if ($inspector->hasTrait() == true) {
                    $inspector->init();
                    $this->classMap[$class]['inspector'] = $inspector;
                    $this->tableInspectorMap[$inspector->tableName()] = $inspector;
                    $inspector->inspect();
                }
            }
        }
    }
    
    private function makeRepository(ModelInspector $modelInspector)
    {
        $injectedClassName = $modelInspector->classShortName();
        $baseCode = $this->files->get(dirname(__FILE__)."/../model/templates/Injected.repository.template.php");
        
        $baseCode = str_replace('_INJECTED_APP_NAMESPACE_', app()->getNamespace(), $baseCode);
        $baseCode = str_replace('_INJECTED_CLASS_NAME_Template', $injectedClassName, $baseCode);
        $baseCode = str_replace('_INJECTED_EXTENDED_CLASS_NAME_', $modelInspector->className(), $baseCode);
         
        
        $this->files->put($this->baseRepositoriesPath."/".$modelInspector->classShortName()."ConnectRepository.php", $baseCode);
        
        return app()->getNamespace()."Repositories\Connect\\".$modelInspector->classShortName()."ConnectRepository";
    }


    private function generateConfig()
    {
        $endpoints = config("connect_auto_generated.endpoints");
        
        foreach ($this->classMap as $inspector) {
            $inspector = $inspector['inspector'];
            $className = $inspector->className();
            $classPath = $inspector->endpointReference();
            $repositoryClass = $this->makeRepository($inspector);
            //do not override in case developer wants to use a different class
            if (isset($endpoints[$classPath]) == false) {
                $endpoints[$classPath] = $repositoryClass;
            }
        }
        $settings = [];
        $settings['endpoints'] = $endpoints;
        
        $config_file = config_path()."/connect_auto_generated.php";
        $this->files->delete($config_file);
        $this->files->put($config_file, "<?php\n\nreturn ".var_export($settings, true).";");
    }



    private function prepareStorage()
    {
        $this->initAndClearFolder($this->baseTmpPath);
        $this->initAndClearFolder($this->baseBuildPath);
     
        $this->initAndClearFolder($this->baseRepositoriesPath);
    }


    public function removeHiddenFields()
    {
        
        ///looping over the ModelInspectors and removing hidden fields
        foreach ($this->tableInspectorMap as $tableName => $model) {
            foreach ($model->getHidden() as  $hidden) {
                $this->info("$tableName hiding ".$hidden, 'vvv');
                unset($this->tableMap[$tableName]['attributes'][$hidden]);
            }
        }
    }
    


    public function dumpObject($fileName, $object)
    {
        $fileName =  $this->baseTmpPath.'/'.$fileName.'.json';
      
        $this->files->delete($fileName) ;
        $this->files->put($fileName, json_encode($object));
    }

    private function outputClient()
    {
        $android = new AndroidClientWriter($this);
        $android->outputClient();

        $ios = new iOSClientWriter($this);
        $ios->outputClient();
    }

    //JAVA
    

    
  


    
    public function getModelClassFromTableName($table)
    {
        $modelInspector =  isset($this->tableInspectorMap[$table]) ? $this->tableInspectorMap[$table] : null ;
       
        return $modelInspector ? $modelInspector->className() : null;
    }

    
    /**
     * create the folder , deletes first if exisits
     *
     * @param  string  $folder
     */
    public function initAndClearFolder($folder)
    {
        if ($this->files->isDirectory($folder) == true) {
            $this->files->deleteDirectory($folder);
        }
        $this->files->makeDirectory($folder, 0755, true);
    }

    
    /**
     * Get the name of the migration.
     *
     * @param  string  $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }
    
    
    /**
     * Get all of the migration files in a given path.
     *
     * @param  string|array  $paths
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path.'/*_*.php');
        })->filter()->sortBy(function ($file) {
            return $this->getMigrationName($file);
        })->values()->keyBy(function ($file) {
            return $this->getMigrationName($file);
        })->all();
    }
    
    public static function getProtectedValue($obj, $name)
    {
        $array = (array)$obj;
        $prefix = chr(0).'*'.chr(0);
        return $array[$prefix.$name];
    }

    public function get_this_class_methods($class)
    {
        $array1 = get_class_methods($class);
        if ($parent_class = get_parent_class($class)) {
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        } else {
            $array3 = $array1;
        }
        return($array3);
    }
}
