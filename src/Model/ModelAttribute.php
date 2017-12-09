<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Square1\Laravel\Connect\Model;

use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Support\Fluent;

class ModelAttribute
{
    const TYPE_INT = 'int';
    const TYPE_DOUBLE = 'double';
    const TYPE_BOOL = 'bool';
    const TYPE_STRING = 'string';
    const TYPE_TIME_STAMP = 'time_stamp';
    
    //    public $type;
    //    public $name;
    //    public $references;
    //    public $on;
    //    public $update;
    //    public $allowed;
    //
    //    public $collection;
    //
    //    public $dynamic;

    public $fluent;
    
    // public $foreignKey;
    
    
    
    public function __construct(Fluent $fluent)
    {
        $this->fluent = $fluent;
        //        $this->name = $name;
        //        $this->type = $type;
        //        $this->references = $references;
        //        $this->on = $on;
        //        $this->update = $isUpdate;
        //        $this->collection = FALSE;
        //        $this->dynamic = FALSE;
    }
    
    

    public function __get($key)
    {
        return $this->fluent[$key];
    }
    

    public function __set($key, $value)
    {
        $this->fluent[$key] = $value;
    }
    
    public function primaryKey()
    {
        return !empty($this->fluent) &&
        !empty($this->fluent->autoIncrement) &&
        $this->fluent->autoIncrement;
    }


    public function isRelation()
    {
        return !empty($this->on) && !
                empty($this->references);
    }
    
    public function __toString()
    {
        if (!empty($this->on) && !            empty($this->references)
        ) {
            return "att:$this->name:ref:$this->references:on:$this->on";
        }
        return "att:$this->name:$this->type";
    }
    
    public function dummyData()
    {
        if (isset($this->allowed)) {
            if (is_array($this->allowed)) {
                return $this->allowed[0];
            }
            
            return $this->allowed;
        }
        
        switch ($this->type) {
        case 'boolean':
            return true;
        case 'double':
        case 'float':
            return 1.1;
        case 'tinyint':
        case 'integer':
            return 1;
        case 'text':
        case 'string':
            return "string";
        case 'date':
        case 'dateTime':
        case 'timestamp':
            return Carbon::now(new DateTimeZone('Europe/London'));
        }
        
        if ($this->type == null) {
            return 'string';
        }
        
        if ($this->type == 'enum') {
            return $this->fluent['allowed'][0];
        }
        
        return new $this->type;
    }
}
