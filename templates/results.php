<?php
	$escape = static fn (string|int $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees — Shared Projects</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <header>
        <h1>Employees — Shared Projects</h1>
        <p>Find the pair of employees who worked together the longest across shared projects.</p>
    </header>
    <section class="card" aria-labelledby="upload-title">
        <h2 id="upload-title">Analyze a CSV file</h2>
        <form method="post" enctype="multipart/form-data">
            <label for="csv">CSV file</label>
            <input id="csv" name="csv" type="file" accept=".csv,text/csv" required aria-describedby="csv-help">
            <p id="csv-help" class="help">Maximum 2 MiB. Columns: EmpID, ProjectID, DateFrom, DateTo. Header optional.</p>
            <button type="submit">Analyze</button>
        </form>
        <p class="help">Dates: Y-m-d, Y/m/d, d.m.Y, d-m-Y, or d/m/Y, with leading zeros.
            01/02/2024 means February 1. Use NULL in DateTo for today in UTC.
            Uploaded files are not stored.</p>
    </section>
    <?php if ($error !== null): ?>
        <section class="notice error" role="alert">
            <h2>Unable to analyze this file</h2>
            <p><?= $escape($error) ?></p>
        </section>
    <?php elseif ($analyzed && $result === null): ?>
        <section class="notice" role="status">
            <h2>No shared period found</h2>
            <p>No pair of different employees worked together on a shared project during overlapping dates.</p>
        </section>
    <?php elseif ($result !== null): ?>
        <section class="card" aria-labelledby="result-title">
            <h2 id="result-title">Longest collaboration</h2>
            <dl class="result-fields">
                <div><dt>Employee ID #1</dt><dd><?= $escape($result->employeeId1) ?></dd></div>
                <div><dt>Employee ID #2</dt><dd><?= $escape($result->employeeId2) ?></dd></div>
                <div><dt>Total days</dt><dd><?= $escape($result->totalDays) ?></dd></div>
            </dl>
        </section>
        <section class="card" aria-labelledby="projects-title">
            <h2 id="projects-title">Shared projects</h2>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Shared project breakdown">
                <table>
                    <caption>Shared days by project (both endpoints included)</caption>
                    <thead><tr>
                        <th scope="col">Employee ID #1</th>
                        <th scope="col">Employee ID #2</th>
                        <th scope="col">Project ID</th>
                        <th scope="col">Days worked</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($result->projects as $project): ?>
                        <tr>
                            <td><?= $escape($result->employeeId1) ?></td>
                            <td><?= $escape($result->employeeId2) ?></td>
                            <td><?= $escape($project->projectId) ?></td>
                            <td><?= $escape($project->daysWorked) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="help">Work on different projects on the same date counts separately for each project.</p>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
