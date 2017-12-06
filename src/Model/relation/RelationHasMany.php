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
class RelationHasMany extends RelationHasOne
{
    public function relatesToMany()
    {
        return true;
    }
}
