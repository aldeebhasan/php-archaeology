# Phase 4 — Fossils, erosion, reports

Outcome: `archaeo report` and `archaeo fossils` print the roadmap §11 style output.

- [ ] **4.1 `Parser\ReferenceCollector`** (NodeVisitor, same traversal as 3.3)
  - Emits `(from_fqn|null, to_fqn, kind)` per D11. Kinds: `call`, `method`, `static`, `new`, `extends`, `implements`, `use_trait`, `type_hint`, `instanceof`, `dynamic`, `string`.
  - In 3.5's insert step, map `from_fqn` to `from_artifact_id`.
  - Done when: a table-driven test with one PHP snippet per row (a dozen rows, including `self::`, `parent::`, nullable/union types, `?->`, `$o->$m()`, `call_user_func`, string `'App\\Foo'`) checks the expected refs.

- [ ] **4.2 `Analyzer\FossilDetector`**
  - Query: artifacts with no inbound ref (D11 matching, self-refs excluded) AND `last_changed_at < now - fossil_min_age_days` AND not a never-fossil (D12).
  - Attach confidence (D13). Inject a clock (`DateTimeImmutable $now`) for tests.
  - Done when: acme's dead class is a high-confidence fossil. Adding a `$o->$m()` anywhere makes it medium; adding `'DeadClass'` makes it low. An entry-point controller is never reported.

- [ ] **4.3 `Analyzer\LivingFossilDetector`** and **`Analyzer\ErosionDetector`**
  - Living fossil: D14 plus roadmap §8. Erosion: D15.
  - Done when: acme's old core class is a living fossil and the many-times-changed file is the top erosion zone.

- [ ] **4.4 `FossilsCommand`**
  - `--confidence=high|medium|low` (minimum level, default `low`) and `--limit=50`.
  - Table: FQN, kind, last changed, confidence, path:line. Exit with a clear message if no finished dig exists.
  - Done when: CommandTester output snapshot on acme.

- [ ] **4.5 `ReportCommand`**
  - Expedition report (roadmap §11) + top 10 oldest artifacts + top 10 erosion zones + living fossils count. Shows the date of the dig it reports on.
  - Done when: CommandTester output snapshot on acme.
