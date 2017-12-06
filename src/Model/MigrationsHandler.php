<?php

namespace Square1\Laravel\Connect\Model;

use Illuminate\Support\Collection;
use \Illuminate\Filesystem\Filesystem;
use Square1\Laravel\Connect\Console\MakeClient;
use Square1\Laravel\Connect\Model\MigrationInspector;

class MigrationsHandler
{
   
    
    /**
     *
     * Map Database table list of parameters to the table name
     * @var type array
     */
    private $tableMap;
    
    
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;
    
    private $client;
      
    public function __construct(Filesystem $files, MakeClient $client)
    {
        $this->files = $files;
        $this->client = $client;
        $this->tableMap = [];
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
    
    
    public function process()
    {
        $this->client->info("----  PROCESSING MIGRATIONS  ------");

        $migrations =  $this->getMigrationFiles(database_path()."/migrations");
       
        foreach ($migrations as $migration) {
            $this->files->requireOnce($migration);
        }

        $classes = get_declared_classes();

        foreach ($classes as $class) {
          
            //discard framework classes
            if (is_subclass_of($class, 'Illuminate\Database\Migrations\Migration') &&
                    strpos((string)$class, 'Illuminate') === false) {
                $inspector = new MigrationInspector($class, $this->files, $this->client);
                $inspector->inspect();
                $this->aggregateTableDetails($inspector);
            }
        }
        
        return $this->tableMap;
    }
    
    private function aggregateTableDetails(MigrationInspector $inspector)
    {
        foreach ($inspector->getAttributes() as $table => $attributes) {
  
            //loop over the attributes for that table
            foreach ($attributes as $attribute => $attributeSettings) {
                foreach ($attributeSettings as $attributeSetting) {
                    $this->tableMap[$table]['attributes'][$attribute] = $attributeSetting;
                }
            }
        }
        
        foreach ($inspector->getCommands() as $table => $commands) {
            if (!isset($this->tableMap[$table]["commands"])) {
                $this->tableMap[$table]["commands"] = array();
            }
            $this->tableMap[$table]["commands"] = array_merge($commands, $this->tableMap[$table]["commands"]);
        }
        
        //now we need to apply those commands
        foreach ($this->tableMap as $table) {
            $this->runCommandsOnTable($table);
        }
    }
    
    private function runCommandsOnTable(&$table)
    {
        $attributes = $table["attributes"];
        $commands = $table["commands"];
        
        foreach ($commands as $command) {
            if ($command->name == "foreign") {
                $column =  $command->columns[0];
                if (isset($attributes[$column])) {
                    $attributes[$column]->on = $command->on;
                    $attributes[$column]->references = $command->references;
                }
            }
        }
        
        $table['attributes'] = $attributes;
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
}
