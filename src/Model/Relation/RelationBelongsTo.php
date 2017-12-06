<?php

namespace Square1\Laravel\Connect\Model\Relation;

/**
 * Description of RelationBelongsTo
 *
 * @author roberto
 */
class RelationBelongsTo extends Relation
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
    
    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected $ownerKey;

   
    
        /**
     * Create a new belongs to relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @return void
     */
    public function __construct($related, $child, $foreignKey, $ownerKey, $relationName)
    {
        $this->ownerKey = $ownerKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($related, $child, $relationName);
    }
    
    
    public function toArray()
    {
        $array = parent::toArray();
        
        $array['ownerKey'] = $this->ownerKey;
        $array['foreignKey'] = $this->foreignKey;
         
        return $array;
    }
    
    public function relatesToMany()
    {
        return false;
    }
}
