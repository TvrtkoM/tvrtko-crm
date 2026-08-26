---
paths:
  - 'app/**'
---

# App

## Divide by a float literal in raw SQL — SQLite truncates decimals
SQLite gives `decimal(x,y)` columns NUMERIC affinity, so a whole value like `25.00` is stored as the integer `25` and `tax_rate / 100` evaluates to `0` (integer division) — silently, with no error. Write `/ 100.0` in any raw expression that divides a money/percent column. `OfferController::totalSubquery()` sorts offers by their computed total this way; the same trap applies to any future raw math on `decimal` columns.
