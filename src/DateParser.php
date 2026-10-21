<?php


namespace Employees;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use ValueError;

final class DateParser
{
    public function parse(string $value): DateTimeImmutable
    {
        $value = trim($value, " \t\n\r\v\f");
        $utc = new DateTimeZone('UTC');
        foreach (['Y-m-d', 'Y/m/d', 'd.m.Y', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value, $utc);
            } catch (ValueError) {
                break;
            }
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format($format) === $value
            ) {
                return $date;
            }
        }

        throw new InvalidArgumentException('Invalid date; expected Y-m-d, Y/m/d, d.m.Y, d-m-Y, or d/m/Y.');
    }
}
