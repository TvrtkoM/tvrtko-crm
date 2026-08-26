/** One entry of Laravel's paginator `links` array. */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * A Laravel `LengthAwarePaginator` as it serializes into an Inertia prop.
 */
export type Paginator<TItem> = {
    data: TItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

/**
 * The list view's query-string state, echoed back by `{Entity}Controller@index`
 * so the toolbar, sortable headers and pagination reflect the current request.
 */
export type IndexFilters = {
    search: string | null;
    status: string | null;
    sort: string;
    dir: 'asc' | 'desc';
};
