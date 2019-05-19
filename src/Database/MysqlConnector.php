<?php

namespace AD\Database;

use Illuminate\Database\Connectors\MySqlConnector as DefaultMySqlConnector;
use PDO;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use AD\Auth\DbAuthTokenProvider;
use Illuminate\Support\Facades\Log;

class MySqlConnector extends DefaultMySqlConnector
{
    
    /**
     * Create a new PDO connection.
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return \PDO
     * 
     * @throws InvalidArgumentException when aws profile is not supplied
     */
    public function createConnection($dsn, array $config, array $options)
    {
        list($username) = [
            Arr::get($config, 'username'),
        ];

        if (! isset($config['aws_profile'])) {
          throw new InvalidArgumentException('An aws profile must be specified.');
        }

        $token_provider = new DbAuthTokenProvider($config);

        try {
            $password = $token_provider->getToken(); 

            Log::info('Connecting to db using auth token ' . $password);

            return $this->createPdoConnection(
                $dsn, $username, $password, $options
            );
        } catch (Exception $e) {
            $password = $token_provider->getToken(TRUE); 

            Log::info('Connecting to db using auth token ' . $password);

            return $this->tryAgainIfCausedByLostConnectionOrBadToken(
                $e, $dsn, $username, $password, $options
            );
        }
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @param  \Exception  $e
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function tryAgainIfCausedByLostConnectionOrBadToken(Exception $e, $dsn, $username, $password, $options)
    {
        if ($this->causedByLostConnectionOrBadToken($e)) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Determine if the given exception was caused by a lost connection or bad auth token
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByLostConnectionOrBadToken(\Exception $e)
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'Access denied'
        ]);
    }
}
