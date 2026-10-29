# Phase 2 — Excavation

Outcome: commits, file churn and per-line blame stored in `.archaeology/dig.sqlite`.

- [ ] **2.1 `tests/Support/GitRepoBuilder`**
  - Fluent builder: `GitRepoBuilder::create()->commit('2019-03-01', 'Alice <a@x>', ['src/A.php' => '<?php …'], 'msg')->…->build(): string $path`.
  - Sets `GIT_AUTHOR_DATE`, `GIT_COMMITTER_DATE`, name and email per commit. Runs `git init -b main`, with local `user.*` config and `commit.gpgsign=false`.
  - Writes to `sys_get_temp_dir()`; removes the repo in `tearDown`.
  - Also `tests/Support/Scenarios::acme()`: ~6 commits from 2018 to 2024 by 3 authors, with one dead class, one heavily used old class, and one file changed many times. It is reused in phases 3–4, so document its expected facts in a docblock.
  - Done when: a test builds acme and `git log` shows the scripted dates.

- [ ] **2.2 `Storage\Database` + `migrations/001_initial.sql`**
  - Schema from roadmap §7, plus `COLLATE NOCASE` on `fqn` and `to_fqn` (D10). Derived values (ages, fossils) are computed at query time, not stored as columns.
  - `Database::open(path)`: creates the dir, sets `PRAGMA foreign_keys=ON`, runs pending migrations by `user_version` (D16), sets `ERRMODE_EXCEPTION`.
  - `reset()` deletes all rows except `digs`. `transaction(callable)`.
  - Done when: tests cover a fresh DB at `user_version=1`, reopening (no re-migration), and reset.

- [ ] **2.3 `Model\Commit` + `Excavator\GitLogReader`**
  - Runs `git log HEAD --format=%x1e%H%x1f%an%x1f%ae%x1f%aI%x1f%s --name-only`. Split records on `\x1e` and fields on `\x1f`; the rest of a record is file paths.
  - Returns `iterable<Commit>` and `array<string,int> $churn` (D6–D8). Convert dates to UTC Z.
  - `shortcut: buffers whole git log output, stream via Process iterator if memory hurts on huge repos.`
  - Done when: unit test on a captured string (including a subject with unicode, a merge commit and an empty-file commit) + an acme integration test.

- [ ] **2.4 `Excavator\FileFinder`**
  - Lists `git ls-tree -r --name-only -z HEAD`, then filters by Config `paths`/`exclude` (D9) and `*.php`.
  - Done when: unit tests cover prefix vs segment boundary (`vendor` vs `vendors`), wildcard, and nested exclude.

- [ ] **2.5 `Excavator\GitBlameReader`**
  - `blame(path): array<int line, string hash>` from `git blame --line-porcelain HEAD -- <path>`. Take the hash from each header line; content lines start with `\t`.
  - `source(path): string` via `git show HEAD:<path>`.
  - Blame can name commits not in `commits` only if history is weird. In that case insert them from the porcelain `author*` fields.
  - Done when: acme test with exact line → commit mapping for a file edited in 3 commits.

- [ ] **2.6 Temporary `excavate` (log + blame only)**
  - Wire 2.2–2.5 into `ExcavateCommand`: dig row `running` → reset → insert commits → insert files with churn → line_ages → `finished`; `failed` + rethrow on exception.
  - Use a transaction and prepared statements. Warn if shallow (roadmap §13) or dirty (D5). Show a progress bar over files. D17 applies to per-file failures.
  - Done when: the command test on acme asserts row counts. Running it on this package's host test project takes < 1 min.
