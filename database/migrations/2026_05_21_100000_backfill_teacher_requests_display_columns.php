<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_requests')) {
                continue;
            }

            $rows = DB::connection($conn)->table('teacher_requests')->get();

            foreach ($rows as $row) {
                $parsed = $this->parseProposed($row->proposed_changes ?? null);
                $updates = [];

                foreach (['subject', 'grade_level', 'section_name', 'day_of_week', 'preferred_start_time', 'preferred_end_time'] as $col) {
                    $current = trim((string) ($row->{$col} ?? ''));
                    $fromJson = trim((string) ($parsed[$col] ?? ''));
                    if ($current === '' && $fromJson !== '') {
                        $updates[$col] = $fromJson;
                    }
                }

                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::connection($conn)->table('teacher_requests')->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
        // display-only backfill — no rollback
    }

    /**
     * @return array<string, mixed>
     */
    private function parseProposed(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
};
