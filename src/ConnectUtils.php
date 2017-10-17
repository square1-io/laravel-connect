<?php

namespace Square1\Laravel\Connect;

use Laravel\Passport\TokenRepository;

class ConnectUtils {
    
    static public function repositoryInstanceForModelPath($modelReference)
    {
        $repositoryClass = config("connect_auto_generated.endpoints.".$modelReference);
       
        if (!empty($repositoryClass)) {
            return app()->make($repositoryClass);
        }
       
        return null;
    }
    
    
    static public function getUserForTokenString($tokenString)
    {
        $jwtParser = new \Lcobucci\JWT\Parser();
        $jwtToken = $jwtParser->parse($tokenString);
        $tokenId = $jwtToken->getHeader('jti');
        
        if (!empty($tokenId)) {
            $tokenRepo = new TokenRepository();
            $eloquentToken = $tokenRepo->find($tokenId);
            
            if (isset($eloquentToken)) {
             //resolve the user now
            $authClass = config('connect.api.auth.model');
                $authModel = new $authClass;
                $repository = static::repositoryInstanceForModelPath($authModel->endpointReference());
                $user = $repository->show($eloquentToken->user_id);
                return $user;
            }
        
            return null;
        }
    }
    
        /**
     * Parse token from the authorization header.
     *
     * @param string $header
     * @param string $method
     *
     * @return false|string
     */
    private static function parseAuthHeader($request, $header = 'authorization', $method = 'bearer')
    {
        $header = $request->headers->all();
        $header = $header['authorization'];
        
        if(is_array($header))
        {
            $header = $header[0];
        }
        
        if (! starts_with(strtolower($header), $method)) {
            return false;
        }

        return trim(str_ireplace($method, '', $header));
    }
    
    public static function currentAuthUser($request)
    {
        $currentUser = null;
        
        $tokenString = static::parseAuthHeader($request);
        $currentUser = static::getUserForTokenString($tokenString);
        
        
        return $currentUser;
    }
    
}
