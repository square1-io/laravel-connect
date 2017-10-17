<?php

namespace Square1\Laravel\Connect\Clients\Android;

use Illuminate\Support\Str;
use Square1\Laravel\Connect\Console\MakeClient;
use Square1\Laravel\Connect\Clients\ClientWriter;
use Square1\Laravel\Connect\Model\ModelAttribute;
use Square1\Laravel\Connect\Model\ModelInspector;

class AndroidClientWriter extends ClientWriter
{
    public function __construct(MakeClient $makeClient)
    {
        parent::__construct($makeClient);
    }

    public function outputClient()
    {
        $this->info("------ RUNNING ANDROID CLIENT WRITER ------");

        $package = config("connect.clients.android.package");

        $path = $this->buildJavaPackageFolder($package);

        $tableMap  = array_merge(array(), $this->client()->tableMap);
         
        foreach ($this->client()->classMap as $classMap) {
            $routes = isset($classMap["routes"]) ? $classMap["routes"] : [];
            $inspector = $classMap['inspector'];
            $className = $inspector->classShortName();
            $primaryKey = $inspector->primaryKey();
            $classPath = $inspector->endpointReference();
            $this->info($package . "." . $className);

            //loop over the tables and match members and types
            $members = $this->buildJavaMembers($inspector->allAttributes());
            $relations = $this->buildJavaRelations($inspector->relations());
            $endpoints = $this->buildJavaRoutes($routes);
        

            $java = view("android::master", compact('classPath', 'package', 'className', 'primaryKey', 'members', 'relations', 'endpoints'))->render();
            $this->client()->files->put($path . "/" . $className . ".java", $java);
        }
    }

    private function buildJavaRoutes($routes)
    {
        $requests = [];

        foreach ($routes as $route) {
            $allowedMethods = array_diff($route['methods'], ['HEAD']);
            foreach ($allowedMethods as $method) {
                $request = $this->buildJavaRoute($method, $route);
                if (count($allowedMethods) > 1) {
                    $request['requestName'] = $request['requestName'] . "_$method";
                }
                $requests[] = $request;
            }
        }

        return $requests;
    }

    private function buildJavaRoute($method, $route)
    {
        $requestParams = null;
        $requestParamsMap = null;

        foreach ($route['params'] as $paramName => $param) {
            $type = null;
            if (isset($param['table'])) {
                $type = $this->resolveTableNameToJavaType($param['table']);
            }
            //if no table type we use the route type
            if (!isset($type)) {
                $type = $this->resolveToJavaType($param['type']);
            }

            if (isset($param['array']) && $param['array'] == true) {
                $type = "Iterable<$type>";
            }

            //buidling the method signature
            if (!empty($requestParams)) {
                $requestParams = $requestParams . ', ';
            }
            $requestParams = $requestParams . "$type $paramName";

            if (!empty($requestParamsMap)) {
                $requestParamsMap = $requestParamsMap . ',';
            }
            $requestParamsMap = $requestParamsMap . "\"$paramName\",$paramName";
        }

        $request = [];
        $request['requestMethod'] = $method;
        $request['requestUri'] = $route['uri'];
        $request['requestName'] = $route['methodName'];
        $request['paginated'] = $route['paginated'] == true ? 'true' : 'false';
        $request['requestParams'] = $requestParams;
        $request['requestParamsMap'] = $requestParamsMap;

        return $request;
    }

    private function buildJavaRelations($relations)
    {
        $results = [];
        
        foreach ($relations as $relationName => $relation) {
            $varName = lcfirst($relationName);
            $name = $relationName;
            $type = $relation['many'] ? "ModelManyRelation" : "ModelOneRelation";
            $hasSetter = false;
            $relatedClass = $this->client()->classMap[$relation['related']]['inspector']->classShortName();
            $type = $type."<$relatedClass>";
            $many = $relation['many'];
            $key = $relation['foreignKey'];
            $results[$varName] = compact('hasSetter', 'varName', 'name', 'type', 'relatedClass', 'key', 'many');
        }
        
        return $results;
    }

    private function buildJavaMembers($attributes)
    {
        $members = array();

        foreach ($attributes as $attribute) {
            $attribute = is_array($attribute) ? $attribute[0] : $attribute;
            $this->info("$attribute", 'vvv');
            //this save us from members that use language specific keywords as name
            $varName = lcfirst($attribute->name);
            $name = Str::studly($attribute->name);
            $type = $this->resolveType($attribute);
      
            $hasSetter = $attribute->dynamic == false; //those have no setter! are from the append of the model array
            $primaryKey = $attribute->primaryKey();

            if (!empty($type) && !$primaryKey) {
                $members[$varName] = compact('hasSetter', 'varName', 'name', 'type');
            }
        }

        return $members;
    }

    public function buildRoutes($routes)
    {
        if (empty($routes)) {
            return [];
        }

        $javaRoutes = [];
        foreach ($routes as $route) {
        }

        return $javaRoutes;
    }

    public function buildRoute($route)
    {
        $javaRoute = [];

        $params = [];

        foreach ($route["params"] as $paramName => $param) {
            $current = [];
            $current['name'] = $paramName;
            $current['type'] = isset($param["table"]) ?
                    $this->resolveTableNameToJavaType($param["table"]) : null;
            if (is_null($current['type'])) {
                $current['type'] = $this->resolveToJavaType($param["type"]);
            }

            $current['array'] = isset($param["array"]);

            $params[] = $current;
        }



        return $javaRoute;
    }

    /**
     *
     * @param mised $attribute, string or ModelAttribute
     * @return type
     */
    public function resolveType($attribute)
    {
        if ($attribute instanceof ModelAttribute) {
            if (!empty($attribute->on)) {
                if (isset($this->client()->tableInspectorMap[$attribute->on])) {//$attribute->isRelation() == TRUE){
                    ///so this is a relation, lets get the 'on' value and find what class this relates to
                    $modelInspector = $this->client()->tableInspectorMap[$attribute->on];
                    if (!empty($modelInspector)) {
                        return $modelInspector->classShortName();
                    } else {
                        return null; ///this was a relation with a model that is not exposed
                    }
                } else {
                    return null;
                }
            }
            $attribute = $attribute->type;
        }

        return $this->resolveToJavaType($attribute);
    }

    public function resolveTableNameToJavaType($table)
    {
        $modelInspector = $this->client()->tableInspectorMap[$table];
        if (!empty($modelInspector)) {
            return $modelInspector->classShortName();
        }

        return null;
    }

    public function resolveToJavaType($type)
    {
        if ($type == 'text' ||
                $type == 'char' ||
                $type == 'string' ||
                $type == 'enum') {
            return 'String';
        }

        if ($type == 'timestamp' ||
                $type == 'date' ||
                $type == 'dateTime') {
            return 'Date';
        }

        if ($type == 'integer' || $type == 'int') {
            return 'Integer';
        }

        if ($type == 'float') {
            return 'Float';
        }

        if ($type == 'double') {
            return 'Double';
        }

        if ($type == 'boolean') {
            return 'Boolean';
        }

        if ($type == 'image') {
            return 'UploadedFile';
        }

        return $type;
    }

    private function buildJavaPackageFolder($package)
    {
        $path = $this->client()->baseBuildPath . '/android/' . str_replace('.', '/', $package);

        $this->client()->initAndClearFolder($path);

        return $path;
    }
}
