<?php
/**
 *  Criteria
 *
 * @author roberto
 */

namespace Square1\Laravel\Connect\App\Filters;

use Illuminate\Support\Str;

class Criteria
{
   
    const CONTAINS = "contains";
    const EQUAL = "equal";
    const NOTEQUAL = "notequal";
    const GREATERTHAN = "greaterthan"; 
    const LOWERTHAN = "lowerthan";
    const GREATERTHANOREQUAL = "greaterthanorequal";
    const LOWERTHANOREQUAL = "lowerthanorequal";


    private $relation;
    
    private $param;
    
    private $name;
    
    private $value;
    
    private $verb;
    

    public function __construct($name, $value, $verb) 
    {
        
        $this->name = $name;
        
        $exploded = explode('.', $name);
        
        if(count($exploded) == 2) {
            $this->relation = $exploded[0]; 
            $this->param = $exploded[1];
        }
        else
        {
            $this->relation = '';
            $this->param = $exploded[0];
        }

        $this->value = $value;
        $this->verb = Str::lower($verb);
        
    }
    
    public function onRelation()
    {
        return strlen($this->relation) > 0;
    }

    public function relation()
    {
        return $this->relation;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function verb()
    {
        return $this->verb;
    }
    
    public function value()
    {
        return $this->value;
    }    
    
    public function apply($query, $table = '')
    {
        
        if(strlen($table) > 0) {
            $name = $table.'.'.$this->param;
             
        }else
         {
            $name = $this->param;
        }
       
        if ($this->verb === "contains") {
            $query->where($name, 'like', '%' . $this->value . '%');
        } elseif ($this->verb === "equal") {
            $query->where($name, $this->value);
        } elseif ($this->verb === "notequal") {
            $query->where($name, "!=", $this->value);
        } elseif ($this->verb === "greaterthan") {
            $query->where($name, ">", $this->value);
        } elseif ($this->verb === "lowerthan") {
            $query->where($name, "<", $this->value);
        } elseif ($this->verb === "greaterthanorequal") {
            $query->where($name, "=>", $this->value);
        } elseif ($this->verb === "lowerthanorequal") {
            $query->where($name, "<=", $this->value);
        }

        return $query;
    }
    
    
}
