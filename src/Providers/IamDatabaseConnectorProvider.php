<?php

namespace AD\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class IamDatabaseConnectorProvider extends ServiceProvider
{
    /**
     * Register the application services.
     * Swap out the default mysql connector and bind our custom one
     *
     * @return void
     */
    public function register()
    {
      if (config('database.connections.mysql_iam.use_iam_auth')) {
        $this->app->bind('db.connector.mysql', \AD\Database\MysqlConnector::class);
      }
    }
}