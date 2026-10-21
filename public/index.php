<?php

use Employees\CsvReader;
use Employees\EmployeePairFinder;

ini_set('display_errors', '0');
require_once dirname(__DIR__) . '/bootstrap.php';

$today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
$result = null;
$error = null;
$analyzed = false;
$maxBytes = 2 * 1024 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $postLimit = trim((string) ini_get('post_max_size'));
        $postLimitBytes = (float) $postLimit;
        $suffix = strtolower(substr($postLimit, -1));

        switch ($suffix) {
            case 'g':
                $postLimitBytes *= 1024 * 1024 * 1024;
                break;

            case 'm':
                $postLimitBytes *= 1024 * 1024;
                break;

            case 'k':
                $postLimitBytes *= 1024;
                break;
        }

        if (
            $_POST === []
            && $_FILES === []
            && $postLimitBytes > 0
            && (float) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $postLimitBytes
        ) {
            throw new InvalidArgumentException(
                'The request exceeds the server upload limit. Choose a CSV file up to 2 MiB.'
            );
        }

        if ($_FILES === []) {
            throw new InvalidArgumentException(
                'Please choose a CSV file to analyze.'
            );
        }

        $upload = $_FILES['csv'] ?? null;

        if (count($_FILES) !== 1 || !is_array($upload)) {
            throw new InvalidArgumentException(
                'Invalid upload. Please choose one CSV file.'
            );
        }

        if (!isset(
            $upload['name'],
            $upload['type'],
            $upload['tmp_name'],
            $upload['error'],
            $upload['size']
        )) {
            throw new InvalidArgumentException(
                'Invalid upload. Please choose one CSV file.'
            );
        }

        if (
            !is_string($upload['name'])
            || !is_string($upload['type'])
            || !is_string($upload['tmp_name'])
            || !is_int($upload['error'])
            || !is_int($upload['size'])
            || $upload['size'] < 0
        ) {
            throw new InvalidArgumentException(
                'Invalid upload. Please choose one CSV file.'
            );
        }

        if ($upload['error'] !== UPLOAD_ERR_OK) {
            switch ($upload['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new InvalidArgumentException(
                        'The file exceeds the upload limit. Choose a CSV file up to 2 MiB.'
                    );

                case UPLOAD_ERR_PARTIAL:
                    throw new InvalidArgumentException(
                        'The file was only partially uploaded. Please try again.'
                    );

                case UPLOAD_ERR_NO_FILE:
                    throw new InvalidArgumentException(
                        'Please choose a CSV file to analyze.'
                    );

                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    throw new RuntimeException(
                        'Server upload failure, code ' . $upload['error']
                    );

                default:
                    throw new InvalidArgumentException(
                        'Invalid upload error. Please try again.'
                    );
            }
        }

        if (!is_uploaded_file($upload['tmp_name'])) {
            throw new InvalidArgumentException(
                'Invalid upload. Please upload the file again.'
            );
        }

        $actualSize = filesize($upload['tmp_name']);

        if ($actualSize === false) {
            throw new RuntimeException(
                'Cannot determine uploaded file size.'
            );
        }

        if ($actualSize > $maxBytes) {
            throw new InvalidArgumentException(
                'The file is too large. The maximum size is 2 MiB.'
            );
        }

        $reader = new CsvReader();
        $assignments = $reader->read($upload['tmp_name'], $today);

        $finder = new EmployeePairFinder();
        $result = $finder->find($assignments);

        $analyzed = true;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        http_response_code(400);
    } catch (Throwable $exception) {
        error_log((string) $exception);
        $error = 'Unable to analyze the file right now. Please try again later.';
        http_response_code(500);
    }
}

header('Content-Type: text/html; charset=UTF-8');
require dirname(__DIR__) . '/templates/results.php';
