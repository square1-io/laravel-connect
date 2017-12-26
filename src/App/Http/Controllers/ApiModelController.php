<?php

namespace Square1\Laravel\Connect\App\Http\Controllers;

use Illuminate\Http\Request;
use Square1\Laravel\Connect\ConnectUtils;

class ApiModelController extends ConnectBaseController
{
    private $repository;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $route = $request->route();

        if (isset($route)) {
            $modelReference = $route->parameter('model');
            $this->repository = ConnectUtils::repositoryInstanceForModelPath($modelReference);
            if (empty($this->repository)) {
                //TODO handle this  gracefully
                abort(404);
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {

            $params = $request->all();
            
            $perPage = array_get($params, 'per_page', 15);
            $filter = array_get($params, 'filter', []);
            $sortBy = array_get($params, 'sort_by', []);

            $with = array_get($params, 'include', '');

            if (!empty($with)) {
                $with = explode(',', $with);
            } else {
                $with = [];
            }

            $data = $this->repository->index($with, $perPage, $filter, $sortBy);

            return response()->connect($data);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexRelation(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $params = $request->all();

            $perPage = array_get($params, 'per_page', 15);
            $filter = array_get($params, 'filter', []);
            $sort_by = array_get($params, 'sort_by', []);

            $with = array_get($params, 'include', '');

            if (!empty($with)) {
                $with = explode(',', $with);
            } else {
                $with = [];
            }

            $parentId = $request->route()->parameter('id');
            $relationName = $request->route()->parameter('relation');

            $data = $this->repository->indexRelation($parentId, $relationName, $with, $perPage, $filter, $sort_by);

            return response()->connect($data);
        });
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $params = $request->all();
        $data = $this->repository->create($params);

        return response()->connect($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($model, $id, Request $request)
    {
        $params = $request->all();

        $with = array_get($params, 'include', '');

        if (!empty($with)) {
            $with = explode(',', $with);
        } else {
            $with = [];
        }

        $data = $this->repository->show($id, $with);

        return response()->connect($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function showRelation(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $parentId = $request->route()->parameter('id');
            $relId = $request->route()->parameter('relId');
            $relationName = $request->route()->parameter('relation');

            $params = $request->all();
            
            $with = array_get($params, 'include', '');
            
            if (!empty($with)) {
                $with = explode(',', $with);
            } else {
                $with = [];
            }

            $data = $this->repository->showRelation($parentId, $relationName, $relId, $with);

            return response()->connect($data);
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $id = $request->route()->parameter('id');
            $params = $request->all();
            $data = $this->repository->update($id, $params);

            return response()->connect($data);
        });
    }

    /**
     * Update the specified relation.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function updateRelation(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $parentId = $request->route()->parameter('id');
            $relationName = $request->route()->parameter('relation');

            $relationData = $request->input('relationId');
            if (!isset($relationData)) {
                $relationData = $request->all();
            }
            $data = $this->repository->updateRelation($parentId, $relationName, $relationData);

            return response()->connect($data);
        });
    }

    /**
     * Update the specified relation.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteRelation(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $parentId = $request->route()->parameter('id');
            $relId = $request->route()->parameter('relationId');
            $relationName = $request->route()->parameter('relation');

            $data = $this->repository->deleteRelation($parentId, $relationName, $relId);

            return response()->connect($data);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
