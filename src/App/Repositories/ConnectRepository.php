<?php

namespace Square1\Laravel\Connect\App\Repositories;

interface ConnectRepository
{
    /**
     * list the models and paginates them.
     *
     * @param array $with Eager load models
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function index($with, $perPage, $filter, $sort_by);

    /**
     * Get all the models.
     *
     * @param array $with Eager load models
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function indexRelation($parentId, $relationName, $with, $perPage, $filter, $sort_by);
    

     /*
      * returns the model instance give the id

      */
    public function show($id, $with);

     
    public function showRelation($parentId, $relationName, $relId, $with);
     
    /**
     * Creates a model.
     *
     * @param array $params The model fields
     *
     * @return VillagePod\Models\Model
     */
    public function create($params);

    /**
     * Updates a model.
     *
     * @param int   $id     The model's ID
     * @param array $params The model fields
     *
     * @return Models\Model
     */
    public function update($id, $params);

    
    public function updateRelation($parentId, $relationName, $relationData);
    /**
     * Deletes a model.
     *
     * @param int $id The model's ID
     *
     * @return bool
     */
    public function delete($id);

    /**
     * Restores a previously deleted model.
     *
     * @param int $id The model's ID
     *
     * @return VillagePod\Models\Model
     */
    public function restore($id);

    /**
     * Get a new instance of model.
     *
     * @return VillagePod\Models\Model
     */
    public function getNewModel();

 
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
    public function pagination($page = 0, $limit = 5);


     /*
     *  process uploaded files and based on the Model
     *  returs an appropriate form of the file
     */
    public function storeUploadedFile($file);
    
}
