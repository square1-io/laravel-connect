<?php

namespace Square1\Laravel\Connect\App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Square1\Laravel\Connect\ConnectUtils;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Square1\Laravel\Connect\App\Http\Controllers\ConnectBaseController;
use League\OAuth2\Server\Exception\OAuthServerException;

class AuthController extends ConnectBaseController
{
    private $authModel;
    
    private $accessTokenController;
    
    public function __construct(AccessTokenController $accesstTokenController, Request $request)
    {
        parent::__construct($request);
        
        $authClass = config('connect.api.auth.model');
        $this->authModel = new $authClass;
      
        $this->authServer = app()->make('League\OAuth2\Server\AuthorizationServer');
        $this->accessTokenController = $accesstTokenController;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $data = $this->currentAuthUser();
        return response()->connect($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(ServerRequestInterface $request)
    {
        $reference = $this->authModel->endpointReference();
        $statusCode = 500;
        try {
            $token = $this->accessTokenController->issueToken($request);
            $statusCode = $token->getStatusCode();
            $responseBody = json_decode($token->getBody(), true);
            if ($statusCode == 200) {
                $user = ConnectUtils::getUserForTokenString($responseBody['access_token']);
                $data = array_merge(['reference' => $reference,'user' => $user], $responseBody);
            } else {
                $data = $responseBody;
            }
        } catch (\ErrorException $error) {
            $statusCode = 500;
            $data = ['error' =>['message'=>'something went wrong']];
        } catch (\Exception $e) {
            dd($e);
            $statusCode = 500;
            $data = ['error' =>['message'=>'something went wrong']];
        }
        
        return response()->connect($data, $statusCode);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function register(ServerRequestInterface $request)
    {
        $params = $request->getParsedBody();
        $params['password'] = bcrypt($params['password']);
        $user = $this->authModel->create($params);
        $token = $this->accessTokenController->issueToken($request);
        $token = json_decode($token->getBody(), true);
        $reference = $this->authModel->endpointReference();
        $data = array_merge(['reference' => $reference,'user' => $user], $token);
         
        return response()->connect($data);
    }
    
    public function connect(Request $request)
    {
    }
}
