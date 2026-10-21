<?php

namespace Employees;

use DateTimeImmutable;

final class EmployeePairFinder
{
    public function find(array $assignments): ?EmployeePairResult
    {
        // групираме участията по проект и служител.
        $projects = [];

        foreach ($assignments as $assignment) {
            $projects[$assignment->projectId][$assignment->employeeId][] = [
                $assignment->dateFrom,
                $assignment->dateTo,
            ];
        }

        $pairs = [];

        foreach ($projects as $projectId => $employees) {
            
			ksort($employees, SORT_NUMERIC);

            foreach ($employees as $employeeId => $periods) {
                $employees[$employeeId] = $this->merge($periods);
            }

            $employeeIds = array_keys($employees);
            $employeeCount = count($employeeIds);

            for ($i = 0; $i < $employeeCount; $i++) {
                for ($j = $i + 1; $j < $employeeCount; $j++) {
                    $firstId = $employeeIds[$i];
                    $secondId = $employeeIds[$j];

                    $days = $this->sharedDays(
                        $employees[$firstId],
                        $employees[$secondId]
                    );

                    if ($days > 0) {
                        $pairs[$firstId][$secondId][$projectId] = $days;
                    }
                }
            }
        }

        $best = null;

        foreach ($pairs as $firstId => $partners) {
            foreach ($partners as $secondId => $daysByProject) {
                $totalDays = array_sum($daysByProject);

                if ($best !== null) {
                    if ($totalDays < $best->totalDays) {
                        continue;
                    }

                    if ($totalDays === $best->totalDays) {
                        if ($firstId > $best->employeeId1) {
                            continue;
                        }

                        if ($firstId === $best->employeeId1 && $secondId >= $best->employeeId2) {
                            continue;
                        }
                    }
                }

                ksort($daysByProject, SORT_NUMERIC);

                $sharedProjects = [];

                foreach ($daysByProject as $projectId => $days) {
                    $sharedProjects[] = new SharedProject($projectId, $days);
                }

                $best = new EmployeePairResult($firstId, $secondId, $totalDays, $sharedProjects);
            }
        }

        return $best;
    }

    private function merge(array $periods): array
    {
        usort($periods, static function (array $first, array $second): int {
            if ($first[0] < $second[0]) {
                return -1;
            }

            if ($first[0] > $second[0]) {
                return 1;
            }

            return 0;
        });

        $merged = [];

        foreach ($periods as $period) {
            $start = $period[0];
            $end = $period[1];
            $lastIndex = count($merged) - 1;

            if ($lastIndex >= 0 && $start <= $merged[$lastIndex][1]) {
                if ($end > $merged[$lastIndex][1]) {
                    $merged[$lastIndex][1] = $end;
                }
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }

    private function sharedDays(array $first, array $second): int
    {
        $firstIndex = 0;
        $secondIndex = 0;
        $days = 0;

        $firstCount = count($first);
        $secondCount = count($second);

        while ($firstIndex < $firstCount && $secondIndex < $secondCount) {
            $firstPeriod = $first[$firstIndex];
            $secondPeriod = $second[$secondIndex];

            $start = max($firstPeriod[0], $secondPeriod[0]);
            $end = min($firstPeriod[1], $secondPeriod[1]);

            if ($start <= $end) {
                $days += (int) $start->diff($end)->days + 1;
            }

            if ($firstPeriod[1] <= $secondPeriod[1]) {
                $firstIndex++;
            } else {
                $secondIndex++;
            }
        }

        return $days;
    }
}