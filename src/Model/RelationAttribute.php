<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Square1\Laravel\Connect\Model;

use Illuminate\Support\Fluent;

class RelationAttribute
{
    const TYPE_HAS_MANY = 'hasMany';
    const TYPE_MORPH_MANY = 'morphMany';
    const TYPE_BELONGS_TO = 'belongsTo';
    const TYPE_HAS_ONE = 'hasOne';
    const TYPE_BELONGS_TO_MANY = 'belongsToMany';


    public $type;
    
    public $model;
   
    public $name;
    
    public $foreignKey;
    
    public $localKey;
    
    private $fluent;




    /**
     *
     * @param string $name  the name of this relationship
     * @param string $type  the type of the relationship
     * @param string $model the model class that this reference to
     */
    
    public function __construct($name, $type, $model)
    {
        $this->name = $name;
        $this->type = $type;
        $this->model = $model;
        $this->localKey = 'unset';
        $this->foreignKey = 'unset';
    }
    
    public function __toString()
    {
        return "rel:$this->name:$this->type:$this->model:$this->foreignKey-$this->localKey";
    }
}
