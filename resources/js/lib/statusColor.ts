/**
 * Tailwind classes for the palette names returned by the status enums'
 * `color()` helpers. The class strings are written out in full so Tailwind's
 * scanner picks them up — never build these by interpolation.
 */
export type StatusColorClasses = {
    /** Accent bar / dot marking a Kanban column. */
    accent: string;
    /** Tinted pill used for status badges. */
    badge: string;
};

const statusColors: Record<string, StatusColorClasses> = {
    slate: {
        accent: 'bg-slate-400 dark:bg-slate-500',
        badge: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    },
    gray: {
        accent: 'bg-gray-400 dark:bg-gray-500',
        badge: 'bg-gray-500/10 text-gray-700 dark:text-gray-300',
    },
    zinc: {
        accent: 'bg-zinc-400 dark:bg-zinc-500',
        badge: 'bg-zinc-500/10 text-zinc-700 dark:text-zinc-300',
    },
    blue: {
        accent: 'bg-blue-500',
        badge: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    amber: {
        accent: 'bg-amber-500',
        badge: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    },
    green: {
        accent: 'bg-green-500',
        badge: 'bg-green-500/10 text-green-700 dark:text-green-300',
    },
    red: {
        accent: 'bg-red-500',
        badge: 'bg-red-500/10 text-red-700 dark:text-red-300',
    },
};

export function statusColorClasses(color: string): StatusColorClasses {
    return statusColors[color] ?? statusColors.slate;
}
