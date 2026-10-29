# Phase 6 — Release

- [ ] **6.1 Real-world runs**
  - Run `excavate` + `report` + `fossils` on the O2 projects. Record duration, artifact count and any crash here.
  - Fix crashes. Spot-check 10 high-confidence fossils per project and record the false-positive rate.

- [ ] **6.2 CI** (`.github/workflows/ci.yml`)
  - Matrix: PHP 8.2–8.5 × `--prefer-lowest` / latest. Run `composer check`. Set git `user.*` for the builder tests.

- [ ] **6.3 README.md**
  - Install, the 5 commands, config reference (every key + default), how fossil confidence works, known limits (roadmap §13).

- [ ] **6.4 Publish** (human, needs O1)
  - Final vendor name in `composer.json`, `git tag v0.1.0`, Packagist submit + GitHub hook (roadmap §10).
