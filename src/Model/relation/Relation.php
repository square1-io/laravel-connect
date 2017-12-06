<?php

namespace Square1\Laravel\Connect\Model\Relation;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Relation implements Jsonable, Arrayable
{
    
    /**
     * The parent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $parent;

    /**
     * The related model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $related;
    
     /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relationName;
    
   
    /**
     * Create a new relation instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return void
     */
    public function __construct($related, $parent, $relationName)
    {
        $this->relationName = $relationName;
        $this->parent = $parent;
        $this->related = $related;
    }
    
    
      /**
     * indicates if this relation points to one or more related model instances
     *
     * @var type boolean
     */
    
    public function relatesToMany()
    {
        return false;
    }

    
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        $array['name'] = $this->relationName;
        $array['type'] = $this->get_class_name($this);
        $array['parent'] = get_class($this->parent);
        $array['related'] = get_class($this->related);
        $array['many'] = $this->relatesToMany();
        return $array;
    }
    
    public function __call($method, $parameters)
    {
        return $this;
    }
    
    public function get_class_name($object = null)
    {
        if (!is_object($object) && !is_string($object)) {
            return false;
        }

        $class = explode('\\', (is_string($object) ? $object : get_class($object)));
        return $class[count($class) - 1];
    }
}
