<?php

namespace Square1\Laravel\Connect\App\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Square1\Laravel\Connect\ConnectUtils;

/**
 * Description of AfterConnectMiddleware
 *
 * @author roberto
 */
class AfterConnectMiddleware
{
    public function handle($request, Closure $next)
    {
        //deal with logging in a user 
        try{
            $currentUser = ConnectUtils::currentAuthUser($request);
       
            if($currentUser) {
                Auth::login($currentUser);
            }
        }
        catch(Exception $e)
        {
       
        }
        $response = $next($request);
        
        //deal with crossdomain requests
        
        if ($this->isCORSEnabled()) {
            $headers = ['Access-Control-Allow-Origin' => '*'];
                  
            if ($this->isPreflightRequest($request)) {
                $headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization';
                $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
            }
            
            $response->headers->add($headers);
        }
        
        //Handle JSONP requests
        if ($request->has('callback')) {
            return $response->withCallback($request->input('callback'));
        }

        return $response;
    }
    
    
    public function isCORSEnabled()
    {
        return true;
    }

    /**
     * Check for a Preflight request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return boolean
     */
    protected function isPreflightRequest($request)
    {
        return  $request->isMethod('OPTIONS') &&
                $request->hasHeader('Access-Control-Request-Method');// &&
               // $request->hasHeader('Origin');
    }
}
