<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait ReordersKanbanCards
{
    /**
     * Move a Kanban card into a status column at a target index, keeping the
     * `position` of every affected card contiguous so the board renders cards
     * in the exact order they were dropped.
     */
    protected function moveCardToPosition(
        Model $card,
        string $newStatus,
        int $targetIndex,
        string $statusColumn = 'status',
    ): void {
        DB::transaction(function () use ($card, $newStatus, $targetIndex, $statusColumn): void {
            $status = $card->getAttribute($statusColumn);
            $previousStatus = $status instanceof \BackedEnum ? (string) $status->value : (string) $status;

            $card->forceFill([$statusColumn => $newStatus])->save();

            $this->reindexColumn($card, $statusColumn, $newStatus, $card->getKey(), $targetIndex);

            if ($previousStatus !== $newStatus) {
                $this->reindexColumn($card, $statusColumn, $previousStatus);
            }
        });
    }

    /**
     * Reassign contiguous `position` values to every card in a status column,
     * optionally splicing a moved card in at a target index first.
     */
    private function reindexColumn(
        Model $model,
        string $statusColumn,
        string $status,
        int|string|null $insertKey = null,
        int $insertIndex = 0,
    ): void {
        $keyName = $model->getKeyName();

        $keys = $model->newQuery()
            ->where($statusColumn, $status)
            ->when($insertKey !== null, fn ($query) => $query->whereKeyNot($insertKey))
            ->orderBy('position')
            ->orderBy($keyName)
            ->pluck($keyName)
            ->all();

        if ($insertKey !== null) {
            $insertIndex = max(0, min($insertIndex, count($keys)));
            array_splice($keys, $insertIndex, 0, [$insertKey]);
        }

        foreach ($keys as $index => $key) {
            $model->newQuery()->whereKey($key)->update(['position' => $index]);
        }
    }
}
