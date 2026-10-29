# Code Archaeology

> An archaeology tool for PHP projects, written in PHP.
> Install it in a project with Composer, and it digs through that project's git history to show what is ancient, what is alive, and what is buried and forgotten.

```bash
composer require --dev yourname/code-archaeology
vendor/bin/archaeo excavate
```

---

## 1. The idea

Every codebase has layers, like soil at a dig site. Code written in 2019 sits under code written in 2023, and some of it hasn't been touched in years. Developers usually can't see these layers, so they don't know:

- Which parts of the project are old and fragile
- Which functions are dead and safe to delete
- Which files keep getting rewritten (and probably hide bugs)
- Who built what, and when

**Code Archaeology** makes these layers visible. It reads the git history of the project it is installed in, carbon dates every function and class, and presents the project as a dig site you can explore.

### Design decision: one package, one project

The tool is a **Composer dev dependency**, installed per project, the same way PHPStan, PHPUnit or Rector are used:

- It is only installed when a project needs it (`composer require --dev`).
- It always analyzes **the project it is installed in**. No paths, no list of repositories.
- Its data lives inside that project, in a `.archaeology/` folder.
- Settings live in an `archaeology.php` file in the project root, committed with the project so the whole team shares them.

This keeps the tool simple: one project, one database, one config.

---

## 2. Vocabulary

The archaeology metaphor is the identity of the tool. Use these terms consistently in the code, the output and the docs.

| Term | Meaning |
|---|---|
| **Dig site** | The project the package is installed in |
| **Excavation (dig)** | One analysis run over the project |
| **Strata (layers)** | Periods of the project's life: years, quarters or release tags |
| **Artifact** | A class, interface, trait, enum, function or method |
| **Carbon dating** | Working out the age of each line with `git blame` |
| **Fossil** | Old code that nothing references anymore (probably dead) |
| **Living fossil** | Very old code that is still heavily used (fragile core) |
| **Erosion zone** | A file with high churn: rewritten again and again |
| **Civilization** | An era defined by which authors were active |
| **Expedition report** | The summary of a whole dig |

---

## 3. How a developer uses it

```bash
# 1. Install in the project
composer require --dev yourname/code-archaeology

# 2. Create the config file (archaeology.php) and the .archaeology/ folder
vendor/bin/archaeo init

# 3. Run a dig on this project
vendor/bin/archaeo excavate

# 4. Read the results
vendor/bin/archaeo report
vendor/bin/archaeo fossils --confidence=high

# 5. Open the age map in the browser
vendor/bin/archaeo serve
```

Teams can also add a Composer script so everyone runs it the same way:

```json
"scripts": {
    "dig": "archaeo excavate && archaeo report"
}
```

Then `composer dig` runs the whole thing.

### Finding the project root

The tool needs to know where the project starts. It walks up from the current folder until it finds `composer.json`, then checks that a `.git` folder exists there (or runs `git rev-parse --show-toplevel`). If there is no git repository, it stops with a clear message: *"No git history found. Code Archaeology needs a git repository to dig."*

### Files the tool creates in the project

```
my-project/
├── archaeology.php          # config — commit this
├── .archaeology/            # data — add to .gitignore
│   └── dig.sqlite
├── composer.json
├── src/
└── vendor/
```

`archaeo init` adds `.archaeology/` to the project's `.gitignore` automatically.

### Config file: `archaeology.php`

```php
<?php

return [
    // Folders to dig (relative to the project root)
    'paths' => ['src', 'app'],

    // Folders and files to skip
    'exclude' => ['vendor', 'storage', 'bootstrap/cache', 'tests/fixtures'],

    // Code the framework calls for you — never mark these as fossils
    'entry_points' => [
        'App\\Http\\Controllers\\*',
        'App\\Console\\Commands\\*',
        'App\\Listeners\\*',
        '*Test',
    ],

    // Thresholds
    'fossil_min_age_days'       => 365,
    'living_fossil_min_years'   => 3,
    'living_fossil_min_refs'    => 5,
    'erosion_top_percent'       => 10,
];
```

If no config file exists, the tool uses sensible defaults (dig `src/`, skip `vendor/`).

---

## 4. MVP scope

The first version should be small but already useful. **PHP projects only.**

### In the MVP

