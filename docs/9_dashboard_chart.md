# Step 9 — Dashboard pipeline chart

> Spec: @docs/PROJECT.md (§6 dashboard). Progress index: @docs/Log.md
> **Prerequisites:** Step 7 (the `/dashboard` CRM home already exists). **New dependencies:** none.

**Goal.** Add a **"Pipeline by stage"** chart to the dashboard — horizontal bars of total deal
**value (€) per `DealStage`**, sitting **above** the Recent deals / Recent offers grid. It answers
the one question the KPI cards don't: *where in the funnel the pipeline money actually sits.*

## Design decisions (settled)

- **Chart type:** horizontal bar chart (magnitude across a small set of named categories).
- **Measure:** total deal `value` per stage (the bar length); deal **count** shown as a secondary
  label. All five stages appear, including `Won`/`Lost` (seeing won vs lost value is the point).
- **Dependency-free.** Hand-rolled with Tailwind — no chart library. A five-bar magnitude chart
  needs none, and it keeps the demo's dependency list minimal.
- **Colors = the existing status palette.** Reuse `statusColorClasses(color).accent` (the solid
  `bg-*-500` fills already used for Kanban accents) keyed off each stage's `color()`. Per the
  dataviz method this is a **status palette**, not an arbitrary categorical one: every bar is
  directly labeled with its stage name, so identity is never carried by color alone — no legend,
  no palette validation needed. This also makes the chart read consistently with the Deals board
  (green `Won`, red `Lost`, …).
- **Placement:** a full-width shadcn `Card`, between the KPI grid and the `lg:grid-cols-2` recent
  grid in `Dashboard.vue`.

## Backend — `app/Http/Controllers/DashboardController.php`

Add a `pipeline` prop to the `Inertia::render('Dashboard', [...])` payload: one entry per
`DealStage` (in enum order), each carrying `status`, `label`, `color`, `count`, and `value`.

Use a single grouped query, then map over `DealStage::cases()` so **empty stages still appear**
(zero bars). Note the `keyBy` closure — `status` is an enum-cast attribute and a backed enum
can't be an array key, so key by `->status->value`:

```php
$dealsByStage = Deal::query()
    ->selectRaw('status, count(*) as count, coalesce(sum(value), 0) as value')
    ->groupBy('status')
    ->get()
    ->keyBy(fn (Deal $row): string => $row->status->value);

$pipeline = collect(DealStage::cases())->map(fn (DealStage $stage): array => [
    'status' => $stage->value,
    'label' => $stage->label(),
    'color' => $stage->color(),
    'count' => (int) ($dealsByStage[$stage->value]->count ?? 0),
    'value' => (float) ($dealsByStage[$stage->value]->value ?? 0),
])->all();
```

Add `'pipeline' => $pipeline,` to the render array. (The `openDeals()` helper and the rest stay
as-is.)

## Frontend

### New component — `resources/js/components/PipelineChart.vue`

A self-contained bar chart. Props:

```ts
type PipelineStage = {
    status: string;
    label: string;
    color: string;
    count: number;
    value: number;
};
defineProps<{ pipeline: PipelineStage[] }>();
```

Structure (one `Card`, `BarChart3` lucide icon in the title, then a row per stage):

- Compute `maxValue = Math.max(1, ...pipeline.map((s) => s.value))` to scale bar widths (guard
  against divide-by-zero when everything is 0).
- Each row: `[label w-28 truncate] [bar track flex-1] [value + count, right-aligned tabular-nums]`.
- Bar track: `relative h-2.5 flex-1 rounded-full bg-muted`; the fill is an absolutely-positioned
  inner div, `rounded-full`, width `= (value / maxValue) * 100%`, class from
  `statusColorClasses(stage.color).accent`.
- Value label: `formatCurrency(stage.value)` (from `@/lib/format`) with a muted `· {{ count }}`
  after it.
- **Mark specs (dataviz):** thin bars (`h-2.5`), rounded data-ends (`rounded-full`), a per-bar
  `:title` tooltip (e.g. `"{label}: {value} · {count} deals"`), direct category + value labels
  (no legend, no number-on-every-tick axis), recessive `bg-muted` track. Dark mode comes free from
  the `accent` tokens (`bg-*-500` reads in both themes) and `bg-muted`.
- Empty state: if `pipeline` is empty or every `value` is 0, still render the labeled rows with
  empty tracks (the counts communicate the state) — no special "no data" card needed.

Reuse helpers already in the codebase: `statusColorClasses` from `@/lib/statusColor`,
`formatCurrency` from `@/lib/format`, `cn` from `@/lib/utils`, and the shadcn `Card` family.

### Wire into `resources/js/pages/Dashboard.vue`

- Import `PipelineChart` and add `pipeline: PipelineStage[]` (or an inline type) to the page's
  `defineProps`.
- Render `<PipelineChart :pipeline="pipeline" />` **between** the KPI grid `</div>` and the
  `<div class="grid gap-4 lg:grid-cols-2">` recent grid.

## Tests — extend `tests/Feature/DashboardTest.php`

Add a case asserting the `pipeline` prop: seed deals across a couple of stages with known values,
then assert the payload. Example shape:

```php
->has('pipeline', 5) // one entry per DealStage
->where('pipeline.0.status', DealStage::Qualification->value)
->where('pipeline.0.count', /* expected */)
->where('pipeline.0.value', /* expected */)
```

Cover: correct per-stage `count` and summed `value`, that a stage with no deals still appears with
`count = 0` / `value = 0`, and that all five stages are present in enum order.

## Acceptance criteria

- The dashboard shows a "Pipeline by stage" card above Recent deals/offers, with five labeled bars
  colored to match the Deals board, each showing its € value and deal count.
- `php artisan test --compact` green (new + existing dashboard tests).
- `npm run types:check`, `npm run lint`, `npm run build` all clean.

## Finish

- `vendor/bin/pint --dirty --format agent`.
- Mark step 9 done in @docs/Log.md and note it (dashboard chart, dependency-free).
