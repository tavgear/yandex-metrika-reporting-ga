<?php

declare(strict_types=1);

namespace Tvg\YandexMetrika\ReportingGa;

/**
 * Designed to work with API YandexMetrica reports
 * compatible with Google Analytics Core Reporting API (v3)
 *
 * @link https://yandex.ru/dev/metrika/doc/api2/ga/intro-docpage/ API documentation
 */
class Client
{

    /**
     * Address report API
     */
    const REQUEST_URL = 'https://api-metrika.yandex.net/analytics/v3/data/ga';

    /**
     * Today date shortcut
     */
    const DATE_TODAY = 'today';

    /**
     * Yesterday date shortcut
     */
    const DATE_YESTERDAY = 'yesterday';

    /**
     * Sample level shortcut (default)
     */
    const SAMPLING_LEVEL_DEFAULT = 'DEFAULT';

    /**
     * Sample level shortcut
     */
    const SAMPLING_LEVEL_FASTER = 'FASTER';

    /**
     * Sample level shortcut
     */
    const SAMPLING_LEVEL_HIGHER_PRECISION = 'HIGHER_PRECISION';

    private $authorizationToken = null;
    private $httpClient         = null;
    private $counterId          = null;
    private $metrics            = null;
    private $dimensions         = [];
    private $samplingLevel      = self::SAMPLING_LEVEL_DEFAULT;
    private $startDate          = self::DATE_TODAY;
    private $endDate            = self::DATE_TODAY;
    private $filters            = '';
    private $sort               = '';

    /**
     * Create report client with authorizationToken
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/intro/authorization-docpage/ How to get token
     *
     * @param string $authorizationToken
     */
    public function __construct(string $authorizationToken)
    {
        $this->authorizationToken = $authorizationToken;
        $this->httpClient         = new \GuzzleHttp\Client();
    }

    /**
     * Convert date in varios format
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    private function getDateValue($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        } elseif (is_int($date)) {
            return $date . 'daysAgo';
        }

        return $date;
    }

    /**
     * Build request params array
     *
     * @param int $start
     * @param int $count
     * @return array
     */
    private function buildRequestParams(int $start, int $count): array
    {
        $headers = [
            'Authorization'   => 'OAuth ' . $this->authorizationToken,
            'Accept-Encoding' => 'gzip, deflate',
        ];

        $params = [
            'ids'           => $this->counterId,
            'start-date'    => $this->startDate,
            'end-date'      => $this->endDate,
            'metrics'       => implode(',', $this->metrics),
            'dimensions'    => implode(',', $this->dimensions),
            'samplingLevel' => $this->samplingLevel,
            'filters'       => $this->filters,
            'sort'          => $this->sort,
            'start-index'   => $start,
            'max-results'   => $count,
        ];

        return [
            'query'   => $params,
            'headers' => $headers,
        ];
    }

    /**
     * Send a request to YandexMetrica.
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ Request manual
     *
     * @param int $start Start index report rows (start-index)
     * @param int $count Count of result rows (max-results)
     * @return \Tvg\YandexMetrika\ReportingGa\Report Report result
     * @throws Exception
     * @throws \GuzzleHttp\Exception\ClientException
     */
    public function request(int $start = 1, int $count = 1000): Report
    {
        if ($this->counterId === null or $this->metrics === null) {
            throw new Exception('Counter identifier and metric param must be specified', Exception::ERR_CODE);
        }

        try {
            $result = $this->httpClient->request('GET', self::REQUEST_URL, $this->buildRequestParams($start, $count));
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            if ($exc->hasResponse()) {
                $response    = $exc->getResponse();
                $yandexError = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
                $response->getBody()->rewind();
                if (isset($yandexError['error'])) {
                    $yandexError = $yandexError['error'];
                }

                $msg       = 'Report server error: ' . ($yandexError['message'] ?? ' for error detail see response ') . ' (code:' . ($yandexError['code'] ?? '-') . ')';
                $exception = new Exception($msg, Exception::ERR_CODE, $exc);

                $exception->setResponce($response);

                throw $exception;
            } else {
                throw $exc;
            }
        }

        return new Report(\GuzzleHttp\json_decode($result->getBody()->getContents(), true));
    }

