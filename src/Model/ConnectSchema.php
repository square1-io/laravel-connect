<?php

namespace Square1\Laravel\Connect\Model;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Database\Schema\Builder
 */
class ConnectSchema extends Facade
{
    public static function inspecting(MigrationInspector $inspector)
    {
        static::$resolvedInstance['connect'] = new InjectedBuilder($inspector);
    }

    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        if (isset(static::$resolvedInstance['connect'])) {
            return static::$resolvedInstance['connect'];
        } else {
            return static::$app['db']->connection($name)->getSchemaBuilder();
        }
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        if (isset(static::$resolvedInstance['connect'])) {
            return static::$resolvedInstance['connect'];
        } else {
            return static::$app['db']->connection()->getSchemaBuilder();
        }
    }
}
