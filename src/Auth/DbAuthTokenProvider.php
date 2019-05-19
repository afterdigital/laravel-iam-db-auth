<?php

namespace AD\Auth;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Rds\AuthTokenGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class DbAuthTokenProvider
{

  /**
   * AWS configuration values
   * @var Array
   */
  protected $config;

  /**
   * Class constructor
   * 
   * @param Array - AWS configuration
   * @return Void
   */
  public function __construct(array $config)
  {
    $this->config = $config;

    $provider = CredentialProvider::defaultProvider();

    $this->rds_auth_generator = new AuthTokenGenerator($provider);
  }

  /**
   * Get the DBS auth token from the AWS Auth Token Generator
   * 
   * @param Bool - Force refetch of cached token
   * @return String - The Auth token
   */
  public function getToken($break_cache = FALSE)
  {
    if ($break_cache)
    {
      Cache::forget('db_token');
    }

    return Cache::remember('db_token', 10, function() {
      return $this->rds_auth_generator->createToken(Arr::get($this->config, 'host') . ':' . Arr::get($this->config, 'port'), Arr::get($this->config, 'aws_region'), Arr::get($this->config, 'username'));
    });
  }
}