    /**
     * Get the first row of the report. Used to quickly retrieve single row data.
     *
     * @return array First row with keys report fields
     */
    public function getRow(): array
    {
        return $this->request(1, 1)->getRowsAsArray()[0];
    }

    /**
     * Get all rows of a report.
     * The required number of requests to the YandexMetrica is performed automatically according
     * to the specified number of lines in one request
     *
     * Handler - callback(Report $result)
     *
     * @param int $rowsPerRequest Number of rows received in one request
     * @param callable $requestHandler The handler to which the result of each request is passed
     * @return \Traversable Rows in array, whose keys are report fields
     */
    public function getRows(int $rowsPerRequest = 1000, callable $requestHandler = null): \Traversable
    {
        $rowsCount = 0;

        while (true) {
            $result = $this->request($rowsCount + 1, $rowsPerRequest);

            if ($requestHandler !== null) {
                $requestHandler($result);
            }

            foreach ($result->getRows() as $row) {
                yield $row;
            }

            $rowsCount += $result->getRowsCount();
            if ($rowsCount >= $result->getAllRowsCount()) {
                break;
            }
        }
    }

    /**
     * Save reports in csv format (by fputcsv function).
     *
     * For csv params @see fputcsv
     *
     * @param string $path Full path to the file
     * @param bool $isWriteHeader Add header to file or not
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     * @return void
     */
    public function saveToCsv(string $path, bool $isWriteHeader = true, string $delimiter = ',', string $enclosure = '"',
                              string $escape_char = "\\"): void
    {
        $fp = fopen($path, 'w');

        $isFirstLine = true;
        foreach ($this->getRows() as $row) {
            if ($isWriteHeader and $isFirstLine) {
                fputcsv($fp, array_keys($row), $delimiter, $enclosure, $escape_char);
                $isFirstLine = false;
            }
            fputcsv($fp, $row, $delimiter, $enclosure, $escape_char);
        }

        fclose($fp);
    }

    /**
     * Get the count of total rows in a report
     *
     * @return int
     */
    public function getAllRowsCount(): int
    {
        return $this->request(1, 1)->getAllRowsCount();
    }

    /**
     * Set the report period. Dates can be specified in the formats:
     * - "YYYY-MM-DD"
     * - \DateTimeInterface
     * - DATE_TODAY, DATE_YESTERDAY constant
     * - Integer number. In this case, the number is interpreted as N days ago
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ doc
     *
     * @param type $from Date begin period
     * @param type $to Date end period
     * @return \self
     */
    public function setPeriod($from, $to = self::DATE_TODAY): self
    {
        $this->startDate = $this->getDateValue($from);
        $this->endDate   = $this->getDateValue($to);
        return $this;
    }

    /**
     * Set YandexMetrica counter identifier
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ doc
     *
     * @param mixed $id With "ga:" or not
     * @return \self
     */
    public function setCounterId($id): self
    {
        $this->counterId = preg_replace('/^[\s\D]*/', 'ga:', $id);
        return $this;
    }

    /**
     * Set metrica param
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ params
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/ga/terms-docpage/ metrics
     *
     * @param mixed $metrics Array of metrics or metrics string
     * @return \self
     */
    public function setMetrics($metrics): self
    {
        $this->metrics = is_array($metrics) ? $metrics : [$metrics];
        return $this;
    }

    /**
     * Set dimention param
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ params
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/ga/terms-docpage/ dimentions
     *
     * @param type $dimensions Array of dimentions or one string
     * @return \self
     */
    public function setDimensions($dimensions): self
    {
        $this->dimensions = is_array($dimensions) ? $dimensions : [$dimensions];
        return $this;
    }

    /**
     * Set filters param
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ params
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/segmentation-ga-docpage/ filters
     *
     * @param string $filters String of filters in the desired format
     * @return \self
     */
    public function setFilters(string $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Set sort param
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ params
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/segmentation-ga-docpage/ sort
     *
     * @param string $sort String of sort in the desired format
     * @return \self
     */
    public function setSort(string $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * Set samplingLevel param
     *
     * @link https://yandex.ru/dev/metrika/doc/api2/ga/queries/requestjson-docpage/ params
     *
     * @param string $samplingLevel SAMPLING_LEVEL_* constant
     * @return \self
     */
    public function setSamplingLevel(string $samplingLevel): self
    {
        $this->samplingLevel = $samplingLevel;
        return $this;
    }

}
