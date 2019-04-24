<?php

namespace Hyyppa\FluentFM\Connection;

use Hyyppa\FluentFM\Contract\FluentFM;

/**
 * Trait FluentQuery.
 */
trait FluentQuery
{

    /**
     * @var array
     */
    protected $query;

    /**
     * @var bool
     */
    protected $with_portals = false;

    /**
     * @var bool
     */
    protected $with_deleted = true;


    /**
     * Limit the number of results returned.
     *
     * @param  int  $limit
     *
     * @return self|FluentFM
     */
    public function limit(int $limit) : FluentFM
    {
        $this->query[ 'limit' ] = $limit;

        return $this;
    }


    /**
     * Begin result set at the given record id.
     *
     * @param  int  $offset
     *
     * @return self|FluentFMRepository
     */
    public function offset(int $offset) : FluentFM
    {
        $this->query[ 'offset' ] = $offset;

        return $this;
    }


    /**
     * Sort results ascending by field.
     *
     * @param  string  $field
     *
     * @return $this
     */
    public function sortAsc(string $field) : FluentFM
    {
        $this->sort($field);

        return $this;
    }


    /**
     * Sort results by field.
     *
     * @param  string  $field
     * @param  bool  $ascending
     *
     * @return self|FluentFM
     */
    public function sort(string $field, bool $ascending = true) : FluentFM
    {
        $this->query[ 'sort' ] = json_encode([
            [
                'fieldName' => $field,
                'sortOrder' => $ascending ? 'ascend' : 'descend',
            ],
        ]);

        return $this;
    }


    /**
     * Sort results descending by field.
     *
     * @param  string  $field
     *
     * @return $this
     */
    public function sortDesc(string $field) : FluentFM
    {
        $this->sort($field, false);

        return $this;
    }


    /**
     * Include portal data in results.
     *
     * @return self|FluentFM
     */
    public function withPortals() : FluentFM
    {
        $this->with_portals = true;

        return $this;
    }


    /**
     * Don't include portal data in results.
     *
     * @return self|FluentFM
     */
    public function withoutPortals() : FluentFM
    {
        $this->with_portals = false;

        return $this;
    }


    /**
     * @param $field
     *
     * @return self|FluentFM
     */
    public function whereEmpty($field) : FluentFM
    {
        return $this->where($field, '');
    }


    /**
     * @param       $field
     * @param  array  $params
     *
     * @return self|FluentFM
     */
    public function where($field, ...$params) : FluentFM
    {
        switch (\count($params)) {
            case  1:
                $value = '='.$params[ 0 ];
                break;
            case  2:
                $value = $params[ 0 ].$params[ 1 ];
                break;
            default:
                $value = '*';
        }

        $this->query[ 'query' ][ 0 ][ $field ] = $value;

        return $this;
    }


    /**
     * @param  string  $field
     *
     * @return self|FluentFM
     */
    public function whereNotEmpty(string $field) : FluentFM
    {
        return $this->has($field);
    }


    /**
     * @param  string  $field
     *
     * @return self|FluentFM
     */
    public function has(string $field) : FluentFM
    {
        return $this->where($field, '*');
    }


    /**
     * @return array
     */
    public function queryString() : array
    {
        $output = [];

        foreach ($this->query as $param => $value) {
            if (strpos($param, 'script') !== 0) {
                $param = '_'.$param;
            }

            $output[ $param ] = $value;
        }

        $output[ '_query' ] = null;

        return $output;
    }


    /**
     * Run FileMaker script with param before requested action.
     *
     * @param  string  $script
     * @param  null  $param
     *
     * @return self|FluentFM
     */
    public function prerequest(string $script, $param = null) : FluentFM
    {
        return $this->script($script, $param, 'prerequest');
    }


    /**
     * Run FileMaker script with param. If no type specified script will run
     * after requested action and sorting is complete.
     *
     * @param  string  $script
     * @param  null  $param
     * @param  string|null  $type
     *
     * @return self|FluentFM
     */
    public function script(string $script, $param = null, string $type = null) : FluentFM
    {
        $base = 'script';

        if ($type) {
            $base .= '.'.$type;
        }

        $this->query[ $base ]          = $script;
        $this->query[ $base.'.param' ] = $param;

        return $this;
    }


    /**
     * Run FileMaker script with param after requested action but before sort.
     *
     * @param  string  $script
     * @param  null  $param
     *
     * @return self|FluentFM
     */
    public function presort(string $script, $param = null) : FluentFM
    {
        return $this->script($script, $param, 'presort');
    }


    /**
     * Exclude records that have their deleted_at field set.
     *
     * @return FluentFM
     */
    public function withoutDeleted() : FluentFM
    {
        $this->with_deleted = false;

        return $this;
    }


    /**
     * Include records that have their deleted_at field set.
     *
     * @return FluentFM
     */
    public function withDeleted() : FluentFM
    {
        $this->with_deleted = true;

        return $this;
    }


    /**
     * Clear query parameters.
     *
     * @return self|FluentFM
     */
    protected function clearQuery() : FluentFM
    {
        $this->query = [
            'limit'                   => null,
            'offset'                  => null,
            'sort'                    => null,
            'query'                   => null,
            'script'                  => null,
            'script.param'            => null,
            'script.prerequest'       => null,
            'script.prerequest.param' => null,
            'script.presort'          => null,
            'script.presort.param'    => null,
        ];

        $this->with_portals = false;
        $this->with_deleted = true;

        return $this;
    }
}
