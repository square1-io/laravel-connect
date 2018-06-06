<?php

namespace Square1\Laravel\Connect\App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Auth;
use Square1\Laravel\Connect\ConnectUtils;
use Psr\Http\Message\ServerRequestInterface;
use Square1\Laravel\Connect\Traits\InternalConnectScope;
use League\OAuth2\Server\Exception\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Square1\Laravel\Connect\App\Http\Controllers\ConnectBaseController;


class AuthController extends ConnectBaseController
{
    private $authModel;
    
    private $accessTokenController;
    
    public function __construct(AccessTokenController $accesstTokenController, Request $request)
    {
        parent::__construct($request);
        
       
        //TODO Should have this depending on client? As in separate for iOS, Android 
        $clientId = env('CONNECT_API_AUTH_CLIENT_ID', '');
        $grantType = env('CONNECT_API_AUTH_GRANT_TYPE', 'password');
        $client_secret = env('CONNECT_API_AUTH_CLIENT_SECRET', '');
        
        //Never add those to the request from the client but hide this inside the code
        $request->request->add(['grant_type' => $grantType,
        'client_id' => $clientId,
        'client_secret' => $client_secret,]);

        
        $authClass = config('connect.api.auth.model');
        $this->authModel = new $authClass; 
        //if there is any restriction on accessging this model we need to remobe it ( for example if user model is accessible only to logged in users)
        $this->authModel::disableModelAccessRestrictions();

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
        $data = Auth::user();
        return response()->connect($data);
    }

    /**
     * Login a user with username and password
     *
     * @return json , with user details and both refresh and auth token
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
                //parse error here 
                $error = [];
                if(isset($responseBody['error'])) {
                    $error['code'] = 403; 
                    $error['type'] = $responseBody['error']; 
                }
                if(isset($responseBody['message'])) {
                    $error['message'] = $responseBody['message']; 
                }
                if(isset($responseBody['hint'])) {
                    $error['hint'] = $responseBody['hint']; 
                }
                $data['error'] = $error;
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
     *  Refresh an auth token
     *
     * @return \Illuminate\Http\Response
     */
    public function refresh(ServerRequestInterface $request)
    {
    
        $currentBody = $request->getParsedBody();
        $currentBody['grant_type'] = 'refresh_token';
        $request = $request->withParsedBody($currentBody);
     
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
                //parse error here 
                $error = [];
                if(isset($responseBody['error'])) {
                    $error['code'] = 403; 
                    $error['type'] = $responseBody['error']; 
                }
                if(isset($responseBody['message'])) {
                    $error['message'] = $responseBody['message']; 
                }
                if(isset($responseBody['hint'])) {
                    $error['hint'] = $responseBody['hint']; 
                }
                $data['error'] = $error;
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
     * @param  int $id
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
    
    //TODO connect account to facebook, google, linkeding ecc... ecc...
    public function connect(Request $request)
    {
    }
}
