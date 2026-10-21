<?php


namespace Employees;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class ProjectAssignment
{
    public DateTimeImmutable $dateFrom;
    public DateTimeImmutable $dateTo;

    public function __construct(
        public int $employeeId,
        public int $projectId,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ) {
        if ($employeeId <= 0 || $projectId <= 0) {
            throw new InvalidArgumentException('Employee and project IDs must be positive integers.');
        }

        $utc = new DateTimeZone('UTC');
        $this->dateFrom = new DateTimeImmutable($dateFrom->format('Y-m-d'), $utc);
        $this->dateTo = new DateTimeImmutable($dateTo->format('Y-m-d'), $utc);

        if ($this->dateTo < $this->dateFrom) {
            throw new InvalidArgumentException('End date must not precede start date.');
        }
    }
}
