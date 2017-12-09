<?php

namespace Square1\Laravel\Connect\App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Square1\Laravel\Connect\ConnectUtils;
use Square1\Laravel\Connect\App\Filters\FilterManager;
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
     * @param array $with Eager load models
     * @param int $perPage set the number of elemets per page
     * @param array $filter the array representation of a FilterManager object
     * @param array $sort_by a list of sorting preferences
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function index($with, $perPage, $filter, $sort_by)
    {
        $filter =  FilterManager::buildFromArray($this->model, $filter);
          
        return $this->model->with($with)->filter($filter)
                ->order($sort_by)
                ->paginate(intval($perPage));
    }
    
    /**
     *  Get a paginated list of all the instances of the current related model(s).
     *  This treats in the same toMany and toOne relations, a collection will be returned in all cases.
     *
     * @param int $parentId the id of the parent model
     * @param String $relationName the name of the relationship to fetch
     * @param array $with Eager load models
     * @param int $perPage set the number of elemets per page
     * @param array $filter the array representation of a FilterManager object
     * @param array $sort_by a list of sorting preferences
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function indexRelation($parentId, $relationName, $with, $perPage, $filter, $sort_by = [])
    {
        $model = $this->model;
        $relation = $model->$relationName();
        
        //prevent calling other methods on the model
        if ($relation instanceof Relation) {
            $model = $model::find($parentId);
   
            $relation = $model->$relationName();
            $relatedModel = $relation->getRelated();
            $filter = FilterManager::buildFromArray($relatedModel, $filter);
             
            return $relation->with($with)
                    ->filter($filter)
                    ->order($sort_by)
                    ->paginate(intval($perPage));
        }
        return null;
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
        $this->checkCanCreate($params);
        
        foreach ($params as $param => $value) {
            if ($value instanceof UploadedFile) {
                $params[$param] = $this->storeUploadedFile($value);
            }
        }
        
        $model = $this->model->create($params);

        return $model;
    }

    /**
     * Updates a model.
     *
     * @param int   $id     The model's ID
     * @param array $params The model fields
     *
     * @return VillagePod\Models\Model
     */
    public function update($id, $params)
    {
        $model = $this->model->where('id', $id)->get()->first();
        
        foreach ($params as $param => $value) {
            if ($value instanceof UploadedFile) {
                $params[$param] = $this->storeUploadedFile($value);
            }
        }
        
        $model->forceFill($params);
        $model->touch();
        return $model->withRelations()->get()->first();
    }
    
    public function updateRelation($parentId, $relationName, $relationData)
    {
        $model = $this->model;
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
        $this->checkCanDelete($model);

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
        $this->checkCanDelete($model);

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
     * Gets the model paginated.
     *
     * @param int $page  Page to show
     * @param int $limit Items per page
     *
     * @return array Array with the result
     *               - result: Array with the result
     *               - total: Total of items
     *               - page:   Current page
     *               - pages: Total of pages
     */
    public function pagination($page = 0, $limit = 5)
    {
        return $this->paginate($this->model, $page, $limit);
    }

    /**
     * Checks if the user is allowed to see an instance of this model.
     *
     * @param VillagePod\Models\Model $model
     *
     * @throws VillagePod\ApiException
     */
    protected function checkCanShow($model)
    {
        if (!$this->canShow($model)) {
            abort(403);
        }
    }

    /**
     * Checks if the user is allowed to create an instance of this model.
     *
     * @throws
     */
    protected function checkCanCreate($params)
    {
        if (!$this->canCreate($params)) {
            abort(403);
        }
    }

    /**
     * Checks if the user is allowed to update an instance of this model.
     *
     * @param VillagePod\Models\Model $model
     * @param array                   $newValues New Values
     *
     * @throws VillagePod\ApiException
     */
    protected function checkCanUpdate($model, $newValues)
    {
        if (!$this->canUpdate($model, $newValues)) {
            abort(403);
        }
    }

    /**
     * Checks if the user is allowed to delete an instance of this model.
     *
     * @param array $params Parameters of the new model
     *
     * @throws VillagePod\ApiException
     */
    protected function checkCanDelete($params)
    {
        if (!$this->canDelete($params)) {
            abort(403);
        }
    }

    /**
     * Determines if the user is allowed to see an instance of this model.
     *
     * @param VillagePod\Models\Model $model
     *
     * @return bool whether the user is allowed or not
     */
    protected function canShow($model)
    {
        return true;
    }

    /**
     * Determines if the user is allowed to create an instance of this model.
     *
     * @param array $params Parameters of the new instance
     *
     * @return bool whether the user is allowed or not
     */
    protected function canCreate($params)
    {
        return true;
    }

    /**
     * Determines if the user is allowed to update an instance of this model.
     *
     * @param VillagePod\Models\Model $model
     * @param array                   $newValues New Values
     *
     * @return bool whether the user is allowed or not
     */
    protected function canUpdate($model, $newValues)
    {
        return true;
    }

    /**
     * Determines if the user is allowed to delete an instance of this model.
     *
     * @param Model $model
     *
     * @return bool whether the user is allowed or not
     */
    protected function canDelete($model)
    {
        return true;
    }

    /**
     *  Store uploaded files in the defined storage.
     *  Place then in a subfolder named as the endpoint reference for the model
     *
     *  @param UploadedFile $file, the uploaded file
     *
     *  @return String an appropriate representation of the location where the file was stored
     *
     */
     public function storeUploadedFile($file)
     {
         return Storage::putFile($this->model->endpointReference(), $file);
     }
}
