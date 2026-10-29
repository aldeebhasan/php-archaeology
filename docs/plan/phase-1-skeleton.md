# Phase 1 — Package skeleton

Outcome: `php bin/archaeo init` works in a real project installed via a Composer path repository.

- [ ] **1.1 Project files**
  - `composer.json`: start from roadmap §10, apply the D2 ranges, add `phpstan/phpstan` to require-dev (D19), and add scripts `test`, `stan`, `check` (= test + stan).
  - `phpunit.xml.dist` (testsuite `tests/`, fail on warning/risky), `phpstan.neon.dist` (level 8, paths `src` `bin` `tests`).
  - `.gitattributes` (roadmap §10 + `/docs export-ignore`, `/CLAUDE.md export-ignore`, `/.claude export-ignore`, `/phpstan.neon.dist export-ignore`).
  - `tests/SmokeTest.php`, a trivial test so the suite isn't empty.
  - Done when: `composer install && composer check` is green.

- [ ] **1.2 `bin/archaeo`**
  - Entry point per roadmap §10 that registers commands via the D3 compat. Start with `InitCommand` only.
  - `chmod +x`.
  - Done when: `php bin/archaeo list` shows `init`.

- [ ] **1.3 `Project\ProjectLocator`**
  - `locate(string $cwd): Project`. Walks up to the first `composer.json`, then runs `git rev-parse --show-toplevel` there.
  - Throws `ProjectNotFound` with roadmap §3's exact message when there is no git repo.
  - `Project` (readonly): `root`, `dataDir` (`root/.archaeology`), `dbPath`, `configPath`.
  - Also `isShallow()` (`git rev-parse --is-shallow-repository`) and `isDirty()` (`git status --porcelain`), both used later.
  - Done when: tests cover nested cwd, no composer.json, no git, and a shallow clone (`git clone --depth=1 file://…`).

- [ ] **1.4 `Project\Config`**
  - `Config::load(Project)`: if `archaeology.php` exists, `require` it, check that it is an array, and merge it over the defaults (roadmap §3; defaults `paths: ['src']`, `exclude: ['vendor']`, `entry_points: ['*Test']`).
  - Validate each key's type. Unknown key → error naming the key (catches typos). Readonly typed properties.
  - Done when: tests cover the defaults, an override, a wrong type, an unknown key, and a file that returns a non-array.

- [ ] **1.5 `Project\Preflight` + `Command\BaseCommand`**
  - Preflight checks: `git --version` runs, `extension_loaded('pdo_sqlite')`. Each check has a clear message.
  - `BaseCommand::initialize()` runs the preflight and locates the project (exposed as `$this->project`). `list` and `help` stay unaffected.
  - Done when: a test with a fake failing check shows the message and a non-zero exit code.

- [ ] **1.6 `InitCommand`**
  - Copies `resources/stubs/archaeology.php` (the roadmap §3 example, with comments) unless the file exists ("already exists, left unchanged").
  - Creates `.archaeology/`.
  - Appends `/.archaeology/` to `.gitignore` if it isn't already there (idempotent; creates `.gitignore` if missing).
  - Done when: a `CommandTester` test runs it twice → same result, no duplicate `.gitignore` line, existing config untouched.

- [ ] **1.7 Real install (manual, human)**
  - Follow roadmap §10 "Testing the package inside a real project"; run `vendor/bin/archaeo init`.
  - Done when: it works in one real project; note the project name here.
