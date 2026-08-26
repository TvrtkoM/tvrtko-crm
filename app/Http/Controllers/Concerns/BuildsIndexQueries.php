<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait BuildsIndexQueries
{
    /**
     * Resolve the list view's query-string state.
     *
     * Only whitelisted sort columns are honored — anything else falls back to
     * `$defaultSort` — so the value can be handed straight to the query builder.
     *
     * @param  class-string<\BackedEnum>  $statusEnum
     * @param  array<int, string>  $sortable
     * @param  'asc'|'desc'  $defaultDir
     * @return array{search: string|null, status: string|null, sort: string, dir: 'asc'|'desc'}
     */
    protected function indexFilters(
        Request $request,
        string $statusEnum,
        array $sortable,
        string $defaultSort,
        string $defaultDir = 'desc',
    ): array {
        $search = trim((string) $request->string('search'));
        $sort = (string) $request->string('sort');
        $status = $request->enum('status', $statusEnum)?->value;

        return [
            'search' => $search === '' ? null : $search,
            'status' => $status === null ? null : (string) $status,
            'sort' => in_array($sort, $sortable, true) ? $sort : $defaultSort,
            'dir' => match (strtolower((string) $request->string('dir'))) {
                'asc' => 'asc',
                'desc' => 'desc',
                default => $defaultDir,
            },
        ];
    }
}
