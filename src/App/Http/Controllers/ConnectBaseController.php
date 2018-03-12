<?php

namespace Square1\Laravel\Connect\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ConnectBaseController extends Controller
{
    private $request;
            
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
     

    public function options()
    {
        return '';
    }
    
    public function withErrorHandling($closure)
    {
        try {
            return $closure();
        } catch (\Exception $e) {
            $this->exceptionHandler()->report($e);
            $payload = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
            
            return response()->connect(['error' => $payload]);
        }
    }
    
    /**
     * Get the exception handler instance.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function exceptionHandler()
    {
        return Container::getInstance()->make(ExceptionHandler::class);
    }
}
