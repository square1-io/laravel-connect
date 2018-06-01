<?php

namespace Square1\Laravel\Connect\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;


class InternalConnectScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $model->restrictModelAccessInternal($builder, $model);
    }
}

trait ConnectModelTrait
{

     static $modelRestrictionEnabled = true;
    /**
     * Boot the global scope  trait for a model.
     *
     * @return void
     */
    public static function bootConnectModelTrait()
    {
        static::$modelRestrictionEnabled = true;
        static::addGlobalScope(new InternalConnectScope);
    }
    
    /**
     * Get a type hint for the given attribute .
     *
     * @param string $name the name of the attribute
     *
     * @return string
     */
    public function getTypeHint($name)
    {
        if (isset($this->hint) && isset($this->hint[$name])) {
            return $this->hint[$name];
        }
        return null;
    }
    
    /**
     * Undocumented function
     *
     * @return void
     */

    public function endpointReference()
    {
        return Str::snake(class_basename(get_class($this)), "_");
    }

    /**
     * Override in each model to control access to model
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    function restrictModelAccessInternal(Builder $builder, $model) 
    {
        if (static::$modelRestrictionEnabled == true) 
        {
            $this->restrictModelAccess($builder, $model);
        }
    }

    /**
     * Override in each model to control access to model
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function restrictModelAccess(Builder $builder, $model) 
    {

    }

    public static function disableModelAccessRestrictions()
    {
        static::$modelRestrictionEnabled = false;
    }

    public static function enableModelAccessRestrictions()
    {
        static::$modelRestrictionEnabled = true;
    }
    /**
     * Undocumented function
     *
     * @param [type] $parent
     * @return void
     */

    public function withRelations($parent = null)
    {
        $with_array = isset($this->with_relations) ? $this->with_relations : [];
        
        if (empty($parent) == false) {
            $callback = function ($value) use ($parent) {
                return $parent.'.'.$value;
            };
            
            $with_array = array_map($callback, $with_array);
        }
      
        return $this::with($with_array);
    }
    
    /**
     * Undocumented function
     *
     * @param [type] $query
     * @param [type] $sort_by
     * @return void
     */

    public function scopeOrder($query, $sort_by)
    {
        foreach ($sort_by as $paramName => $sort) {
            $query = $query->orderBy($paramName, $sort);
        }
        
        return $query;
    }
    
    
    /**
     * Scope a query to filter based on the filter array received.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeFilter($query, $filter = null)
    {
        if (isset($filter)) {
            return $filter->apply($query, $this);
        }
    }


    /**
     * Return a Relation given a name , false if the name doesn't match any defined
     * relation
     *
     * @param String $relationName
     * @return Relation or false
     */
    public function getRelationWithName(&$relationName)
    {
        //if ($this::class->snakeAttributes == true) {
        $relationName = camel_case($relationName);
        // }
      
        if (!method_exists($this, $relationName)) {
            return false;
        }

        $relation = $this->$relationName();

        if ($relation instanceof Relation) {
            return $relation;
        }

        return false;
    }

    public function getRelationTableWithName(&$relationName)
    {
        $relation = $this->getRelationWithName($relationName);
    
        if ($relation instanceof Relation) {
            return $relation->getRelated()->getTable();
        }

        return false;
    }
}
