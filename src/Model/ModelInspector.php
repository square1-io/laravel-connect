<?php

namespace Square1\Laravel\Connect\Model;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use \Illuminate\Filesystem\Filesystem;
use Square1\Laravel\Connect\Console\MakeClient;
use Square1\Laravel\Connect\Model\Relation\Relation;
use Square1\Laravel\Connect\Model\Relation\BelongsToMany;

class ModelInspector
{
    private $model;
     
    private $modelInfo;

    private $baseTmpPath;
     
    private $client;
     
    private $methods;
     
    private $dynamicAttributesMethods;
     
    public $relations;
     
    public $dynamicAttributes;
     
    public $tableAttributes;


    private $endpointReference;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new  instance.
     *
     * @return void
     */
    public function __construct($className, Filesystem $files, MakeClient $client)
    {
        $this->files = $files;
        $this->client = $client;
        $this->baseTmpPath = $client->baseTmpPath."/model";
        $this->client->info(">>>>>>>>>>>>>>>>>>>>>>>>>".$className);
       
       
        $this->modelInfo = new ReflectionClass($className);
    }
    
    public function init()
    {
        $this->endpointReference = $this->modelInfo->newInstance()->endpointReference();
        $this->model = $this->prepareForInspection();
        $this->tableAttributes = $this->client->tableMap[$this->tableName()]["attributes"];
        $this->model->setTableAttributes($this->tableAttributes);
        $this->model->inspector = $this;
        $this->dynamicAttributes = array();
        $this->relations = array();
        $this->methods = array();
        $this->dynamicAttributesMethods = array();
        $this->purgedUselessMethods($this->methods, $this->dynamicAttributesMethods);
    }
    
    public function classShortName()
    {
        return $this->modelInfo->getShortName();
    }
    
    public function className()
    {
        return $this->modelInfo->getName();
    }
    
    
    public function tableName()
    {
        return $this->model->getTable();
    }
    
    public function primaryKey()
    {
        return $this->model->getKeyName();
    }
    
    public function endpointReference()
    {
        return $this->endpointReference;
    }
    
    public function hasTrait()
    {
        return isset($this->modelInfo->getTraits()['Square1\Laravel\Connect\Traits\ConnectModelTrait']);
    }

    private function prepareForInspection()
    {
        $injectedClassName = $this->classShortName();
        $baseCode = $this->files->get(dirname(__FILE__)."/templates/Injected.model.template.php");
        $baseCode = str_replace('_INJECTED_CLASS_NAME_Template', $injectedClassName, $baseCode);
        $baseCode = str_replace('_INJECTED_EXTENDED_CLASS_NAME_', $this->className(), $baseCode);
        
        $injectedClassName = 'Square1\Laravel\Connect\Console\Injected\Injected'.$this->classShortName();
        //prepare tmp folder
        if ($this->files->isDirectory($this->baseTmpPath) == false) {
            $this->files->makeDirectory($this->baseTmpPath, 0755, true);
        }
        
        //get the file name to store this new class in
        $fileName = $this->injectedClassFileName();
        if ($this->files->isFile($fileName) == true) {
            $this->files->delete($fileName);
        }
        
        $this->files->put($fileName, $baseCode);
        include_once $fileName;
        
        return new $injectedClassName;
    }
    
    
    public function inspect()
    {
        $this->client->info('starting inspection ');
        
        foreach ($this->tableAttributes as $attribute) {
            
            //update the attribute type based on model specifics
            $type = $this->model->getTypeHint($attribute->name);
            if($type) {
                $this->client->info("updating type of $attribute->name to $type" );
                $attribute->type = $type;
            }

            $this->model->{$attribute->name} = $attribute->dummyData();
        }
        
        $this->findRelations();
        $this->resolveAppendedAttributes();
    }
    
    
    private function findRelations()
    {
        $this->client->info('finding relations ...');
 
        foreach ($this->methods as $method) {
            $this->callMethod($method, $this->model);
        }
    }
    
