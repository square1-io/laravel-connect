<?php

namespace Square1\Laravel\Connect\App\Routes;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Validation\Validator;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Support\Facades\Route as RouteFacade;
use \Illuminate\Filesystem\Filesystem;
use Square1\Laravel\Connect\Console\MakeClient;

class RoutesInspector
{
    private $client;

     /**
      * The filesystem instance.
      *
      * @var \Illuminate\Filesystem\Filesystem
      */
    protected $files;
    
    public $routes;

     /**
      * Create a new  instance.
      *
      * @return void
      */
    public function __construct(Filesystem $files, MakeClient $client)
    {
        $this->files = $files;
        $this->client = $client;
        $this->routes = [];
    }
    
    
    public function inspect()
    {
        $this->client->info("-- INSPECTING ROUTES --");
        
        $routeCollection = RouteFacade::getRoutes();

        foreach ($routeCollection as $value) {
            $this->processRoute($value);
        }
        
         // dd(json_encode($this->routes));
    }
    
    private function processRoute(Route $route)
    {
        $uri = $route->uri;
        $methods = $route->methods;
        $action = $route->getAction();
        
        if (!isset($action['controller'])) {
            return;
        }
        
        $controller = $action['controller'];
        $action = explode('@', $controller);
       
        $controllerClass = new ReflectionClass($action[0]);
        if (!$controllerClass->hasMethod($action[1])) {
            return;
        }
        $controllerMethod = $controllerClass->getMethod($action[1]);
        $parameters = $controllerMethod->getParameters();

   
        $result = $this->processRouteRequest($uri, $methods, $controller, $action, $parameters);
        if (isset($result)) {
            $name = $route->getName();
            if (isset($name)) {
                $result['name'] = $name;
            }
            $this->routes[$uri] = $result;
        }
    }
    
    private function processRouteRequest($uri, $me, $controller, $action, $parameters)
    {
        $value = null;
       
        foreach ($parameters as $parameter) {
            // is the request associated to this route to be exposed in the connect client ?
            $requestInstance = $this->requestParameterInstanceWithTraith($parameter);
            
            if (isset($requestInstance)) {
                $parameters = array_diff($parameters, [$parameter]);
                //extracting route parameters
         
                
                preg_match_all("/\{(\w+?)?\}/", $uri, $routeParameters);
             
                //$parameters = array_diff($parameters, $routeParameters);
                
                if (!isset($value)) {
                    $value = [];
                }
                $value['params'] = $this->requestParseParameters($requestInstance);
                $value['uri'] =  $uri;
                $value['methods'] =  $me;
                $value['paginated'] =  $requestInstance->getIsPaginated();
                $value['model'] = $requestInstance->getAssociatedModel();
                $value['other'] =  $parameters;
                $value['route_params'] =  isset($routeParameters[1])? $routeParameters[1] : [] ;
                $value['methodName'] =  $action[1];
                $value['controller'] =  $action[0];
                $this->client->info("valid route at $uri with request ". $parameter->getClass()->getName());
                break;
            }
        }
        
        return $value;
    }
    
    public function requestParameterInstanceWithTraith(ReflectionParameter $parameter)
    {
        $parameterClass = $parameter->getClass();
        
     
        if (!isset($parameterClass)) {
            return null;
        }
        
        $traits = $parameterClass->getTraitNames();
        
        if (!empty($traits) 
            && in_array("Square1\Laravel\Connect\Traits\ConnectApiRequestTrait", $traits)
        ) {
            $instance =  $parameterClass->newInstanceArgs();
            return $instance;
        }
 
        return null;
    }
    
    public function requestParseParameters($request)
    {
        $rules = $request->parameters();
        if ($request->getIsPaginated()) {
            $this->addPaginatedParameters($rules);
        }
        
        $validator = new RequestParamTypeResolver($this->client, $request->rules());
        $validator->resolve();
        return $validator->paramsType;
    }
  
    public function addPaginatedParameters(array $array)
    {
        $array['page'] = 'integer';
        $array['per_page'] = 'integer';
    }
}
