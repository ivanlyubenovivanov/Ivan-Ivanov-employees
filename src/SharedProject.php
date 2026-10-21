<?php


namespace Employees;

final readonly class SharedProject
{
    public function __construct(
        public int $projectId,
        public int $daysWorked,
    ) {
    }
}
