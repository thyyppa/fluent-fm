<?php

namespace Hyyppa\FluentFM\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Hyyppa\FluentFM\Contract\FluentFM;
use Ramsey\Uuid\Uuid;

/**
 * Class FluentFMRepository.
 */
class FluentFMRepository extends BaseConnection implements FluentFM
{

    use FluentQuery;

    protected $auto_id = true;


    public function __construct(array $config, Client $client = null)
    {
        parent::__construct($config, $client);

        $this->clearQuery();
    }


    /**
     * {@inheritdoc}
     */
    public function record($layout, $id) : FluentFM
    {
        $this->records($layout, $id);

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function records($layout, $id = null) : FluentFM
    {
        $this->callback = function () use ($layout, $id) {
            $response = $this->client->get(Url::records($layout, $id), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'query'        => $this->queryString(),
            ]);

            Response::check($response, $this->queryString());

            return Response::records($response, $this->with_portals);
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function find(string $layout) : FluentFM
    {
        $this->callback = function () use ($layout) {
            $response = $this->client->post(Url::find($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => array_filter($this->query),
            ]);

            Response::check($response, array_filter($this->query));

            return Response::records($response, $this->with_portals);
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function create(string $layout, array $fields = [])
    {
        if ( ! array_key_exists('id', $fields) && $this->auto_id) {
            $fields[ 'id' ] = Uuid::uuid4()->toString();
        }

        $this->callback = function () use ($layout, $fields) {
            $response = $this->client->post(Url::records($layout), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => ['fieldData' => array_filter($fields)],
            ]);

            Response::check($response, ['fieldData' => array_filter($fields)]);

            return (int) Response::body($response)->response->recordId;
        };

        return $this->exec();
    }


    /**
     * {@inheritdoc}
     */
    public function globals(string $layout, array $fields = []) : bool
    {
        $this->callback = function () use ($layout, $fields) {
            $globals = [];

            foreach ($fields as $key => $value) {
                $globals[ $layout.'::'.$key ] = $value;
            }

            $response = $this->client->patch(Url::globals(), [
                'Content-Type' => 'application/json',
                'headers'      => $this->authHeader(),
                'json'         => ['globalFields' => array_filter($globals)],
            ]);

            Response::check($response, ['globalFields' => array_filter($globals)]);

            return true;
        };

        return $this->exec();
    }


    /**
     * {@inheritdoc}
     */
    public function update(string $layout, array $fields = [], int $recordId = null) : FluentFM
    {
        $this->callback = function () use ($layout, $fields, $recordId) {
            $recordIds = [$recordId];

            if ( ! $recordId) {
                if ( ! $records = $this->find($layout)->get()) {
                    return true;
                }

                $recordIds = array_keys($records);
            }

            foreach ($recordIds as $id) {
                $response = $this->client->patch(Url::records($layout, $id), [
                    'Content-Type' => 'application/json',
                    'headers'      => $this->authHeader(),
                    'json'         => ['fieldData' => array_filter($fields)],
                ]);

                Response::check($response, ['fieldData' => array_filter($fields)]);
            }

            return true;
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function upload(string $layout, string $field, string $filename, int $recordId = null) : FluentFM
    {
        $this->callback = function () use ($layout, $field, $filename, $recordId) {
            $recordIds = $recordId ? [$recordId] : array_keys($this->find($layout)->get());

            foreach ($recordIds as $id) {
                $response = $this->client->post(Url::container($layout, $field, $id), [
                    'Content-Type' => 'multipart/form-data',
                    'headers'      => $this->authHeader(),
                    'multipart'    => [
                        [
                            'name'     => 'upload',
                            'contents' => fopen($filename, 'rb'),
                            'filename' => basename($filename),
                        ],
                    ],
                ]);

                Response::check($response, [
                    'multipart' => [
                        [
                            'name'     => 'upload',
                            'contents' => '...',
                            'filename' => basename($filename),
                        ],
                    ],
                ]);
            }

            return true;
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function download(string $layout, string $field, string $output_dir = './', int $recordId = null) : FluentFM
    {
        $this->callback = function () use ($layout, $field, $output_dir, $recordId) {
            if ($recordId) {
                $records = $this->record($layout, $recordId)->get();
            } else {
                $records = $this->find($layout)->get();
            }

            if ( ! is_dir($output_dir) && ! mkdir($output_dir, 0775, true) && ! is_dir($output_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $output_dir));
            }

            $downloader = new Client([
                'verify'  => false,
                'headers' => $this->authHeader(),
                'cookies' => true,
            ]);

            foreach ($records as $record) {
                $ext = pathinfo(
                    parse_url($record[ $field ])[ 'path' ],
                    PATHINFO_EXTENSION
                );

                $filename = sprintf('%s/%s.%s', $output_dir, $record[ 'id' ], $ext);
                $response = $downloader->get($record[ $field ]);

                Response::check($response, $this->query);

                file_put_contents(
                    $filename,
                    $response->getBody()->getContents()
                );
            }

            $downloader = null;
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function delete(string $layout, int $recordId = null) : FluentFM
    {
        $this->callback = function () use ($layout, $recordId) {
            $recordIds = $recordId ? [$recordId] : array_keys($this->find($layout)->get());

            foreach ($recordIds as $id) {
                $response = $this->client->delete(Url::records($layout, $id), [
                    'headers' => $this->authHeader(),
                ]);

                Response::check($response, $this->query);
            }

            return true;
        };

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function softDelete(string $layout, int $recordId = null) : FluentFM
    {
        return $this->update(
            $layout,
            ['deleted_at' => date('m/d/Y H:i:s')],
            $recordId
        )->whereEmpty('deleted_at');
    }


    /**
     * {@inheritdoc}
     */
    public function undelete(string $layout, int $recordId = null) : FluentFM
    {
        return $this->update(
            $layout,
            ['deleted_at' => ''],
            $recordId
        )->withDeleted();
    }


    /**
     * {@inheritdoc}
     */
    public function fields(string $layout) : array
    {
        if (isset($this->field_cache[ $layout ])) {
            return $this->field_cache[ $layout ];
        }

        $id          = $this->create($layout);
        $temp_record = $this->record($layout, $id)->first();
        $fields      = array_keys($temp_record);
        $this->delete($layout, $id)->exec();

        return $this->field_cache[ $layout ] = $fields;
    }


    /**
     * {@inheritdoc}
     */
    public function logout() : void
    {
        if ( ! $this->token) {
            return;
        }

        try {
            $this->client->delete('sessions/'.$this->token, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (Exception $e) {
        }
    }


    /**
     * {@inheritdoc}
     */
    public function exec()
    {
        return $this->get();
    }


    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $results = null;

        if ( ! isset($this->query[ 'query' ][ 0 ]) || ! \is_array($this->query[ 'query' ][ 0 ])) {
            $this->has('id');
        }

        if ($this->with_deleted === false) {
            $this->whereEmpty('deleted_at');
        }

        try {
            $results = ($this->callback)();
        } catch (\Exception $e) {
            if ($e->getCode() === 401 || $e->getCode() === 952) {
                $this->getToken();
                $results = ($this->callback)();
            } elseif ($e instanceof RequestException && $response = $e->getResponse()) {
                Response::check($response, $this->query);
            } else {
                throw $e;
            }
        } finally {
            $this->clearQuery();
        }

        return $results;
    }


    /**
     * {@inheritdoc}
     */
    public function latest(string $layout, string $field = 'created_at')
    {
        return $this->records($layout)->sortDesc($field)->limit(1)->first();
    }


    /**
     * {@inheritdoc}
     */
    public function lastUpdate(string $layout, string $field = 'updated_at')
    {
        return $this->records($layout)->sortDesc($field)->limit(1)->first();
    }


    /**
     * {@inheritdoc}
     */
    public function oldest(string $layout, string $field = 'created_at')
    {
        return $this->records($layout)->sortAsc($field)->limit(1)->first();
    }


    /**
     * {@inheritdoc}
     */
    public function first()
    {
        return \array_slice($this->get(), 0, 1)[ 0 ];
    }


    /**
     * {@inheritdoc}
     */
    public function last()
    {
        return \array_slice($this->get(), -1, 1)[ 0 ];
    }


    /**
     * Request new token from filemaker.
     * Useful for tasks delayed in queue.
     *
     * Try this if you are getting 952 errors from filemaker.
     */
    public function refreshToken() : void
    {
        $this->logout();
        $this->getToken();
    }


    /**
     *
     */
    public function __destruct()
    {
        try {
            $this->logout();
            unset($this->client);
        } catch (\Exception $e) {
        }
    }


    /**
     * @param  bool  $auto_id
     */
    public function setAutoId(bool $auto_id) : void
    {
        $this->auto_id = $auto_id;
    }


    /**
     * @return self|FluentFMRepository
     */
    public function enableAutoId() : self
    {
        $this->auto_id = true;

        return $this;
    }


    /**
     * @return self|FluentFMRepository
     */
    public function disableAutoId() : self
    {
        $this->auto_id = false;

        return $this;
    }

}
