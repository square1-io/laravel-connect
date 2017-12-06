<?php

namespace Square1\Laravel\Connect\Console\Injected;

use Closure;
use _INJECTED_EXTENDED_MIGRATION_NAME_;
use Square1\Laravel\Connect\Model\ConnectSchema;

use Square1\Laravel\Connect\Model\MigrationInspector;

class Injected_INJECTED_MIGRATION_NAME_Template extends _INJECTED_MIGRATION_NAME_Template
{
    private $inspector;
    
    public function __construct(MigrationInspector $inspector)
    {
        $this->inspector = $inspector;

        foreach (class_parents($this) as $parent) {
            if (method_exists($parent, '__construct')) {
                parent::__construct();
                break;
            }
        }
    }

    public function inspect()
    {
        ConnectSchema::inspecting($this->inspector);
        $this->up();
        $this->inspector->inspectionCompleted();
    }
}
