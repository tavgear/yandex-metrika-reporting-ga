<?php

declare(strict_types=1);

namespace Tvg\YandexMetrika\ReportingGa;

/**
 * Represents result of a request to the YandexMetrika
 */
class Report
{

    /**
     *
     * @var array Row data
     */
    private $data = null;

    /**
     *
     * @var array Report fields names
     */
    private $headersNames = [];

    /**
     * Create an report object based on the YandexMetrika response
     *
     * @param array $data Decoded response from YandexMetrika
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        foreach ($this->data['columnHeaders'] as $header) {
            $this->headersNames[] = $header['name'];
        }
    }

    /**
     * Get report fields
     *
     * @return array Field names
     */
    public function getHeader(): array
    {
        return $this->headersNames;
    }

    /**
     * Get raw response from YandexMetrika
     *
     * @return array Decoded response
     */
    public function getRawData(): array
    {
        return $this->data;
    }

    /**
     * Get all response rows
     *
     * @return \Traversable Rows in array of values, with report fields as keys
     */
    public function getRows(): \Traversable
    {
        foreach ($this->data['rows'] as $row) {
            yield array_combine($this->headersNames, $row);
        }
    }

    /**
     * Get all response rows as an array
     *
     * @return array Array of all rows
     */
    public function getRowsAsArray(): array
    {
        $rows = [];

        foreach ($this->getRows() as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Get all rows count of report
     *
     * @return int
     */
    public function getAllRowsCount(): int
    {
        return (int) $this->data['totalResults'];
    }

    /**
     * Get rows count of response data
     *
     * @return int
     */
    public function getRowsCount(): int
    {
        return (int) count($this->data['rows']);
    }

}
