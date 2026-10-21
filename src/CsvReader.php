<?php

namespace Employees;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class CsvReader
{
    public function read(string $path, DateTimeImmutable $today): array
    {
        $file = @fopen($path, 'rb');

        if ($file === false) {
            throw new RuntimeException('Cannot open CSV file for reading.');
        }

        try {
            // Skip the UTF-8 BOM before reading CSV fields.
            if (fread($file, 3) !== "\xEF\xBB\xBF") {
                rewind($file);
            }

            $parser = new DateParser();
            $assignments = [];
            $recordNumber = 0;
            $firstNonEmpty = true;
            $fields = ['EmpID', 'ProjectID', 'DateFrom', 'DateTo'];

            while (($record = fgetcsv($file, null, ',', '"', '')) !== false) {
                
				++$recordNumber;

                if (
                    count($record) === 1 && trim($record[0] ?? '', " \t\n\r\v\f") === '') {
						continue;
					}

                if (count($record) !== 4) {
                    throw new InvalidArgumentException("CSV record {$recordNumber}: expected exactly 4 fields.");
                }

                foreach ($record as $index => $value) {
                    $record[$index] = trim($value ?? '', " \t\n\r\v\f");
                }

                foreach ($record as $index => $value) {
                    if (str_contains($value, "\xEF\xBB\xBF")) {
                        throw new InvalidArgumentException("CSV record {$recordNumber}, {$fields[$index]}: " . 'UTF-8 BOM is allowed only at the beginning of the file.');
                    }
                }

                if ($firstNonEmpty) {
                    $header = [];

                    foreach ($record as $value) {
                        $header[] = strtolower($value);
                    }

                    $firstNonEmpty = false;

                    if ($header === ['empid', 'projectid', 'datefrom', 'dateto']) {
                        continue;
                    }
                }

                $employeeId = $this->parseId($record[0], $recordNumber, 'EmpID');
                $projectId = $this->parseId($record[1], $recordNumber, 'ProjectID');
                $dateFrom = $this->parseDate($parser, $record[2], $recordNumber, 'DateFrom');

                if (strcasecmp($record[3], 'NULL') === 0) {
                    $dateTo = $today;
                } else {
                    $dateTo = $this->parseDate($parser, $record[3], $recordNumber, 'DateTo');
                }

                try {
                    $assignments[] = new ProjectAssignment($employeeId, $projectId, $dateFrom, $dateTo);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("CSV record {$recordNumber}, DateTo: " . $exception->getMessage(), 0, $exception);
                }
            }

            if (!feof($file)) {
                throw new RuntimeException('Failed to read CSV file.');
            }

            if ($assignments === []) {
                throw new InvalidArgumentException('CSV file contains no data records.');
            }

            return $assignments;
        } finally {
            fclose($file);
        }
    }

    private function parseId(string $value, int $recordNumber, string $field): int {
        
		$digits = ltrim($value, '0');
        $maximum = (string) PHP_INT_MAX;

        if (preg_match('/\A[0-9]+\z/', $value) !== 1 || $digits === '' || strlen($digits) > strlen($maximum) || 
			( strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0 )
        ) {
            throw new InvalidArgumentException("CSV record {$recordNumber}, {$field}: " . 'expected a positive ASCII integer no greater than PHP_INT_MAX.');
        }

        return (int) $digits;
    }

    private function parseDate(DateParser $parser, string $value, int $recordNumber, string $field): DateTimeImmutable {
        try {
            return $parser->parse($value);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                "CSV record {$recordNumber}, {$field}: "
                . $exception->getMessage(),
                0,
                $exception
            );
        }
    }
}