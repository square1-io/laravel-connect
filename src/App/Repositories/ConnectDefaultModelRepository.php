<?php

namespace Square1\Laravel\Connect\App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Square1\Laravel\Connect\ConnectUtils;
use Square1\Laravel\Connect\App\Filters\Filter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ConnectDefaultModelRepository implements ConnectRepository
{
    protected $model;

    public function __construct($model)
    {
        $this->model = new $model;
    }
    /**
     * Get a paginated list of all the instances of the current model.
     *
     * @param array $with    Eager load models
     * @param int   $perPage set the number of elemets per page
     * @param array $filter  the array representation of a Filter object
     * @param array $sort_by a list of sorting preferences
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function index($with, $perPage, $filter, $sort_by)
    {
        $filter =  Filter::buildFromArray($this->model, $filter);
          
        return $this->model->with($with)->filter($filter)
            ->order($sort_by)
            ->paginate(intval($perPage));
    }
    
    /**
     *  Get a paginated list of all the instances of the current related model(s).
     *  This treats in the same toMany and toOne relations, a collection will be returned in all cases.
     *
     * @param int    $parentId     the id of the parent model
     * @param String $relationName the name of the relationship to fetch
     * @param array  $with         Eager load models
     * @param int    $perPage      set the number of elemets per page
     * @param array  $filter       the array representation of a Filter object
     * @param array  $sort_by      a list of sorting preferences
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function indexRelation($parentId, $relationName, $with, $perPage, $filter, $sort_by = [])
    {
        $model = $this->model;

        $relation = ConnectUtils::validateRelation($model, $relationName);
       
        if (!$relation) {
            return [];
        }

        $model = $model::find($parentId);
   
        $relation = $model->$relationName();
        $relatedModel = $relation->getRelated();
        $filter = Filter::buildFromArray($relatedModel, $filter);

        //those 1 relations don't need to be paginated
        if ($relation instanceof HasOne || $relation instanceof BelongsTo) {

            return $relation
                    ->with($with)
                    ->paginate(1)
                    ->first();
        }

        return $relation->with($with)
                ->filter($filter)
                ->order($sort_by)
                ->paginate(intval($perPage));
    }
    
    public function show($id, $with = [])
    {
        return $this->model
            ->with($with)
            ->where('id', $id)
            ->get()
            ->first();
    }
    
    
    public function showRelation($parentId, $relationName, $relId, $with)
    {
        $model = $this->model;

        $relation = ConnectUtils::validateRelation($model, $relationName);
        
        if (!$relation) {
            return null;
        }

        $model = $model::find($parentId);
        
        return $model->$relationName()->with($with)->where('id', $relId)->get()->first();
    }



    /**
     * Creates a model.
     *
     * @param array $params The model fields
     *
     * @return Model
     */
    public function create($params)
    {

        foreach ($params as $param => $value) {
            if ($value instanceof UploadedFile) {
                $params[$param] = $this->storeUploadedFile($value);
            }
        }
        
        $model = $this->model->create($params);

        return $model;
    }

    /**
     * Updates a model. The received params are a key value dictionary containing 3 types of values.
     * 1) Assignable parameters, stanrdard parameters like String or Integer that can be set directly
     * 2) UploadedFile, those are first stored calling the storeUploadedFile and then a String pointer is saved in the model
     * 3) a "relations" key value array, containing a map of the relationships to be updated. This is keyed with the name of the relationship
     *  and an array named add with the list of models to add and remove with a list of models to remove.
     *  relations[relation1][add]= [id1, id2, id3], relations[relation1][remove]= [id4, id5, id6]
     * 
     * @param int   $id     The model's ID
     * @param array $params The model fields a key value array of parameters to be updated
     *
     * @return Models\Model the updated model
     */
    public function update($id, $params)
    {
        $model = $this->model->where('id', $id)->get()->first();
        
        foreach ($params as $param => $value) {
            if ($value instanceof UploadedFile) {
                $params[$param] = $this->storeUploadedFile($value);
            }
        }

        $relations = array_get($params, "relations", []);
       
        $updatedRelations = [];

        foreach($relations as $relation => $data) {
            $relationAdd = array_get($data, "add", []);
            $relationRemove = array_get($data, "remove", []);

            if (ConnectUtils::updateRelationOnModel($model, $relation, $relationAdd, $relationRemove) == true) {
                $updatedRelations[] = $relation;
               
            }
        }

        //remove relations values before assigning to model as those are not part of the fillable values
        unset ($params["relations"]);

        $model->forceFill($params);
        $model->push();

        return $this->show($id, $updatedRelations);
    }
    
    public function updateRelation($parentId, $relationName, $relationData)
    {
        $model = $this->model;

        $relation = ConnectUtils::validateRelation($model, $relationName);
        
        if (!$relation) {
            return null;
        }
        

        $model = $model::find($parentId);
        $relation = $model->$relationName();
        $relatedModel = $relation->getRelated();
        $related = 0;
       
        
        if (is_array($relationData)) {
            //need to create a new instance of the related
            $repository = ConnectUtils::repositoryInstanceForModelPath($relatedModel->endpointReference());
            $related = $repository->create($relationData);
        } elseif ($relationData instanceof Model) {
            $related = $relationData;
        } else {
            $related = $relatedModel::find($relationData);
        }
  
        if ($relation instanceof HasOne) {
            $relation->associate($related);
        } elseif ($relation instanceof BelongsToMany) {
            $relation->attach($related);
        } elseif ($relation instanceof HasMany) {
            $relation->save($related);
        }
        //        else if($relation instanceof MorphTo){
        //
        //        }
        //        else if($relation instanceof MorphOne){
        //
        //        }
        //        else if($relation instanceof MorphMany){
        //
        //        }
        //        else if($relation instanceof MorphToMany){
        //
        //        }
        //        else if($relation instanceof HasManyThrough){
        //
        //        }

        $model->save();
        
        return $related;
    }
    
  
    public function deleteRelation($parentId, $relationName, $relId)
    {
        $model = $this->model;

        $relation = ConnectUtils::validateRelation($model, $relationName);
        
        if (!$relation) {
            return null;
        }

        $model = $model::find($parentId);
        $relation = $model->$relationName();

        if ($relation instanceof HasOne) {
            $relation->dissociate();
        } elseif ($relation instanceof HasMany) {
            $relationModel = $relation->findOrNew($relId);
            $relationModel = $relationModel::find($relId);
   
            //cant remove this only assign to another one if strict relations
            $relationModel->setAttribute($relation->getForeignKeyName(), 0);
            $relationModel->save();
        }
        //        else if($relation instanceof MorphTo){
        //
        //        }
        //        else if($relation instanceof MorphOne){
        //
        //        }
        //        else if($relation instanceof MorphMany){
        //
        //        }
        //        else if($relation instanceof MorphToMany){
        //
        //        }
        //        else if($relation instanceof HasManyThrough){
        //
        //        }
        elseif ($relation instanceof BelongsTo) {
            $relation->dissociate();
        } elseif ($relation instanceof BelongsToMany) {
            $relation->detach($relId);
        }
        
        $model->touch();
        $model->save();
        
        return $model->withRelations()->get()->first();
    }

    /**
     * Deletes a model.
     *
     * @param int $id The model's ID
     *
     * @return bool
     */
    public function delete($id)
    {
        $model = $this->model->findOrFail($id);

        return $model->delete();
    }

    /**
     * Restores a previously deleted model.
     *
     * @param int $id The model's ID
     *
     * @return Model
     */
    public function restore($id)
    {
        $model = $this->model->withTrashed()->findOrFail($id);
    
        return $model->restore();
    }

    /**
     * Get a new instance of model.
     *
     * @return Model
     */
    public function getNewModel()
    {
        return new $this->model();
    }


    /**
     *  Store uploaded files in the defined storage.
     *  Place then in a subfolder named as the endpoint reference for the model
     *
     * @param UploadedFile $file, the uploaded file
     *
     * @return String an appropriate representation of the location where the file was stored
     */
    public function storeUploadedFile($file)
    {
        return Storage::putFile($this->model->endpointReference(), $file);
    }

}
