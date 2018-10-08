<?php

namespace App\Services;

/**
 * Created by PhpStorm.
 * User: akkadius
 * Date: 10/5/18
 * Time: 7:01 AM
 */

use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Class DataDumpReaderService
 */
class DataDumpReaderService
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var \League\Csv\Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $csv_headers;

    /**
     * @var array
     */
    private $csv_header_data_type;

    /**
     * @var array
     */
    private $csv_data;

    /**
     * @param string $file
     * @return DataDumpReaderService
     */
    public function setFile(string $file): DataDumpReaderService
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return $this
     * @throws \League\Csv\Exception
     */
    public function parse(): DataDumpReaderService
    {
        $this->parseHeaders()->parseData();

        return $this;
    }

    /**
     * @return DataDumpReaderService
     */
    public function initReader(): DataDumpReaderService
    {
        $this->reader = Reader::createFromPath(storage_path('app') . '/' . $this->file);

        return $this;
    }

    /**
     * @return $this
     * @throws \League\Csv\Exception
     */
    private function parseHeaders(): DataDumpReaderService
    {
        $statement = (new Statement())
            ->offset(0)
            ->limit(1);

        $header_columns = [];
        $records        = $statement->process($this->getReader());
        foreach ($records as $row) {
            foreach ($row as $column) {

                /**
                 * Strip // +
                 */
                if (strpos($column, '//') !== false) {
                    $column = strstr($column, "//", true);
                }

                /**
                 * Strip /* +
                 */
                if (strpos($column, '/*') !== false) {
                    $column = strstr($column, "/*", true);
                }

                /**
                 * Strip 0x +
                 */
                if (strpos($column, '0x') !== false) {
                    $column = strstr($column, "0x", true);
                }

                /**
                 * Trim
                 */
                $column = trim($column);

                /**
                 * Remove extra spaces
                 */

                $column = str_replace(" ", "", $column);

                /**
                 * Decamelize to snake_case
                 */
                $column = $this->decamelize($column);

                /**
                 * Strip some special characters
                 */
                $column = str_replace([".", "[", "]", "(", ")"], "", $column);

                $header_columns[] = $column;
            }
        }

        /**
         * Parse data types
         */
        $statement = (new Statement())
            ->offset(1)
            ->limit(1);

        $headers_data_type = [];
        $index             = 0;
        $records           = $statement->process($this->getReader());
        foreach ($records as $row) {
            foreach ($row as $data_type) {
                $column_name                     = array_get($header_columns, $index);
                $headers_data_type[$column_name] = $data_type;
                $index++;
            }
        }

        /**
         * Set headers and data type references separately
         */
        $this->setCsvHeaderDataType($headers_data_type);
        $this->setCsvHeaders($header_columns);

        return $this;
    }

    /**
     * @return DataDumpReaderService
     * @throws \League\Csv\Exception
     */
    private function parseData(): DataDumpReaderService
    {
        /**
         * Start at row three for reading data
         */
        $statement = (new Statement())
            ->offset(2);

        /**
         * Get header names
         */
        $header_names = $this->getCsvHeaders();

        /**
         * Iterate through CSV rows
         */
        $records = $statement->process($this->getReader());
        $rows    = [];
        foreach ($records as $row) {

            /**
             * Set data via associate keys
             *
             * Example:
             *   "jump_strength" => "1.350000"
             *   "swim_strength" => "3.375000"
             *   "speed_multiplier" => "0.400000"
             *   "area_friction" => "0.625000"
             */
            $row_data = [];
            $index    = 0;
            foreach ($row as $data) {
                $column_name            = array_get($header_names, $index);
                $row_data[$column_name] = $data;
                $index++;
            }
            $rows[] = $row_data;
        }

        $this->setCsvData($rows);

        return $this;
    }

    /**
     * @param $string
     * @return string
     */
    private function decamelize($string)
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * @return Reader
     */
    public function getReader(): Reader
    {
        return $this->reader;
    }

    /**
     * @param Reader $reader
     */
    public function setReader(Reader $reader): DataDumpReaderService
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * @return array
     */
    public function getCsvHeaders(): array
    {
        return $this->csv_headers;
    }

    /**
     * @param array $csv_headers
     */
    public function setCsvHeaders(array $csv_headers): void
    {
        $this->csv_headers = $csv_headers;
    }

    /**
     * @return array
     */
    public function getCsvData(): array
    {
        return $this->csv_data;
    }

    /**
     * @param array $csv_data
     */
    public function setCsvData(array $csv_data): void
    {
        $this->csv_data = $csv_data;
    }

    /**
     * @return array
     */
    public function getCsvHeaderDataType(): array
    {
        return $this->csv_header_data_type;
    }

    /**
     * @param array $csv_header_data_type
     */
    public function setCsvHeaderDataType(array $csv_header_data_type): void
    {
        $this->csv_header_data_type = $csv_header_data_type;
    }
}