const currencyFormatter = new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
});

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

/**
 * Format a money amount as EUR (e.g. `1.234,56 €`). Decimal columns arrive
 * from Laravel as strings, so both strings and numbers are accepted.
 */
export function formatCurrency(
    value: string | number | null | undefined,
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = typeof value === 'string' ? Number.parseFloat(value) : value;

    return Number.isNaN(amount) ? '—' : currencyFormatter.format(amount);
}

/**
 * Format an ISO date/datetime string as `26 Aug 2026`.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
}
