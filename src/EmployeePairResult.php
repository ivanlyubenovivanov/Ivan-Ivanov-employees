<?php


namespace Employees;

final readonly class EmployeePairResult
{
    /** @param list<SharedProject> $projects */
    public function __construct(
        public int $employeeId1,
        public int $employeeId2,
        public int $totalDays,
        public array $projects,
    ) {
    }
}
