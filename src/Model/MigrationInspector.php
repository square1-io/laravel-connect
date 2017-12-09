<?php

namespace Square1\Laravel\Connect\Model;

use Closure;
use Exception;
use ReflectionClass;
use Illuminate\Support\Fluent;
use Square1\Laravel\Connect\Console\MakeClient;
use \Illuminate\Filesystem\Filesystem;

class MigrationInspector
{
    private $model;
     
    private $modelInfo;

    private $baseTmpPath;
     
    private $client;

    private $attributes;
     
    private $relations;

    private $bluePrints;
     
    private $commands;
     
     /**
      * The filesystem instance.
      *
      * @var \Illuminate\Filesystem\Filesystem
      */
    protected $files;

     /**
      * Create a new  instance.
      *
      * @return void
      */
    public function __construct($className, Filesystem $files, MakeClient $client)
    {
        $this->files = $files;
        $this->client = $client;
        $this->baseTmpPath = $client->baseTmpPath."/migration";
        $this->bluePrints = array();
        $this->commands = array();
        $this->modelInfo = new ReflectionClass(new $className);
        $this->model = $this->prepareForInspection();
     
        $this->attributes = array();
        $this->relations = array();
    }
    
    public function classShortName()
    {
        return $this->modelInfo->getShortName();
    }
    
    public function className()
    {
        return $this->modelInfo->getName();
    }
    

    private function prepareForInspection()
    {
        $injectedClassName = $this->classShortName();
        $baseCode = $this->files->get(dirname(__FILE__)."/templates/Injected.migration.template.php");
        $baseCode = str_replace('_INJECTED_MIGRATION_NAME_Template', $injectedClassName, $baseCode);
        $baseCode = str_replace('_INJECTED_EXTENDED_MIGRATION_NAME_', $this->className(), $baseCode);
        
        $injectedClassName = 'Square1\Laravel\Connect\Console\Injected\Injected'.$this->classShortName();
        //prepare tmp folder
        if ($this->files->isDirectory($this->baseTmpPath) == false) {
            $this->files->makeDirectory($this->baseTmpPath, 0755, true);
        }
        
        //get the file name to store this new class in
        $fileName = $this->injectedClassFileName();
        if ($this->files->isFile($fileName) == true) {
            $this->files->delete($fileName);
        }
        
        $this->files->put($fileName, $baseCode);
        include_once $fileName;
        
        return new $injectedClassName($this);
    }
    
    public function inspect()
    {
        $this->client->info('starting inspection of '.$this->injectedClassFileName());
   
        try {
            $this->model->inspect();
        } catch (Exception $e) {
            // dd($e->getTraceAsString());
        }
    }
    
    private function injectedClassFileName()
    {
        return strtolower($this->baseTmpPath.'/'.str_replace('\\', '_', $this->className()).'.php');
    }

    
       /**
        * Create a new command set with a Closure.
        *
        * @param  string        $table
        * @param  \Closure|null $callback
        * @return \Illuminate\Database\Schema\Blueprint
        */
    public function createBlueprint($table, Closure $callback = null)
    {
        if (!array_key_exists($table, $this->bluePrints)) {
            $this->bluePrints[$table] = new ApiClientBlueprint($this, $table, $callback);
        }
        return $this->bluePrints[$table];
    }
    
    public function inspectionCompleted()
    {
        foreach ($this->bluePrints as $bluePrint) {
            $this->commands[$bluePrint->getTable()] = $bluePrint->getCommands();
            
            $currentColumns = $bluePrint->getColumns();
            //dd($currentColumns);
            $this->client->info("found in ".$bluePrint->getTable(), 'vvv');
            
            foreach ($currentColumns as $column) {
                $attribute = new ModelAttribute($column);
                
                $attribute->fluent = $column;
                $attribute->allowed = $column->allowed;
                
                $this->attributeFound($bluePrint->getTable(), $attribute);
            }
        }
    }

    public function attributeFound($table, ModelAttribute $attribute)
    {
        $this->client->info($attribute, 'vvv');
        $this->attributes[$table][$attribute->name][] = $attribute;
    }
    
    public function relationFound($table, ModelAttribute $attribute)
    {
        $this->client->info("relation found in table $table :".$attribute, 'vvv');
        $this->relations[$table][$attribute->name][] = $attribute;
    }
    
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    public function getCommands()
    {
        return $this->commands;
    }
}
