<?php

namespace Square1\Laravel\Connect\Traits;

use Exception;

trait ConnectApiRequestTrait
{
    public function getAssociatedModel()
    {
        throw new Exception("associatedModel is not set for this request");
    }
    
    /**
     * returning this to true automaticall adds   the following parameters to the request  'page' => 'integer','per_page' => 'integer',
     *
     * @return boolean
     */
  
    public function getIsPaginated()
    {
        return false;
    }

    /**
     * return an array with the parameters for this request 
     *
     * @return array
     */
    public  function parameters()
    {
        $params = array();
        $rules = $this->rules();

        if(isset($this->params)) {
            $params = array_merge($rules, $this->params); 
        }
        else 
        {
            $params = array_merge($rules, $params);  
        }
        return $params;
    }
}
