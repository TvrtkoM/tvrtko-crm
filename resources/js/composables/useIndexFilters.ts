import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import type { ComputedRef, Ref, WritableComputedRef } from 'vue';
import { computed, ref, watch } from 'vue';
import type { IndexFilters } from '@/types';
import type { RouteDefinition, RouteQueryOptions } from '@/wayfinder';

/** A Wayfinder action for an entity's `index` route. */
type IndexAction = (options?: RouteQueryOptions) => RouteDefinition<'get'>;

type QueryOverrides = {
    search?: string | null;
    status?: string | null;
    sort?: string;
    dir?: 'asc' | 'desc';
    page?: number | null;
};

export type UseIndexFiltersReturn = {
    /** Bound to the search box; debounced into a visit as the user types. */
    search: Ref<string>;
    /** Bound to the status filter; every change issues a visit. */
    status: WritableComputedRef<string | null>;
    isFiltered: ComputedRef<boolean>;
    toggleSort: (column: string) => void;
    goToPage: (page: number) => void;
    reset: () => void;
};

/**
 * Drives a list view's server-side `?search`/`?status`/`?sort`/`?dir`/`?page`
 * state. Every change is a partial-feeling Inertia visit that keeps local state
 * and scroll (so the search box holds focus) and replaces the history entry.
 */
export function useIndexFilters(
    action: IndexAction,
    getFilters: () => IndexFilters,
): UseIndexFiltersReturn {
    const filters = computed(getFilters);
    const search = ref(filters.value.search ?? '');

    function visit(overrides: QueryOverrides): void {
        const query = {
            search: filters.value.search,
            status: filters.value.status,
            sort: filters.value.sort,
            dir: filters.value.dir,
            page: null,
            ...overrides,
        };

        router.get(
            action({ query }).url,
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // Keep the box in sync when the server state changes elsewhere (reset, back button).
    watch(
        () => filters.value.search,
        (value) => {
            if ((value ?? '') !== search.value.trim()) {
                search.value = value ?? '';
            }
        },
    );

    watchDebounced(
        search,
        (value) => {
            const trimmed = value.trim();
            const next = trimmed === '' ? null : trimmed;

            if (next !== filters.value.search) {
                visit({ search: next });
            }
        },
        { debounce: 300 },
    );

    return {
        search,
        status: computed<string | null>({
            get: () => filters.value.status,
            set: (value) => visit({ status: value }),
        }),
        isFiltered: computed(
            () =>
                filters.value.search !== null || filters.value.status !== null,
        ),
        toggleSort: (column: string) =>
            visit({
                sort: column,
                dir:
                    filters.value.sort === column && filters.value.dir === 'asc'
                        ? 'desc'
                        : 'asc',
            }),
        goToPage: (page: number) => visit({ page }),
        reset: () => {
            search.value = '';
            visit({ search: null, status: null });
        },
    };
}