1. **`init` command** — creates `archaeology.php`, `.archaeology/`, and updates `.gitignore`.
2. **Excavator** — reads `git log` and `git blame` and stores commits and line ages.
3. **Artifact extractor** — parses PHP files with `nikic/php-parser` and records every class, function and method with its line range.
4. **Carbon dating** — gives each artifact a birth date, last-change date, age and author count.
5. **Fossil detector** — finds artifacts that are old and never referenced, respecting the configured entry points.
6. **CLI reports** — prints the oldest artifacts, the fossils and the erosion zones in the terminal.
7. **Age map** — `archaeo serve` opens one page with a treemap of the project, files colored by age.

### Not in the MVP (later)

- Time slider through history
- Artifact life-story pages
- Civilizations (author eras)
- Rename and move tracking
- Incremental digs (only new commits)
- JSON / HTML report export for CI pipelines

---

## 5. Tech stack

| Part | Choice | Why |
|---|---|---|
| Language | PHP 8.2+ | Wide enough to fit most projects that install it |
| CLI | `symfony/console` | Standard, well documented |
| Running git | `symfony/process` | Safe way to run `git log` / `git blame` |
| Parsing PHP | `nikic/php-parser` | The most accurate PHP parser available |
| Storage | SQLite (via PDO) | No server needed; one file inside the project |
| Age map | PHP built-in server (`php -S`) + one HTML page | No framework needed inside someone else's project |
| Charts | D3.js (bundled in the package) | Treemaps and timelines, works offline |
| Tests | PHPUnit | Test against small fixture repositories |

> **Why no Laravel or Slim for the web UI?** The package is installed *inside other people's projects*. Every dependency it brings can clash with theirs. Keep dependencies to the minimum: the age map is a single page served by PHP's built-in server.

---

## 6. How it works

```
Project (git) ──▶ Excavator ──▶ Artifact extractor ──▶ Analyzer ──▶ .archaeology/dig.sqlite ──▶ CLI reports / Age map
                 (log + blame)   (php-parser AST)      (ages, fossils,
                                                         erosion)
```

1. **Locate** — find the project root and load `archaeology.php`.
2. **Excavate** — list commits with `git log --format=...` and store them.
3. **Blame** — run `git blame --line-porcelain` on each `.php` file in the configured paths and store the commit of every line.
4. **Extract** — parse each file into an AST, record every artifact and its `start_line` / `end_line`.
5. **Collect references** — walk the AST to record calls, `new`, `extends`, `implements`, static calls and type hints.
6. **Carbon date** — for each artifact, read the blame data inside its line range: oldest line = born, newest line = last changed.
7. **Analyze** — compute fossils, living fossils and erosion zones.
8. **Report** — print to the CLI or serve the age map.

---

## 7. Database design (SQLite)

Because the tool only ever handles one project, there is no "dig sites" table. One database = one project.

```sql
CREATE TABLE digs (
    id              INTEGER PRIMARY KEY,
    head_commit     TEXT NOT NULL,
    status          TEXT NOT NULL,          -- running | finished | failed
    started_at      TEXT NOT NULL,
    finished_at     TEXT
);

CREATE TABLE commits (
    id              INTEGER PRIMARY KEY,
    hash            TEXT NOT NULL UNIQUE,
    author_name     TEXT NOT NULL,
    author_email    TEXT NOT NULL,
    committed_at    TEXT NOT NULL,
    message         TEXT
);

CREATE TABLE files (
    id              INTEGER PRIMARY KEY,
    path            TEXT NOT NULL UNIQUE,
    line_count      INTEGER NOT NULL,
    change_count    INTEGER NOT NULL DEFAULT 0   -- churn
);

CREATE TABLE line_ages (
    file_id         INTEGER NOT NULL REFERENCES files(id),
    line_number     INTEGER NOT NULL,
    commit_id       INTEGER NOT NULL REFERENCES commits(id),
    PRIMARY KEY (file_id, line_number)
);

CREATE TABLE artifacts (
    id              INTEGER PRIMARY KEY,
    file_id         INTEGER NOT NULL REFERENCES files(id),
    kind            TEXT NOT NULL,     -- class | interface | trait | enum | function | method
    fqn             TEXT NOT NULL,     -- App\Billing\Invoice::total
    start_line      INTEGER NOT NULL,
    end_line        INTEGER NOT NULL,
    born_at         TEXT,
    last_changed_at TEXT,
    author_count    INTEGER
);

CREATE TABLE artifact_references (
    id               INTEGER PRIMARY KEY,
    from_artifact_id INTEGER REFERENCES artifacts(id),
    to_fqn           TEXT NOT NULL,
    kind             TEXT NOT NULL     -- call | new | static | extends | implements | type_hint
);

CREATE INDEX idx_refs_to ON artifact_references(to_fqn);
CREATE INDEX idx_artifacts_fqn ON artifacts(fqn);
```

