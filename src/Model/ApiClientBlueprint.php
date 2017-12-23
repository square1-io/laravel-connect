<?php

namespace Square1\Laravel\Connect\Model;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;

class ApiClientBlueprint extends Blueprint
{
    private $inspector;
    
 
    /**
     * Create a new schema blueprint.
     *
     * @param  string        $table
     * @param  \Closure|null $callback
     * @return void
     */
    public function __construct(
        MigrationInspector $inspector,
        $table,
        Closure $callback = null
    ) {
        parent::__construct($table, $callback);
        $this->inspector = $inspector;
    }
    
    
    public function build(Connection $connection = null, Grammar $grammar = null)
    {
    }
}
