<?php

namespace Square1\Laravel\Connect\Traits;

use Illuminate\Support\Str;

trait ConnectModelTrait
{
    
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
    
    public function endpointReference()
    {
        return Str::snake(class_basename(get_class($this)), "_");
    }
    
    
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
}
