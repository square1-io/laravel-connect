<?php

namespace Square1\Laravel\Connect\Clients;

use Square1\Laravel\Connect\Console\MakeClient;

abstract class ClientWriter
{
    private $client;

    public function __construct(MakeClient $makeClient)
    {
        $this->client = $makeClient;
    }

    public function client()
    {
        return $this->client;
    }

    public function info($string, $verbosity = null)
    {
        $this->client->info($string, $verbosity);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function appVersion(){
        return $this->client->appVersion;
    }

    public function pathComponentsAsArrayString(){

        $str = config('connect.api.prefix');
        $result = "";
    
        foreach (explode("/",$str) as $part ) {
            
            if(!empty($part)) {
                
                if(empty($result)){
                    $result = "[";
                }else {
                    $result = $result.",";
                }
                
                $result = $result."\"$part\"";
                
            }
                
        }
        
         if(!empty($result)){
             $result = $result."]";
          }

        return $result;

    }

    /**
     *
     * @param mixed $attribute, string or ModelAttribute
     * @return type
     */
    abstract public function resolveType($attribute);

    abstract public function outputClient();
}
