<?php

namespace Square1\Laravel\Connect;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\Relation;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use Laravel\Passport\TokenRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Square1\Laravel\Connect\Traits\InternalConnectScope;

class ConnectUtils
{
    public static function repositoryInstanceForModelPath($modelReference)
    {
        $repositoryClass = config("connect_auto_generated.endpoints.".$modelReference);
       
        if (!empty($repositoryClass)) {
            return app()->make($repositoryClass);
        }
       
        return null;
    }
    
    
    public static function getUserForTokenString($tokenString)
    {
        $jwtParser = new \Lcobucci\JWT\Parser();
        $jwtToken = $jwtParser->parse($tokenString);
        $tokenId = $jwtToken->getHeader('jti');
        
        if (!empty($tokenId)) {
            $tokenRepo = new TokenRepository();
            $eloquentToken = $tokenRepo->find($tokenId);
            
            if (isset($eloquentToken)) {
                //resolve the user now
                $authClass = config('connect.api.auth.model');
                $authModel = new $authClass;
                $repository = static::repositoryInstanceForModelPath($authModel->endpointReference());
                
                $authModel::disableModelAccessRestrictions();
                $user = $repository->show($eloquentToken->user_id);
                $authModel::enableModelAccessRestrictions();

                return $user;
            }
        
            return null;
        }
    }
    
    /**
     * Parse token from the authorization header.
     *
     * @param string $header
     * @param string $method
     *
     * @return false|string
     */
    private static function parseAuthHeader($request, $header = 'authorization', $method = 'bearer')
    {
        $header = $request->headers->all();
        $header = $header['authorization'];
        
        if (is_array($header)) {
            $header = $header[0];
        }
        
        if (! starts_with(strtolower($header), $method)) {
            return false;
        }

        return trim(str_ireplace($method, '', $header));
    }
    
    public static function currentAuthUser($request)
    {
        $currentUser = null;
        
        $tokenString = static::parseAuthHeader($request);
        $currentUser = static::getUserForTokenString($tokenString);
        
        
        return $currentUser;
    }

    public static function formatResponseData($data){
        
        if($data instanceof LengthAwarePaginator) {

            $data = $data->toArray();

            $result = [
                'items' => $data['data'],
                'pagination' =>[
                    'current_page' => $data['current_page'],
                    'last_page' => $data['last_page'],
                    'per_page' => $data['per_page'],
                    'total' => $data['total']
                ]
            ];

            return $result;
        }

        return $data;
    }


    /**
     *  If the Model has a relation with name $relation will return an instance of Relation or false otherwise.
     *
     * @param Model $model the model 
     * @param String $relation the name of the relation
     * @return Relation an instance of the subclass relation or false 
     */
    public static function validateRelation($model, $relation)
    {
                    
        if (!method_exists($model, $relation)) {
            
            return false;
        }

        $relation = $model->$relation();
       
        //prevent calling other methods on the model
        if ($relation instanceof Relation) {
            return $relation;
        }

        return false;
    }

    /**
     * Update a relation on the given model adding the models defined in $add and removing the relation models in $remove
     *
     * @param Model $model
     * @param String $relationName
     * @param mixed $add, Model or array of primary keys or of arrays with data for new model instances
     * @param mixed $remove  Model or array of primary keys 
     * @return BOOL if any change was applied to the model
     */
    public static function updateRelationOnModel($model, $relationName, $add, $remove){

        $relation = ConnectUtils::validateRelation($model, $relationName);
   
        $anyChanges = FALSE;

        if(!$relation){
            return $anyChanges;
        }


        //TODO work on morph relations

        //the model class related to this model
        $relatedModel = $relation->getRelated();

        //first we need to figure out what we have received in $add
        $modelsToAdd = ConnectUtils::modelInstancesFromData($relatedModel, $add);
       
        //is there anything to be added to this relation 
        if(!empty($modelsToAdd)) {

            $anyChanges = TRUE;

            //add to the relation
        if ($relation instanceof MorphTo || 
            $relation instanceof MorphOne || 
            $relation instanceof MorphMany || 
            $relation instanceof MorphToMany) {
                //TODO support morph Relations
        }
        else  if ($relation instanceof HasOne ||  // HasOne is a subclass of  HasOneOrMany can remove
                 $relation instanceof HasOneOrMany || 
                 $relation instanceof HasMany) {
            $relation->saveMany($modelsToAdd);
         } 
         elseif ($relation instanceof BelongsTo) {
             $relation->associate($modelsToAdd[0]);
         }
         elseif ($relation instanceof BelongsToMany) {
            //using this instead of saveMany prevents multiple associations in the table
            // and avoids any conflict with unique keys
             $ids = array_map(function($o) { return $o->getKey(); }, $modelsToAdd);
             $relation->syncWithoutDetaching($ids);
             //$relation->saveMany($modelsToAdd);
         } elseif ($relation instanceof HasManyThrough) {
             //nothing to do here 
         }

        }

        //we need to figure out what we need to remove
        $modelsToRemove = ConnectUtils::modelInstancesFromData($relatedModel, $remove);
      
        //is there anything to be removed from  this relation 
        if(!empty($modelsToRemove)) {
            
            $anyChanges = TRUE;

            //remove from the relation
        if ($relation instanceof MorphTo || 
            $relation instanceof MorphOne || 
            $relation instanceof MorphMany || 
            $relation instanceof MorphToMany) {
                //TODO support morph
        }
        else  if ($relation instanceof HasOne ||
                 $relation instanceof HasOneOrMany || 
                 $relation instanceof HasMany) { 
                //TODO deal with this
                //$relation->saveMany($modelsToRemove);

         } 
         elseif ($relation instanceof BelongsTo) {
             $relation->associate($modelsToRemove);
         }
         elseif ($relation instanceof BelongsToMany) {
             //pull out the model ids and detatch them from the relation
             $ids = array_map(function($o) { return $o->getKey(); }, $modelsToRemove);
             $relation->detach($ids);

         } elseif ($relation instanceof HasManyThrough) {
             //nothing to do here 
         }

        }

        return  $anyChanges;
    }

    /**
     * Undocumented function
     *
     * @param Class $modelClass extending Model 
     * @param mixed $data, array of primaryKeys or of arraydata to create new model instances
     * @return void
     */
    private static function modelInstancesFromData($modelClass, $data) {
        
        //first of all is there anything in data ? 
        if(empty($data)) {
            return [];
        }

        $repository = ConnectUtils::repositoryInstanceForModelPath($modelClass->endpointReference());
       
        //is thre a repository for this model ? 
        if($repository == NULL) {
            return [];
        }

        if($data instanceof $modelClass){
            return [$data];
        }

        $models = [];
     
        if(is_array($data)) {
            //we have an array, it is an array of primary keys or an array of arrays with values we need to create a model instance for ? 
            foreach ($data as $modelData){

                $model = NULL;

                // if data is an array of arrays we presume we need to construct new models with the data in each element of the array
                if(is_array($modelData)){
                    $model = $repository->create($modelData);
                //if it is not an array we presume it is a primary ket for the model
                }else {
                    $model = $modelClass::find($modelData);
                }

                // do we have a model then ???
                if($model){
                    $models[] = $model;
                }
            }
           
        }else { // data is not an array, the only other option is that we have a primaryKey
           
            $model = $modelClass::find($data); 
           
            if($model){
                $models[] = $model;
            } 
        }
        
        return $models;

    }

}
