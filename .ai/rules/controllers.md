---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## List views: resolve filters through BuildsIndexQueries
`{Entity}Controller@index` builds its query from `?search`/`?status`/`?sort`/`?dir`/`?page` via `BuildsIndexQueries::indexFilters()`, which whitelists the sortable columns (anything else falls back to the default) and normalizes `dir` to asc|desc. Return the paginator plus the resolved `filters` array and the enum `options()` as `statuses` — the Vue toolbar, sortable headers and pagination read their state off `filters`, so a controller that omits it renders a dead toolbar.
Always add a `orderBy('id', 'desc')` tiebreaker after the sort column, otherwise pages can repeat or drop rows when the sorted values tie.

## Kanban boards order by a persisted position column
Every board() query (Company/Deal/Offer/Contact) must order by `position` then `id`, never `latest('updated_at')` — updated_at ordering makes a dragged card jump to the top of its column. `updateStatus` validates an optional `position` (nullable|integer|min:0) and calls `moveCardToPosition()` from `Concerns\ReordersKanbanCards`, which sets the status and reassigns contiguous positions in the affected column(s) inside a transaction. The shared `KanbanBoard.vue` sends `{ status, position }` on both `@add` (cross-column) and `@update` (in-column reorder).
