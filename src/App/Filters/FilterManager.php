<?php

/**
 *  FilterManager
 *
 * @author roberto
 */

namespace Square1\Laravel\Connect\App\Filters;

use Illuminate\Contracts\Support\Arrayable;
use Square1\Laravel\Connect\App\Filters\Filter;
use Square1\Laravel\Connect\App\Filters\Criteria;

class FilterManager implements Arrayable
{
    private $filters;
   
    /**
     * The Laravel model on which to apply those filters
     *
     * @var type Model
     */
    private $model;

    private $request;

    public function __construct($model)
    {
        $this->model = $model;
        $this->filters = [];
    }
    
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }
    

    public function apply($query, $model)
    {
        $first = true;
        
        foreach ($this->filters as $filter) {
            if ($first == true) {
                $query->where(
                    function ($q) use ($filter, $model) {
                        $filter->apply($q, $model);
                    }
                );
            } else {// apply the OR clause
                $query->orWhere(
                    function ($q) use ($filter, $model) {
                        $filter->apply($q, $model);
                    }
                );
            }
            
            $first = false;
        }
    
        return $query;
    }
    
    
   
    
    /**
              "filter[0][medias.event_id][equal][0]": eventId,
              "filter[0][medias.event_id][equal][1]": 5,
              "filter[0][id][equal][1]": 52323,
              "filter[1][medias.event_id][equal][0]": 666,
              "filter[1][medias.event_id][equal][1]": 666,
              "filter[1][id][equal][1]": 52323
     *
     * @param  type $model
     * @param  type $array
     * @return \Square1\Laravel\Connect\App\Filters\FilterManager
     */
    
    public static function buildFromArray($model, $array = [])
    {
        $filterManager = new FilterManager($model);
        $filterManager->request = $array;
   
        
        foreach ($array as $filterData) {//array containing a filter
            $filter = static::buildFilterFromArray($filterData);
            
            if (isset($filter)) {
                $filterManager->addFilter($filter);
            }
        }
        
        return $filterManager;
    }
    
    public static function buildFilterFromArray($filterData)
    {
        if (!is_array($filterData)) {
            return null;
        }
        
        $filter = new Filter();
        
        foreach ($filterData as $paramName => $criterias) {
            foreach ($criterias as $verb => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $criteria = new Criteria($paramName, $v, $verb);
                        $filter->addCriteria($criteria);
                    }
                } else {
                    $criteria = new Criteria($paramName, $value, $verb);
                    $filter->addCriteria($criteria);
                }
            }
        }
        
        return $filter;
    }

    
    public function toArray()
    {
        $result = [];
        
        foreach ($this->filters as $filter) {
            $result[] = $filter->toArray();
        }
       
        
        
        return ['orig' => $this->request, 'parsed'=> $result ];
    }
}
