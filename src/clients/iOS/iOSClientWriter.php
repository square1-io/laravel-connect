<?php

namespace Square1\Laravel\Connect\Clients\iOS;

use DOMDocument;
use Illuminate\Support\Str;
use Square1\Laravel\Connect\Console\MakeClient;
use Square1\Laravel\Connect\Clients\ClientWriter;
use Square1\Laravel\Connect\Model\ModelAttribute;
use Square1\Laravel\Connect\Model\ModelInspector;
use Square1\Laravel\Connect\Clients\Deploy\GitDeploy;

class iOSClientWriter extends ClientWriter
{
    public function __construct(MakeClient $makeClient)
    {
        parent::__construct($makeClient);
    }

    public function outputClient()
    {
        $this->info("------ RUNNING iOS CLIENT WRITER ------");

        $path = $this->buildSwiftFolder();
        
        //now that patsh are set prepare git for deploy
        // pull previous version
        $git = new GitDeploy(env('IOS_GIT_REPO'), 
                $this->client()->baseBuildPath . '/iOS/' ,
                env('IOS_GIT_BRANCH') );
        
        $git->init();
        
        $tableMap  = array_merge(array(), $this->client()->tableMap);
        
        $members_test = [];
        //xcdatamodeld package content
        $xml = new DOMDocument();
        $xmlTemplate = $this->client()->files->get(dirname(__FILE__)."/schematemplate.xml");
        $xml->loadXML($xmlTemplate);
        
        $xmlModel = $xml->getElementsByTagName("model")->item(0);
        $xmlElements = $xml->createElement("elements");
        
        //    <elements>
        //<element name="Event" positionX="261" positionY="189" width="128" height="60"/>
   
        foreach ($this->client()->classMap as $classMap) {
            $routes = isset($classMap["routes"]) ? $classMap["routes"] : [];
            $inspector = $classMap['inspector'];
            $className = $inspector->classShortName();
            $primaryKey = $inspector->primaryKey();
            $classPath = $inspector->endpointReference();
            $this->info($className);
            
            //loop over the tables and match members and types
            $members = $this->buildSwiftMembers(array_merge($inspector->getDynamicAttributes(), $tableMap[$inspector->tableName()]['attributes']));
            $members_test[$className] = $members;
            //create xml for coredata schema
            $coredata_entity = $xml->createElement("entity");
            $coredata_entity->setAttribute("name", $className);
            // no need for this or we have conflicts
            //$coredata_entity->setAttribute("codeGenerationType","class");
            $coredata_entity->setAttribute("representedClassName", $className);
            $coredata_entity->setAttribute("syncable", "YES");
            
            //setting attributes to the entity "
            // <attribute name="content" optional="YES" attributeType="String" syncable="YES"/>
            foreach ($members as $member) {
                $newElement = null;
                
                if ($member['primaryKey']) {
                    // ="0" ="YES" syncable="YES"/>
                    $newElement = $xml->createElement("attribute");
                    $userInfo =  $xml->createElement("userInfo");
                    $newElement->appendChild($userInfo);
                    $newElement->setAttribute("name", "identifier");
                    $newElement->setAttribute("attributeType", "Integer 64");
                    $newElement->setAttribute("defaultValueString", "0");
                    $newElement->setAttribute("usesScalarValueType", "YES");
                } elseif (!empty($member['references']) || $member['collection']) {
                    // <relationship name="conversation" optional="YES" maxCount="1" deletionRule="Nullify" destinationEntity="Conversation" inverseName="messages" inverseEntity="Conversation" syncable="YES"/>
                    $newElement = $xml->createElement("relationship");
                    $newElement->setAttribute("name", $member['varName']);
                    $newElement->setAttribute("destinationEntity", $member['type']);
                    $newElement->setAttribute("deletionRule", "Nullify");
                    
                    if ($member['collection']) {
                        $newElement->setAttribute("toMany", "YES");
                        //toMany="YES"
                    } else {
                        $newElement->setAttribute("maxCount", "1");
                    }
                } else {
                    $newElement = $xml->createElement("attribute");
                    $newElement->setAttribute("attributeType", $member['xmlType']);
                    $newElement->setAttribute("name", $member['varName']);
                }
                
                
                if (isset($newElement)) {
                    $newElement->setAttribute("optional", "YES");
                    $coredata_entity->appendChild($newElement);
                }
            }
            
            $xmlModel->appendChild($coredata_entity);
            
            //this is just for the visual editor
            $element = $xml->createElement("element");
            $element->setAttribute("name", $className);
            
            $element->setAttribute("positionX", "261");
            $element->setAttribute("positionY", "161");
            
            $element->setAttribute("width", "150");
            $element->setAttribute("height", "100");

            $xmlElements->appendChild($element);
            
            
            $endpoints = $this->buildSwiftRoutes($routes);
            unset($tableMap[$inspector->tableName()]);

            $swift = view("ios::master", compact('classPath', 'members', 'package', 'className', 'primaryKey', 'endpoints'))->render();
            $this->client()->files->put($path . "/" . $className . "+CoreDataClass.swift", $swift);
        }
        $xmlModel->appendChild($xmlElements);
        $this->buildXCDatamodeld($xml);
        $this->client()->dumpObject('members_test', $members_test);
        
           // deliver to the mobile developer 
        $git->push();
    }

