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
class RelationHasManyThrough extends Relation
{

        /**
         * The "through" parent model instance.
         *
         * @var \Illuminate\Database\Eloquent\Model
         */
    protected $throughParent;

    /**
     * The far parent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $farParent;

    /**
     * The near key on the relationship.
     *
     * @var string
     */
    protected $firstKey;

    /**
     * The far key on the relationship.
     *
     * @var string
     */
    protected $secondKey;

    /**
     * The local key on the relationship.
     *
     * @var string
     */
    protected $localKey;
    
    public function __construct($related, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $relationName)
    {
        $this->localKey = $localKey;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;
        $this->throughParent = $throughParent;

        parent::__construct($related, $throughParent, $relationName);
    }
    
    public function relatesToMany()
    {
        return true;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['localKey'] = $this->localKey;
        $array['firstKey'] = $this->firstKey;
        $array['secondKey'] = $this->secondKey;
        $array['farParent'] = $this->farParent;
        $array['throughParent'] = $this->throughParent;
         
        return $array;
    }
}
