<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that render a Kanban board and need an explicit per-column order.
     *
     * @var array<int, string>
     */
    private array $tables = ['companies', 'deals', 'offers', 'contacts'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unsignedInteger('position')->default(0)->after('status');
            });

            $this->backfillPositions($table);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('position');
            });
        }
    }

    /**
     * Seed positions per status column, preserving the current
     * newest-updated-first order the board rendered before this change.
     */
    private function backfillPositions(string $table): void
    {
        $rows = DB::table($table)
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get(['id', 'status']);

        $positions = [];

        foreach ($rows as $row) {
            $index = $positions[$row->status] ?? 0;

            DB::table($table)->where('id', $row->id)->update(['position' => $index]);

            $positions[$row->status] = $index + 1;
        }
    }
};
