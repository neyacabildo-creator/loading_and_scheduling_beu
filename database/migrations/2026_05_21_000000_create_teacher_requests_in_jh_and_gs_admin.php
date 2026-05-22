<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher schedule/adjustment requests live in each division's admin database
 * (mysql_jh / mysql_gs), not in the teacher portal DBs.
 */
return new class extends Migration
{
    /** @var array<string, string> admin connection => teacher connection (legacy source) */
    private array $pairs = [
        'mysql_jh' => 'mysql_jh_teacher',
        'mysql_gs' => 'mysql_gs_teacher',
    ];

    public function up(): void
    {
        foreach ($this->pairs as $adminConn => $teacherConn) {
            if (! Schema::connection($adminConn)->hasTable('teacher_requests')) {
                Schema::connection($adminConn)->create('teacher_requests', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('faculty_id');
                    $table->string('teacher_name', 150)->nullable();
                    $table->unsignedBigInteger('schedule_id')->nullable();
                    $table->enum('request_type', [
                        'time_change',
                        'room_change',
                        'teacher_reassignment',
                        'section_change',
                        'other',
                    ])->default('other');
                    $table->text('reason');
                    $table->text('proposed_changes')->nullable();
                    $table->string('subject', 120)->nullable();
                    $table->string('grade_level', 80)->nullable();
                    $table->string('section_name', 80)->nullable();
                    $table->string('day_of_week', 20)->nullable();
                    $table->string('preferred_start_time', 20)->nullable();
                    $table->string('preferred_end_time', 20)->nullable();
                    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                    $table->text('admin_notes')->nullable();
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                    $table->timestamp('reviewed_at')->nullable();
                    $table->timestamps();

                    $table->index(['faculty_id', 'status']);
                    $table->index('status');
                    $table->index('request_type');
                });
            }

            $this->backfillFromTeacherDb($adminConn, $teacherConn);
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->pairs) as $adminConn) {
            Schema::connection($adminConn)->dropIfExists('teacher_requests');
        }
    }

    private function backfillFromTeacherDb(string $adminConn, string $teacherConn): void
    {
        if (! Schema::connection($teacherConn)->hasTable('schedule_adjustment_requests')) {
            return;
        }

        if (DB::connection($adminConn)->table('teacher_requests')->exists()) {
            return;
        }

        $rows = DB::connection($teacherConn)->table('schedule_adjustment_requests')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return;
        }

        $userIds = $rows->pluck('requested_by')->merge($rows->pluck('reviewed_by'))->filter()->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($rows as $r) {
            $parsed = $this->parseProposed($r->proposed_changes ?? null);
            $user = $users->get($r->requested_by);
            $teacherName = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? null)
                : null;

            DB::connection($adminConn)->table('teacher_requests')->insert([
                'faculty_id'           => $r->requested_by,
                'teacher_name'         => $teacherName,
                'schedule_id'          => $r->schedule_id,
                'request_type'         => $r->request_type ?? 'other',
                'reason'               => $r->reason,
                'proposed_changes'     => $r->proposed_changes,
                'subject'              => $parsed['subject'] ?? null,
                'grade_level'          => $parsed['grade_level'] ?? null,
                'section_name'         => $parsed['section_name'] ?? null,
                'day_of_week'          => $parsed['day_of_week'] ?? null,
                'preferred_start_time' => $parsed['preferred_start_time'] ?? null,
                'preferred_end_time'   => $parsed['preferred_end_time'] ?? null,
                'status'               => $r->status ?? 'pending',
                'admin_notes'          => $r->admin_notes ?? null,
                'reviewed_by'          => $r->reviewed_by ?? null,
                'reviewed_at'          => $r->reviewed_at ?? null,
                'created_at'           => $r->created_at ?? now(),
                'updated_at'           => $r->updated_at ?? now(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseProposed(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['detail' => $raw];
    }
};
