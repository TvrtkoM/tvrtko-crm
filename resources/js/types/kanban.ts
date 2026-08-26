/**
 * One Kanban column, mirroring a single case of an entity's status enum as
 * serialized by `App\Enums\Concerns\HasEnumOptions::options()`.
 */
export type KanbanColumn = {
    value: string;
    label: string;
    color: string;
};

/**
 * Board records grouped by their status value, as delivered by the
 * `{entity}.board` routes. Absent while a deferred board prop is loading.
 */
export type KanbanCards<TCard> = Record<string, TCard[]>;
