# Decisions

Resolved ambiguities in `docs/roadmap.md`. Cite by ID. Add new entries at the bottom; never renumber.

**D1 — Package name.** `aldeebhasan/php-archaeology` (GitHub: aldeebhasan/php-archaeology). Namespace `CodeArchaeology\`, binary `archaeo`.

**D2 — Version ranges.** `symfony/console` and `symfony/process`: `^6.4 || ^7.0 || ^8.0`. `nikic/php-parser`: `^4.18 || ^5.0`. CI runs `--prefer-lowest` and latest to cover both ends.

**D3 — Command registration compat.** Symfony 7.4 deprecates `Application::add()` in favour of `addCommand()`; it is removed in 8.0. Verify this in task 1.2. `bin/archaeo` registers commands via `method_exists($app, 'addCommand') ? $app->addCommand($c) : $app->add($c)`. Commands use the `#[AsCommand]` attribute plus `configure()`; no other version-specific Console API.

**D4 — Parser compat.** One class `Parser\ParserFactory::create()`: v5 → `(new \PhpParser\ParserFactory())->createForNewestSupportedVersion()`, v4 → `->create(ParserFactory::PREFER_PHP7)`. Use only node classes that exist in both (`Stmt\Class_`, `Stmt\Interface_`, `Stmt\Trait_`, `Stmt\Enum_`, `Stmt\Function_`, `Stmt\ClassMethod`, `Expr\*Call`, `Expr\New_`, `Scalar\String_`). Always run `NodeVisitor\NameResolver` first.

**D5 — A dig analyzes HEAD.** File list from `git ls-tree -r --name-only HEAD`, content from `git show HEAD:<path>`, blame from `git blame --line-porcelain HEAD -- <path>`. This means no "uncommitted" blame lines, line numbers always match the parsed source, untracked and ignored files are skipped for free, and results are reproducible. If the working tree is dirty, print a note.

**D6 — Dates.** Use the **author** date (`%aI`, `author-time` in blame), not the committer date, because rebases rewrite committer dates. Store as ISO-8601 UTC strings (`Y-m-d\TH:i:s\Z`) so SQLite string comparison = time comparison.

**D7 — Churn.** `files.change_count` = number of commits whose `git log --name-only` lists the path. Merge commits list no files by default, so they are not counted twice. Count every path in the log once, keep in memory, and write only for files that are in the dig.

**D8 — Commit message.** Store the subject only (`%s`).

**D9 — Path filtering.** `paths` and `exclude` are relative to the project root. An entry with no `*` matches by path prefix on segment boundaries (`vendor` matches `vendor/x.php`, not `vendors/x.php`). An entry with `*` uses `fnmatch(..., FNM_NOESCAPE)`. Only `*.php` files.

**D10 — FQN format.** `Ns\Class`, `Ns\Class::method`, `Ns\function`. No leading backslash. PHP class and function names are case-insensitive, so declare the `fqn` and `to_fqn` columns `COLLATE NOCASE`. Skip anonymous classes and closures. Skip enum cases and class constants for now.

**D11 — Reference resolution (MVP).** Use static resolution only, with no type inference:
- `new X`, `X::m()`, `X::CONST`, `extends`, `implements`, trait `use`, `instanceof`, `catch`, param/return/property types → `to_fqn = X` (plus `X::m` for static calls). Resolve `self`/`static` to the enclosing class; resolve `parent` to the parent's name.
- `$anything->m()` / `?->m()` → `to_fqn = '::m'` (name-only). A method `C::m` counts as referenced when there is any ref to `C::m` **or** `::m`. This is deliberately conservative: it gives fewer false fossils.
- `f()` → resolved function name (NameResolver's `namespacedName` fallback: also record the global name).
- Dynamic forms (`$o->$m()`, `$f()`, `new $c`, `call_user_func*`, `X::$m()`) → `kind = 'dynamic'`, with `to_fqn = '*'`.
- Scalar strings that look like an identifier or FQN (`/^\\?[A-Za-z_][\w\\]*(::\w+)?$/`) → `kind = 'string'`.
- A reference from an artifact to itself (recursion) does not count.

**D12 — Never-fossils.** Magic methods (`__*`), artifacts matched by `entry_points`, and methods whose class matches `entry_points`. Pattern match uses `fnmatch($pattern, $fqn, FNM_NOESCAPE)`. Without `FNM_NOESCAPE`, the `\` in namespaces would act as an escape character.

**D13 — Fossil confidence.** Low, if a `string` ref matches the short name or the FQN. Otherwise medium, if the project has any `dynamic` ref. Otherwise high.

**D14 — "Rarely changed" (living fossil).** `last_changed_at` is older than `fossil_min_age_days`. No new config key.

**D15 — Erosion zone.** The top `ceil(n * erosion_top_percent / 100)` files by `change_count`, among files with `change_count > 0`.

**D16 — Rebuild per dig.** `excavate` deletes all rows except `digs` and rebuilds everything inside one transaction. Migrations are tracked with `PRAGMA user_version`.

**D17 — Errors don't abort a dig.** A parse error or a failed blame on one file prints a warning, skips that file and is counted in the summary. The `digs.status = failed` status is only for fatal errors (no git, DB error).

**D18 — Age map serving.** `serve` runs `PHP_BINARY -S 127.0.0.1:<port> resources/web/index.php` (the file acts as a router) and passes the DB path in env `ARCHAEO_DB`. Routes: `/` HTML, `/data.json` tree, `/d3.min.js`. The DB is opened read-only. Bind to localhost by default; `--host` overrides. File age = line-weighted mean of line ages.

**D19 — Static analysis.** Use PHPStan level 8 as a dev dependency. It costs host projects nothing, because `require-dev` is not installed for dependents, and it catches mistakes before tests run.
