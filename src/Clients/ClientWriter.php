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
     *
     * @param mixed $attribute, string or ModelAttribute
     * @return type
     */
    abstract public function resolveType($attribute);

    abstract public function outputClient();
}
