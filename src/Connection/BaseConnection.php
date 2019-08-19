<?php

namespace Hyyppa\FluentFM\Connection;

use ErrorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Hyyppa\FluentFM\Exception\FilemakerException;

/**
 * Class BaseConnection.
 */
abstract class BaseConnection
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var
     */
    protected $callback;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var array
     */
    protected $field_cache = [];


    /**
     * BaseConnection constructor.
     *
     * @param  array  $config
     * @param  Client|null  $client
     *
     * @throws FilemakerException
     */
    public function __construct(array $config, Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Client([
                'base_uri'    => sprintf(
                    'https://%s/fmi/data/v1/databases/%s/',
                    $this->config('host'),
                    $this->config('file')
                ),
                'verify'      => false,
                'http_errors' => false,
                'timeout'     => $this->config('timeout'),
            ]);

        $this->getToken();
    }


    /**
     * Get specified value from config, or if not specified
     * the entire config array.
     *
     * @param  string|null  $key
     *
     * @return array|mixed
     */
    protected function config(string $key = null)
    {
        return $key ? $this->config[ $key ] : $this->config;
    }


    /**
     * Generate authorization header.
     *
     * @return array
     * @throws FilemakerException
     *
     */
    protected function authHeader() : array
    {
        if ( ! $this->token) {
            $this->getToken();
        }

        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }


    /**
     * Request api access token from server.
     *
     * @return string
     * @throws FilemakerException
     *
     */
    protected function getToken() : string
    {
        try {
            $header = $this->client->post('sessions', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->config('user').':'.$this->config('pass')),
                ],
            ])->getHeader('X-FM-Data-Access-Token');

            if ( ! count($header)) {
                throw new FilemakerException('Filemaker did not return an auth token. Is the server online?', 404);
            }

            return $this->token = $header[ 0 ];
        } catch (ClientException $e) {
            throw new FilemakerException('Filemaker access unauthorized - please check your credentials', 401, $e);
        } catch (ErrorException $e) {
            if (stristr($e->getMessage(), 'undefined offset')) {
                throw new FilemakerException(
                    'Filemaker didn\'t return X-FM-Data-Access-Token header - unable to authenticate',
                    401,
                    $e
                );
            }

            throw $e;
        }
    }
}
