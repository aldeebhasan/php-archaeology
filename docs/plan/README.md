# MVP execution plan

Goal: tag `v0.1.0`, which installs with `composer require --dev` into a real PHP project and gives you `init`, `excavate`, `report`, `fossils` and `serve` (roadmap §4).

Each task has an ID, the files it touches and a **Done when** check. A task is done only when `composer check` is green.

## Status

| Phase | File | Status |
|---|---|---|
| 1. Package skeleton | [phase-1-skeleton.md](phase-1-skeleton.md) | in progress (1.1 done) |
| 2. Excavation | [phase-2-excavation.md](phase-2-excavation.md) | not started |
| 3. Carbon dating | [phase-3-carbon-dating.md](phase-3-carbon-dating.md) | not started |
| 4. Fossils & reports | [phase-4-fossils.md](phase-4-fossils.md) | not started |
| 5. Age map | [phase-5-age-map.md](phase-5-age-map.md) | not started |
| 6. Release | [phase-6-release.md](phase-6-release.md) | not started |

Dependency order is strictly 1 → 2 → 3 → 4 → 5 → 6. Within a phase, do tasks in order.

## Data flow (one dig)

```
ProjectLocator → Config → Preflight
  → Database::reset()
  → GitLogReader     → commits, file churn          (D6, D7)
  → FileFinder       → .php paths at HEAD           (D5, D9)
  → GitBlameReader   → line_ages                    (D5)
  → ArtifactExtractor + ReferenceCollector → artifacts, artifact_references (D10, D11)
  → CarbonDater      → born_at / last_changed_at / author_count (SQL)
  → FossilDetector / ErosionDetector run at report time (queries, not stored)
```

## Testing strategy

- **Unit**: parsers of git output (feed captured strings), Config validation, path filter, AST visitors (feed PHP source strings).
- **Integration**: `tests/Support/GitRepoBuilder` creates a temp repo with scripted commits, fixed `GIT_AUTHOR_DATE` and fixed authors. Then you assert exact dates, churn and fossils. One shared scenario repo ("acme") is reused across phases 2–4.
- **Command**: Symfony `CommandTester` against a builder repo.
- **Compat**: CI matrix (phase 6) with `--prefer-lowest` (Symfony 6.4 + php-parser 4.18) and latest.

## Open questions (block only the noted task)

- ~~O1~~ Resolved: `aldeebhasan/php-archaeology` (D1).
- **O2** Which 2–3 real projects to benchmark on (6.1). Suggestion: `monolog/monolog`, `guzzlehttp/guzzle`, and one Laravel app of your own.