For the MVP, each `excavate` run clears the tables and rebuilds them. Incremental digs come later.

---

## 8. Classification rules (first draft)

These are starting values; they come from `archaeology.php` and can be tuned per project.

**Fossil** (probably dead code)
- No inbound references in `artifact_references`, **and**
- Last changed more than `fossil_min_age_days` ago, **and**
- Not matched by any pattern in `entry_points`.

**Fossil confidence**
- **High** — the project uses no dynamic calls.
- **Medium** — dynamic calls exist elsewhere (`$obj->$method()`, `call_user_func`).
- **Low** — the artifact's name appears as a string somewhere in the code.

**Living fossil**
- Born more than `living_fossil_min_years` ago, **and**
- At least `living_fossil_min_refs` inbound references, **and**
- Rarely changed.

**Erosion zone**
- File `change_count` in the top `erosion_top_percent` of the project.

---

## 9. Package structure

```
code-archaeology/
├── bin/
│   └── archaeo                     # CLI entry point (no .php extension)
├── src/
│   ├── Command/
│   │   ├── InitCommand.php         # archaeo init
│   │   ├── ExcavateCommand.php     # archaeo excavate
│   │   ├── FossilsCommand.php      # archaeo fossils
│   │   ├── ReportCommand.php       # archaeo report
│   │   └── ServeCommand.php        # archaeo serve
│   ├── Project/
│   │   ├── ProjectLocator.php      # finds the project root and .git
│   │   └── Config.php              # loads archaeology.php + defaults
│   ├── Excavator/
│   │   ├── GitLogReader.php
│   │   └── GitBlameReader.php
│   ├── Parser/
│   │   ├── ArtifactExtractor.php   # NodeVisitor: classes, functions, methods
│   │   └── ReferenceCollector.php  # NodeVisitor: calls, new, extends...
│   ├── Analyzer/
│   │   ├── CarbonDater.php
│   │   ├── FossilDetector.php
│   │   └── ErosionDetector.php
│   ├── Model/
│   │   ├── Artifact.php
│   │   ├── ArtifactKind.php        # enum
│   │   └── Commit.php
│   └── Storage/
│       ├── Database.php
│       └── migrations/
│           └── 001_initial.sql
├── resources/
│   ├── stubs/
│   │   └── archaeology.php         # template copied by `archaeo init`
│   └── web/
│       ├── index.php               # age map page, served by `archaeo serve`
│       └── d3.min.js               # bundled so it works offline
├── tests/
│   ├── fixtures/                   # tiny git repos for testing
│   └── ...
├── composer.json
├── LICENSE
└── README.md
```

---

## 10. Packaging

### `composer.json` of the package

```json
{
    "name": "yourname/code-archaeology",
    "description": "Dig through the git history of your PHP project: find fossils, living fossils and erosion zones.",
    "type": "library",
    "license": "MIT",
    "keywords": ["dead-code", "git", "analysis", "dev", "static-analysis"],
    "require": {
        "php": ">=8.2",
        "ext-pdo_sqlite": "*",
        "symfony/console": "^6.4 || ^7.0",
        "symfony/process": "^6.4 || ^7.0",
        "nikic/php-parser": "^4.18 || ^5.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": { "CodeArchaeology\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "CodeArchaeology\\Tests\\": "tests/" }
    },
    "bin": ["bin/archaeo"]
}
```

Note the **wide version ranges** (`^6.4 || ^7.0`). The package shares a `vendor/` folder with the host project, so if a project uses Symfony 6 and the package demanded Symfony 7 only, Composer would refuse to install it. Wide ranges avoid that. The code must then work with both versions, which the test suite should check.

### The executable: `bin/archaeo`

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

// Composer 2.2+ gives the path of the host project's autoloader.
// The fallback is for running the script inside the package repository itself.
require $GLOBALS['_composer_autoload_path'] ?? __DIR__ . '/../vendor/autoload.php';

use CodeArchaeology\Command;
use Symfony\Component\Console\Application;

