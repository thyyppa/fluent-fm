<?php

namespace Hyyppa\FluentFM\Contract;

use Hyyppa\FluentFM\Exception\FilemakerException;

/**
 * Interface FluentFM.
 */
interface FluentFM
{
    /**
     * Get record by record id.
     *
     * @param $layout
     * @param $id
     * @return FluentFM
     */
    public function record($layout, $id): self;

    /**
     * Get records from filemaker table.
     *
     * @param  $layout
     * @param  null  $id
     * @return FluentFM
     */
    public function records($layout, $id = null): self;

    /**
     * Get metadata from filemaker table.
     *
     * @param  $layout
     * @return FluentFM
     */
    public function metadata($layout): self;

    /**
     * Find records matching current query parameters.
     *
     * @param  string  $layout
     * @return mixed
     */
    public function find(string $layout);

    /**
     * Creates new filemaker record on table.
     *
     * @param  string  $layout
     * @param  array  $fields
     * @return int|mixed
     *
     * @throws FilemakerException
     */
    public function create(string $layout, array $fields = []);

    /**
     * @param  string  $layout
     * @param  array  $fields
     * @return bool
     *
     * @throws FilemakerException
     */
    public function globals(string $layout, array $fields = []): bool;

    /**
     * Update record with given recordId. If recordId is not given
     * updates will be applied to all records matching the current
     * query parameters.
     *
     * @param  string  $layout
     * @param  array  $fields
     * @param  int|null  $recordId
     * @return mixed
     */
    public function update(string $layout, array $fields = [], int $recordId = null);

    /**
     * Upload a file to a container in recordId, if no record id specified
     * file will be added to all records matching current query.
     *
     * @param  string  $layout
     * @param  string  $field
     * @param  string  $filename
     * @param  int|null  $recordId
     * @return mixed
     */
    public function upload(string $layout, string $field, string $filename, int $recordId = null);

    /**
     * Download contents of container field to directory.
     * If no record id is specified the file will be downloaded for all records matching current query.
     * Files will be named using the id field of the record and the original file extension.
     *
     * @param  string  $layout
     * @param  string  $field
     * @param  string  $output_dir
     * @param  int|null  $recordId
     * @return mixed|self
     */
    public function download(string $layout, string $field, string $output_dir = './', int $recordId = null);

    /**
     * Delete record from table. If record id not provided all records matching
     * current query will be removed.
     *
     * @param  string  $layout
     * @param  int|null  $recordId
     * @return mixed
     */
    public function delete(string $layout, int $recordId = null);

    /**
     * Sets deleted_at field on table for recordId. If no recordId
     * specified all matching the current query will be set.
     * This won't update records that have already been soft deleted.
     *
     * @param  string  $layout
     * @param  int|null  $recordId
     * @return mixed
     */
    public function softDelete(string $layout, int $recordId = null);

    /**
     * Clears deleted_at field on table for recordId. If no recordId
     * specified all matching the current query will be cleared.
     *
     * @param  string  $layout
     * @param  int|null  $recordId
     * @return mixed
     */
    public function undelete(string $layout, int $recordId = null);

    /**
     * Get fields for Filemaker table.
     *
     * @param  string  $layout
     * @return array
     *
     * @throws FilemakerException
     */
    public function fields(string $layout): array;

    /**
     * Request new token from filemaker.
     * Useful for tasks delayed in queue.
     *
     * Try this if you are getting 952 errors from filemaker.
     */
    public function refreshToken(): void;

    public function logout(): void;

    /**
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function exec();

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function get();

    /**
     * Get the most recently created record in table.
     *
     * @param  string  $layout
     * @param  string  $field
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function latest(string $layout, string $field = 'created_at');

    /**
     * Get the most recently updated record in table.
     *
     * @param  string  $layout
     * @param  string  $field
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function lastUpdate(string $layout, string $field = 'updated_at');

    /**
     * Get the oldest record in table.
     *
     * @param  string  $layout
     * @param  string  $field
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function oldest(string $layout, string $field = 'created_at');

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function first();

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function last();

    /**
     * Limit the number of results returned.
     *
     * @param  int  $limit
     * @return FluentFM
     */
    public function limit(int $limit): self;

    /**
     * Begin result set at the given record id.
     *
     * @param  int  $offset
     * @return FluentFM
     */
    public function offset(int $offset): self;

    /**
     * Sort results by field.
     *
     * @param  string  $field
     * @param  bool  $ascending
     * @return FluentFM
     */
    public function sort(string $field, bool $ascending = true): self;

    /**
     * Sort results ascending by field.
     *
     * @param  string  $field
     * @return FluentFM
     */
    public function sortAsc(string $field): self;

    /**
     * Sort results descending by field.
     *
     * @param  string  $field
     * @return FluentFM
     */
    public function sortDesc(string $field): self;

    /**
     * Include portal data in results.
     *
     * @return FluentFM
     */
    public function withPortals(): self;

    /**
     * Don't include portal data in results.
     *
     * @return FluentFM
     */
    public function withoutPortals(): self;

    /**
     * @param  $field
     * @param  array  $params
     * @return FluentFM
     */
    public function where($field, ...$params): self;

    /**
     * @param $field
     * @return FluentFM
     */
    public function whereEmpty($field): self;

    /**
     * @param  string  $field
     * @return FluentFM
     */
    public function has(string $field): self;

    /**
     * @param  string  $field
     * @return FluentFM
     */
    public function whereNotEmpty(string $field): self;

    /**
     * Include records that have their deleted_at field set.
     *
     * @return FluentFM
     */
    public function withDeleted(): self;

    /**
     * Exclude records that have their deleted_at field set.
     *
     * @return FluentFM
     */
    public function withoutDeleted(): self;

    /**
     * Run FileMaker script with param. If no type specified script will run
     * after requested action and sorting is complete.
     *
     * @param  string  $script
     * @param  null  $param
     * @param  string|null  $type
     * @return FluentFM
     */
    public function script(string $script, $param = null, string $type = null): self;

    /**
     * Run FileMaker script with param before requested action.
     *
     * @param  string  $script
     * @param  null  $param
     * @return FluentFM
     */
    public function prerequest(string $script, $param = null): self;

    /**
     * Run FileMaker script with param after requested action but before sort.
     *
     * @param  string  $script
     * @param  null  $param
     * @return FluentFM
     */
    public function presort(string $script, $param = null): self;

    /**
     * Clear query parameters.
     *
     * @return self|FluentFM
     */
    public function clearQuery(): self;
}
