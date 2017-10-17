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
     * @return boolean
     */
  
    public function getIsPaginated()
    {
        return false;
    }
}
