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

/**
 * Join a contact's first and last name, skipping a missing half.
 */
export function fullName(contact: {
    first_name: string;
    last_name?: string | null;
}): string {
    return [contact.first_name, contact.last_name].filter(Boolean).join(' ');
}

/**
 * Narrow an ISO date/datetime string to the `YYYY-MM-DD` value a native
 * `<input type="date">` accepts. Laravel serializes `date` casts as full
 * ISO timestamps, which the control would otherwise reject.
 */
export function toDateInput(value: string | null | undefined): string {
    return value ? value.slice(0, 10) : '';
}

/**
 * Today as a `YYYY-MM-DD` string in the user's own timezone.
 */
export function todayInput(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}
