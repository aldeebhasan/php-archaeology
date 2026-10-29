# Phase 3 — Carbon dating

Outcome: every artifact has `born_at`, `last_changed_at` and `author_count`.

- [ ] **3.1 `Model\ArtifactKind` (enum) + `Model\Artifact`**
  - Kinds: class, interface, trait, enum, function, method. Artifact (readonly) fields: `kind`, `fqn`, `path`, `startLine`, `endLine`.

- [ ] **3.2 `Parser\ParserFactory`**
  - D4 compat point. Done when: a test parses a file using PHP 8.2 syntax (readonly class, enum, attributes).

- [ ] **3.3 `Parser\ArtifactExtractor`** (NodeVisitor)
  - Run after NameResolver. Collect the D10 kinds with line ranges from `getStartLine()`/`getEndLine()`. Keep a class stack for method FQNs. Skip anonymous classes and closures.
  - Done when: a fixture source with namespace + class + interface + trait + enum + global function + two methods + an anonymous class gives the exact expected list.

- [ ] **3.4 `Analyzer\CarbonDater`**
  - One SQL `UPDATE artifacts SET …` using correlated subqueries over `line_ages JOIN commits` within `start_line..end_line`: `MIN(committed_at)`, `MAX(committed_at)`, `COUNT(DISTINCT author_email)`.
  - Done when: the acme test asserts exact dates and author counts for three artifacts, including a method changed after its class was born.

- [ ] **3.5 `excavate` end to end**
  - After blame, parse each file's HEAD source (D5), insert artifacts (D17 on parse errors), then run CarbonDater.
  - End summary: files, artifacts, commits, skipped files and duration.
  - Done when: the command test on acme passes, and a manual run on the 1.7 project finishes without errors.
