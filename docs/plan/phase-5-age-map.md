# Phase 5 — Age map

Outcome: `archaeo serve` opens a treemap of files colored by age (D18).

- [ ] **5.1 `resources/web/d3.min.js`**
  - Vendor D3 v7.9.0 (from `https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js`), keeping its ISC license header. Add `resources/web/LICENSE-d3`.

- [ ] **5.2 `Web\AgeMapData`** (plain class, unit-testable)
  - `tree(PDO): array`: a nested folder tree, where each leaf is `{path, lines, change_count, avg_age_days, born_at}`. Use one SQL aggregate over `line_ages JOIN commits` grouped by file.
  - Done when: the acme test checks the tree shape and one file's average age.

- [ ] **5.3 `resources/web/index.php`** (router)
  - Routes per D18. HTML: a d3 treemap sized by lines and colored by `avg_age_days` (sequential scale, with a legend), plus a tooltip with path, age and churn. No other JS libs.
  - Escape all output; open PDO read-only (`?mode=ro` URI).
  - Done when: manual check in a browser on the 1.7 project.

- [ ] **5.4 `ServeCommand`**
  - `--host=127.0.0.1 --port=8080`. Check that a finished dig exists. Run `Process([PHP_BINARY, '-S', "$host:$port", $router])` with `ARCHAEO_DB` env and no timeout, stream its output, and print the URL.
  - Done when: a command test checks the built Process arguments; a manual run works.
