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
class RelationHasOne extends Relation
{

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;
    
    public function __construct($related, $parent, $foreignKey, $localKey, $relationName)
    {
        parent::__construct($related, $parent, $relationName);
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
    }
    
    public function toArray()
    {
        $array = parent::toArray();

        $array['localKey'] = $this->localKey;
        $array['foreignKey'] = $this->foreignKey;
         
        return $array;
    }
}