    private function buildSwiftRoutes($routes)
    {
        $requests = [];

        foreach ($routes as $route) {
            $allowedMethods = array_diff($route['methods'], ['HEAD']);
            foreach ($allowedMethods as $method) {
                $request = $this->buildSwiftRoute($method, $route);
                if (count($allowedMethods) > 1) {
                    $request['requestName'] = $request['requestName'] . "_$method";
                }
                $requests[] = $request;
            }
        }

        return $requests;
    }

    private function buildSwiftRoute($method, $route)
    {
        $requestParams = null;
        $requestParamsMap = null;

        foreach ($route['params'] as $paramName => $param) {
            $type = null;
            if (isset($param['table'])) {
                $type = $this->resolveTableNameToSwiftType($param['table']);
            }
            //if no table type we use the route type
            if (!isset($type)) {
                $type = $this->resolveToSwiftType($param['type']);
            }

            if (isset($param['array']) && $param['array'] == true) {
                $type = "ArrayList<$type>";
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

    private function buildSwiftMembers($attributes)
    {
        $members = array();

        foreach ($attributes as $attribute) {
            $attribute = is_array($attribute) ? $attribute[0] : $attribute;
            $this->info("$attribute", 'vvv');
            //this save us from members that use language specific keywords as name
            $varName = lcfirst($attribute->name);
            $name = Str::studly($attribute->name);
            $type = $this->resolveType($attribute);
            $xmlType = $this->resolveTypeForCoreDataXML($attribute);
            $collection = $attribute->collection;
            $dynamic = $attribute->dynamic; //those have no setter! are from the append of the model array
            $primaryKey = $attribute->primaryKey();

            $references = isset($attribute->foreignKey) ? $attribute->foreignKey : null;
            if (!empty($type)) {
                $members[] = compact('dynamic', 'xmlType', 'collection', 'varName', 'name', 'type', 'primaryKey', 'references');
            }
        }

        return $members;
    }

    public function buildRoutes($routes)
    {
        if (empty($routes)) {
            return [];
        }

        $swiftRoutes = [];
        foreach ($routes as $route) {
        }

        return $swiftRoutes;
    }

    public function buildRoute($route)
    {
        $swiftRoute = [];

        $params = [];

        foreach ($route["params"] as $paramName => $param) {
            $current = [];
            $current['name'] = $paramName;
            $current['type'] = isset($param["table"]) ?
                    $this->resolveTableNameToSwiftType($param["table"]) : null;
            if (is_null($current['type'])) {
                $current['type'] = $this->resolveToSwiftType($param["type"]);
            }

            $current['array'] = isset($param["array"]);

            $params[] = $current;
        }



        return $swiftRoute;
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

        return $this->resolveToSwiftType($attribute);
    }

    public function resolveTableNameToSwiftType($table)
    {
        $modelInspector = $this->client()->tableInspectorMap[$table];
        if (!empty($modelInspector)) {
            return $modelInspector->classShortName();
        }

        return null;
    }
    
    private function resolveTypeForCoreDataXML($type)
    {
        if ($type instanceof ModelAttribute) {
            if (!empty($type->on)) {
                if (isset($this->client()->tableInspectorMap[$type->on])) {//$attribute->isRelation() == TRUE){
                    ///so this is a relation, lets get the 'on' value and find what class this relates to
                    $modelInspector = $this->client()->tableInspectorMap[$type->on];
                    if (!empty($modelInspector)) {
                        return $modelInspector->classShortName();
                    } else {
                        return null; ///this was a relation with a model that is not exposed
                    }
                } else {
                    return null;
                }
            }
            
            $type = $type->type;
        }
        
        if ($type == 'text' ||
                $type == 'char' ||
                $type == 'string' ||
                $type == 'enum') {
            return 'String';
        }

        if ($type == 'timestamp' || $type == 'date') {
            return 'Date';
        }

        if ($type == 'integer' || $type == 'int') {
            return 'Integer 64';
        }

        if ($type == 'float') {
            return 'Float';
        }

        if ($type == 'double') {
            return 'Double';
        }

        if ($type == 'boolean') {
            return 'Bool';
        }

        if ($type == 'image') {
            return 'String';
        }

        return $type;
    }

    public function resolveToSwiftType($type)
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
            return 'Int';
        }

        if ($type == 'float') {
            return 'Float';
        }

        if ($type == 'double') {
            return 'Double';
        }

        if ($type == 'boolean') {
            return 'Bool';
        }

        if ($type == 'image') {
            return 'UploadedFile';
        }

        return $type;
    }

    private function buildSwiftFolder()
    {
        $path = $this->client()->baseBuildPath . '/iOS/';

        $this->client()->initAndClearFolder($path);
        
        //laravel_connect_test_app.xcdatamodeld

        return $path;
    }
    
    private function buildXCDatamodeld(DOMDocument $model)
    {
        $path = $this->client()->baseBuildPath . '/iOS/'.config("connect.clients.ios.data_model_name").".xcdatamodeld";
        
       
        $currentVersion = config("connect.clients.ios.data_model_name").".xcdatamodel";
        $this->client()->initAndClearFolder($path . "/".$currentVersion);
        $this->client()->files->put($path . "/".$currentVersion . "/contents", $model->saveXML());
           
        $plist = view("ios::xcdatamodel_version", compact('currentVersion'))->render();
        $this->client()->files->put($path . "/.xccurrentversion", $plist);
        //laravel_connect_test_app.xcdatamodel
    }
}
