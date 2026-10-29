# Code Archaeology — agent guide

Composer dev-dependency CLI (`vendor/bin/archaeo`) that analyzes the git history of the PHP project it is installed in.
Namespace `CodeArchaeology\` → `src/`. PHP ≥ 8.2. SQLite storage in the host project's `.archaeology/dig.sqlite`.

## Where things are (read only what you need)

| Need | Read |
|---|---|
| What to do next / task status | `docs/plan/README.md` (status table + task IDs) |
| Details of one task | only that phase file: `docs/plan/phase-N-*.md` |
| Why something is done a certain way | `docs/plan/decisions.md` (D1, D2… — cite IDs, don't re-argue) |
| Product vision, vocabulary, original spec | `docs/roadmap.md` — **don't read it whole**; `grep -n` the section you need. Plan + decisions override it. |

## Commands

```bash
composer install
composer test            # phpunit
composer stan            # phpstan (level 8)
composer check           # test + stan — must pass before a task is marked done
vendor/bin/phpunit --filter SomeTest     # run one test, prefer this while iterating
php bin/archaeo list
php82 vendor/bin/phpunit                 # check minimum PHP version (Herd ships php82)
```

## Rules

- **Minimal dependencies**: runtime deps are only `symfony/console`, `symfony/process`, `nikic/php-parser`, `ext-pdo_sqlite`. Never add a runtime dependency — the package shares `vendor/` with host projects. Dev deps are fine.
- Must work on Symfony 6.4/7/8 and php-parser 4.18/5. Version-specific calls go through the single compat point named in decisions (D3, D4).
- Run git only through `symfony/process` with argument arrays (never shell strings). Dig `HEAD`, not the working tree (D5).
- `declare(strict_types=1);` everywhere, `final readonly` value objects, no static state, constructor injection, no service container.
- Use the vocabulary from roadmap §2 (dig, excavation, artifact, fossil, living fossil, erosion zone) in class names, output and docs.
- Tests: every component has a PHPUnit test. Git-dependent tests build throwaway repos with `tests/Support/GitRepoBuilder` (fixed dates, fixed authors) — never commit nested `.git` dirs, never depend on this repo's own history.
- Keep the user's `archaeology.php` as untrusted input: validate types and say clearly what is wrong.

## Workflow per task

1. Pick the first unchecked task in `docs/plan/README.md` (or the one asked for). Read its phase file section only.
2. Write the test first when the behaviour is specifiable; then the code.
3. `composer check` green → tick the box in the phase file and update the status table in `docs/plan/README.md`.
4. If you made a non-obvious choice, add a one-paragraph `Dn` entry to `decisions.md`.
5. One task = one commit (`<task id>: <summary>`, e.g. `2.3: GitLogReader`).
