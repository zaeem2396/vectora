# Continuous integration

GitHub Actions run on **push** and **pull requests** targeting `main`.

| Workflow            | File                      | What it runs                          |
|---------------------|---------------------------|---------------------------------------|
| **Static analysis** | `.github/workflows/static-analysis.yml` | PHPStan (level 6) + Larastan on `src/` |
| **Code style**      | `.github/workflows/format.yml`          | Laravel Pint `--test`                 |
| **Tests**           | `.github/workflows/tests.yml`           | PHPUnit on PHP **8.2**, **8.3**, **8.4** |

## Local commands

```bash
composer analyse      # PHPStan
composer format:test  # style check
composer format       # fix style
composer test         # PHPUnit
```

CI installs dependencies with `composer update` (no committed lockfile). PHPUnit includes **ingestion** (`tests/Unit/Ingestion`, `IngestionBuilderTest`), **advanced search** (`tests/Unit/Search`, `AdvancedSearchBuilderTest`), and **Phase 11 DX** (`SemanticEloquentBuilderTest`, `SemanticDebugCommandTest`, `MakeVectorModelCommandTest`, attribute/cast unit tests).
