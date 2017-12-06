<?php

namespace Square1\Laravel\Connect\Model\Relation;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RelationHasOne
 *
 * @author roberto
 */
class RelationBelongsToMany extends Relation
{

    /**
     * The intermediate table for the relation.
     *
     * @var string
     */
    protected $table;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key of the relation.
     *
     * @var string
     */
    protected $relatedKey;



    /**
     * Create a new belongs to many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return void
     */
    public function __construct($related, $parent, $table, $foreignKey, $relatedKey, $relationName = null)
    {
        $this->table = $table;
        $this->relatedKey = $relatedKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($related, $parent, $relationName);
    }
    
    public function toArray()
    {
        $array = parent::toArray();
        
        $array['table'] = $this->table;
        $array['relatedKey'] = $this->relatedKey;
        $array['foreignKey'] = $this->foreignKey;
        
         
        return $array;
    }
    
    public function relatesToMany()
    {
        return true;
    }
}