    public function callMethod(\ReflectionMethod $method, $on)
    {
        try {
            $this->client->info('calling '.$method->name, 'vvv');
                 
            $result = $method->invoke($on);
                 
            if ($result instanceof Relation) {
                $relatesToMany = $result->relatesToMany();
                $result = $result->toArray();
                    
                //is this relation via a separate table ?
                if ($relatesToMany && isset($result['table'])) {
                    $tableAttributes = $this->client->tableMap[$result['table']]["attributes"];
                    $result['table_attributes'] = $tableAttributes;
                }
                $this->relations[$result['name']] = $result;
                $this->client->info(" found ".$result['type']." of name ".$result['name']);
            }
        } catch (Exception $exc) {
            $this->client->error("error calling  $method->name " .  $exc->getMessage());
        }
    }

    /**
     * There are a number of methods that we don't have any interests on
     * we want to remove any method that is not potentially defining a relationship
     * or a dinamic attribute.
     *
     * @param array $out
     * @param array $dynamicAttributes
     */
    private function purgedUselessMethods(array& $out, array& $dynamicAttributes)
    {
        $this->client->info('removing unnecesary methods ...', 'vvv');
        
        //only take into account public methods.
        $methods = $this->modelInfo->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $traitsMethods = array();
        $traits = $this->modelInfo->getTraits();
        
        //skip methods inherited from traits
        foreach ($traits as $trait) {
            $traitMethods = $trait->getMethods(ReflectionMethod::IS_PUBLIC);
            $traitsMethods = array_merge(
                $traitsMethods,
                array_combine(
                    array_map(
                        function ($o) {
                            return $o->name;
                        },
                        $traitMethods
                    ),
                    $traitMethods
                )
            );
        }
        
        foreach ($methods as $method) {
            if (isset($traitsMethods[$method->name])) {
                $this->client->info("skipping $method->name", 'vvv');
                continue;
            };
            
            //esclude constructors and methods that take one or more parameters
            if (strpos($method->name, '_construct') === false
                && empty($method->getParameters())
                && $method->class == $this->modelInfo->getName()
            ) {//not inherited
                
                if (Str::startsWith($method->name, "get")
                    && Str::endsWith($method->name, "Attribute")
                ) {
                    $dynamicAttributes[$method->name] = $method;
                    
                    $this->client->info("found dynamic attribute $method->name", 'vvv');
                } else {
                    $out[] = $method;
                    $this->client->info("found $method->name", 'vvv');
                }
            }
        }
       
        //return $useful;
    }

    private function resolveAppendedAttributes()
    {
        foreach ($this->model->getAppends() as $attributeName) {
            $methodName = 'get'.Str::studly($attributeName)."Attribute";
            
            if (isset($this->dynamicAttributesMethods[$methodName])) {
                $type = $this->model->getTypeHint($attributeName);
                if ($type == null) {
                    $type =  $this->callMethod($this->dynamicAttributesMethods[$methodName], $this->model);
                    if ($type == null) {
                        $type = "string";
                        $this->client->info("Attribute type $attributeName defaulted to $type", 'vvv');
                    } else {
                        $this->client->info("Attribute type $attributeName -> $type", 'vvv');
                    }
                }
                $fluent = new Fluent(['name' => $attributeName, 'type' => $type, 'dynamic' => true ]);
                $attribute =  new ModelAttribute($fluent);
                $this->dynamicAttributes[$attribute->name] = $attribute;
            }
        }
    }


    private function injectedClassFileName()
    {
        return strtolower($this->baseTmpPath.'/'.str_replace('\\', '_', $this->className()).'.php');
    }


    public function getDynamicAttributes()
    {
        return $this->dynamicAttributes;
    }
    
    public function getTableAttributes()
    {
        return $this->tableAttributes;
    }

    public function allAttributes()
    {
        return array_merge($this->dynamicAttributes, $this->tableAttributes);
    }

    
    public function relations()
    {
        return $this->relations;
    }
    
    
    
    
    public function getHidden()
    {
        return $this->model->getHidden();
    }
}
