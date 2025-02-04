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
    protected $config = ['token_ttl' => 870];

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
    public function __construct(array $config, ?Client $client = null)
    {
        // we merge with the default config containing the newly introduced token cache ttl
        // as to keep this new feature backward compatible
        $this->config = array_merge($this->config, $config);

        $options = [
            'base_uri' => sprintf(
                'https://%s/fmi/data/v1/databases/%s/',
                $this->config('host'),
                $this->config('file')
            ),
        ];

        $this->client = $client ?? new Client(
            array_merge($options, $this->config['client'] ?? [])
        );

        $this->getToken();
    }

    /**
     * Get specified value from config, or if not specified
     * the entire config array.
     *
     * @param  string|null  $key
     * @return array|mixed
     */
    protected function config(?string $key = null)
    {
        return $key ? $this->config[$key] : $this->config;
    }

    /**
     * Generate authorization header.
     *
     * @return array
     *
     * @throws FilemakerException
     */
    protected function authHeader(): array
    {
        if (! $this->token) {
            $this->getToken();
        }

        return [
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    /**
     * Request api access token from server or from the cache if available.
     *
     * @return string
     *
     * @throws FilemakerException
     * @throws ErrorException
     */
    protected function getToken(?bool $require_new_authentication = false): string
    {
        // if we have a cached token available and we are not required to do a fresh authentication
        if (
            $this->isCacheAvailable() &&
            $this->hasCachedToken() &&
            ! $require_new_authentication
        ) {
            // prolong the life of this cached token (as the Data API extends the lifetime of tokens each time they are used)
            $this->extendCachedTokenTtl();

            // and retrieve it from the cache instead of hitting the API's authentication endpoint
            return $this->token = $this->getCachedToken();
        }

        try {
            $header = $this->client->post('sessions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($this->config('user').':'.$this->config('pass')),
                ],
            ])->getHeader('X-FM-Data-Access-Token');

            if (! count($header)) {
                throw new FilemakerException('Filemaker did not return an auth token. Is the server online?', 404);
            }

            // if we have a caching system available
            if ($this->isCacheAvailable()) {
                // cache the token (it has an actual lifetime of 15 minutes,
                // we cache it for only almost 15 minutes (-30 sec) by default)
                $this->cacheToken($header[0]);
            }

            return $this->token = $header[0];
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

    /**
     * Checks if we are able to use the APCu cache.
     *
     * @return bool
     */
    protected function isCacheAvailable(): bool
    {
        return extension_loaded('apcu') && apcu_enabled();
    }

    /**
     * Returns a token cache name, unique to this the current database and host
     * It also includes a hash of the user and pass, in case multiple apps run on the same APCu.
     *
     * @return string
     */
    protected function getTokenCacheName(): string
    {
        return 'filemaker-data-api-token-'.sha1(
            $this->config('host').
            $this->config('file').
            $this->config('user').
            $this->config('pass')
        );
    }

    /**
     * Check if we have a non expired token for the current database and host.
     *
     * @return bool
     */
    protected function hasCachedToken(): bool
    {
        return apcu_exists($this->getTokenCacheName());
    }

    /**
     * Returns a cached token, supposedly valid for the current database and host.
     *
     * @return string
     */
    protected function getCachedToken(): string
    {
        return apcu_fetch($this->getTokenCacheName());
    }

    /**
     * Removes a cached token, useful when the Data API responds
     * that it is invalid (ie. after a reboot of the filemaker instance).
     *
     * @return bool
     */
    protected function removeCachedToken(): bool
    {
        return apcu_delete($this->getTokenCacheName());
    }

    /**
     * Stores a token for future use without the authentication step (avoiding an HTTP roundtrip).
     *
     * @return bool
     */
    protected function cacheToken(string $token): bool
    {
        return apcu_store(
            $this->getTokenCacheName(),
            $token,
            $this->config('token_ttl')
        );
    }

    /**
     * Extends the Time To Live of a cached token, to match Filemaker's
     * own extension of the token validity when we use it.
     *
     * @return void
     */
    protected function extendCachedTokenTtl(): void
    {
        // override the current token with the same one, but with a new ttl
        apcu_store(
            $this->getTokenCacheName(),
            $this->getCachedToken(),
            $this->config('token_ttl')
        );
    }
}