$app = new Application('Code Archaeology', '0.1.0');
$app->add(new Command\InitCommand());
$app->add(new Command\ExcavateCommand());
$app->add(new Command\FossilsCommand());
$app->add(new Command\ReportCommand());
$app->add(new Command\ServeCommand());
$app->run();
```

Make it executable and commit that permission:

```bash
chmod +x bin/archaeo
git update-index --chmod=+x bin/archaeo
```

### Startup checks

Before any command runs, check and explain clearly:

- `git` is installed and on the PATH
- The `pdo_sqlite` extension is loaded
- The current folder is inside a project with a git repository

### Testing the package inside a real project (before publishing)

In any PHP project on your machine, point Composer at your local package folder:

```bash
composer config repositories.archaeo path ../code-archaeology
composer require --dev yourname/code-archaeology:@dev
vendor/bin/archaeo excavate
```

Composer creates a symlink, so changes in the package show up in the project immediately.

### Keeping the installed package small

Add a `.gitattributes` file so tests and dev files are not downloaded into people's projects:

```
/tests            export-ignore
/.github          export-ignore
/phpunit.xml.dist export-ignore
/.gitattributes   export-ignore
```

### Publishing

1. Push the package to a public GitHub repository.
2. Tag a version: `git tag v0.1.0 && git push --tags`.
3. Submit the repository URL at packagist.org (sign in with GitHub).
4. Turn on the Packagist GitHub hook so new tags are published automatically.

After that, any project can run:

```bash
composer require --dev yourname/code-archaeology
```

### Later: avoiding dependency conflicts completely

If conflicts with host projects become a problem, ship a second package that contains only a prebuilt PHAR (built with `humbug/box`), the way `phpstan/phpstan` does. The PHAR carries its own copies of Symfony and php-parser, so it never clashes with the project's versions. This is not needed for the MVP.

---

## 11. Example output

```
$ vendor/bin/archaeo report

Expedition report: acme-shop
──────────────────────────────────────────
Artifacts found:        1,284
Oldest artifact:        App\Legacy\Csv::parse      (born 2016-03-11)
Fossils (high conf.):   37
Living fossils:         12
Top erosion zone:       src/Billing/Invoice.php    (214 changes)
```

---

## 12. Roadmap

| Phase | Goal | Rough time |
|---|---|---|
| **1. Package skeleton** | `composer.json`, `bin/archaeo`, `ProjectLocator`, `Config`, `init` command, test install in a real project | Week 1 |
| **2. Excavation** | SQLite schema, `GitLogReader`, `GitBlameReader` | Week 2 |
| **3. Carbon dating** | `ArtifactExtractor`, `CarbonDater`, `excavate` command end to end | Week 3 |
| **4. Fossils** | `ReferenceCollector`, `FossilDetector`, `ErosionDetector`, `report` and `fossils` commands | Weeks 4–5 |
| **5. Age map** | `serve` command and the D3 treemap page | Week 6 |
| **6. Release** | Tests on real open-source projects, README, `.gitattributes`, tag `v0.1.0`, publish on Packagist | Week 7 |
| **Later** | Time slider, artifact pages, civilizations, incremental digs, rename tracking, CI export, PHAR package | — |

---

## 13. Known challenges

- **Dependency conflicts** — the package shares `vendor/` with the host project. Keep dependencies few and version ranges wide; consider a PHAR package later.
- **Speed** — `git blame` on large projects is slow. Start simple; add incremental digs and parallel blame later.
- **Dynamic PHP** — magic methods, `$obj->$method()`, string callbacks and framework conventions hide real callers. Fossil detection will always be "probably dead", so show confidence levels.
- **Framework entry points** — routes, controllers, commands and listeners are called by the framework, not by code. The `entry_points` config handles this; later, ship ready-made presets for Laravel and Symfony.
- **Renames and moves** — a renamed function looks like a new one. Leave this for after the MVP.
- **Shallow clones** — CI systems often clone with `--depth=1`, which has no history. Detect this (`git rev-parse --is-shallow-repository`) and warn the user.

---

## 14. First steps

1. Create the package folder and its `composer.json` from section 10:
   ```bash
   mkdir code-archaeology && cd code-archaeology
   git init
   composer install
   ```
2. Create `bin/archaeo` with a single `init` command that prints the detected project root.
3. Install it into a real PHP project with a Composer path repository (section 10) and run `vendor/bin/archaeo init`.
4. Build `ProjectLocator` and `Config`, then make `init` create `archaeology.php` and `.archaeology/`.
5. Add `GitLogReader` and store commits in `.archaeology/dig.sqlite` — the first working dig.

Once the package installs cleanly in a real project and can read its history, the rest of the tool grows from there.