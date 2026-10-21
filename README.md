# Employees — Shared Projects

A small object-oriented PHP application that finds the pair of employees with
the most shared working days across common projects.

## Requirements and startup

Only PHP 8.2+ is required. No database, framework, Composer, external
dependencies, or dependency installation is needed.

From the project root, run:

```sh
php -S localhost:8000 -t public
```

Open http://localhost:8000.

Alternatively, with XAMPP Apache running and the project under `htdocs`, open
http://localhost/Ivan-Ivanov-employees/public/.

## Usage

Choose a CSV file and click **Analyze**. The result shows the winning employee
IDs, total shared days, and a project breakdown. The maximum file size is
**2 MiB (2,097,152 bytes)**, checked against the actual temporary upload file;
PHP server limits may be lower. Uploaded files are processed from PHP's
temporary location and are not saved in the project. There are no sessions or
persistent storage.

## CSV input

Each data record must have exactly four comma-separated fields:

```csv
EmpID,ProjectID,DateFrom,DateTo
1,10,2024-01-01,2024-01-10
2,10,2024-01-05,2024-01-15
```

- The header is optional. If present as the first non-empty record, its names
  and order must match the example, ignoring case and outer whitespace.
- A UTF-8 BOM is accepted only at the beginning of the file.
- Outer whitespace is removed from fields. Blank records are skipped; a record
  containing four empty fields is invalid. Empty files and headers without
  data are rejected.
- IDs must contain only ASCII digits and represent positive integers up to
  `PHP_INT_MAX`. Leading zeros are allowed; range checking happens before cast.
- Case-insensitive `NULL` in DateTo is replaced with today's date. The web
  entry point calculates today once per request in UTC. Empty dates and NULL
  in DateFrom are invalid. Future dates are allowed, but the end cannot precede
  the start.

Any data record that fails field or value validation rejects the entire input. 
Validation messages identify the logical CSV record number and field where applicable. 
Headers and skipped blank records count towards numbering; a quoted multiline field 
is still part of one record.

## Dates

Supported formats are `Y-m-d`, `Y/m/d`, `d.m.Y`, `d-m-Y`, and `d/m/Y`.
Months and days require leading zeros. Year-last slash dates always mean
day/month/year: `01/02/2024` is February 1.

Dates are parsed strictly, including calendar validation, and normalized to
midnight UTC. Impossible dates such as `2024-02-30` are rejected.

## Calculation rules

- Both start and end dates count. January 1–10 and January 5–15 share six days.
- Assignments are grouped by project and employee. Overlapping periods for the
  same employee on the same project are merged before comparing different
  employees, preventing duplicate counting. Gaps between periods remain.
- Sorted, merged periods are intersected using two pointers. Shared days are
  summed across all common projects. The same date on different projects
  counts separately for each project.
- Ties select the numerically smallest first employee ID, then the smallest
  second ID. Employee IDs in the result are ascending. Only projects with
  positive shared days appear, sorted numerically by project ID.
- If no two different employees overlap on a project, the finder returns
  `null` and the page displays a no-shared-period message.

## Structure

- `bootstrap.php` opens `src/`, iterates its immediate files, and loads each
  file with a lowercase `.php` extension using `require_once`. Paths are based
  on `__DIR__`; this is directory-based loading, not PSR-4 autoloading or a
  fixed list of class includes.
- `src/` contains classes in the `Employees` namespace: `ProjectAssignment`
  validates IDs and date ranges; `DateParser` strictly converts dates;
  `CsvReader` reads and validates assignments; `EmployeePairFinder` calculates
  the winner; `EmployeePairResult` and `SharedProject` hold the result and its
  project breakdown.
- `public/index.php` handles requests and uploads, calls the reader and finder,
  and renders `templates/results.php`. `public/style.css` provides the styling.
  Dynamic output is HTML-escaped; unexpected errors are logged and shown as a
  generic message.
- `examples/` contains files for manual checking.

## Manual checks

Upload each example through the form. The following results were checked
directly through `bootstrap.php`, `CsvReader`, and `EmployeePairFinder`:

| File | Expected result |
| --- | --- |
| `examples/valid.csv` | Employees 1 and 2; 9 days total. Project 2: 3 days; project 10: 6 days. |
| `examples/no-overlap.csv` | No shared period (`null`). |
| `examples/invalid-date.csv` | `InvalidArgumentException` at CSV record 2, DateFrom, for February 30. |
| `examples/employees-large.csv` | Employees 143 and 218; 3654 days total. Projects 9001 and 9002: 1827 days each. |

These examples use fixed dates. No PHPUnit suite is included. The checks above
cover the core calculation, not browser rendering or HTTP upload behavior.

## Limitations

Input is expected to be properly formed CSV. Reading uses standard `fgetcsv`
with a comma delimiter, double-quote enclosure, and an empty escape parameter;
there is no separate CSV syntax validator. Only the explicitly listed date
formats are supported.
