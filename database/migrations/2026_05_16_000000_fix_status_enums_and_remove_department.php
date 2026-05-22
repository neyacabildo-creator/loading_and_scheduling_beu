<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update ClassSchedule status enum to support 'deleted' and 'inactive' states
     * Update FacultyLoad status enum to support 'available' and 'not_available'
     * Remove department column from faculty_loads table
     */
    public function up(): void
    {
        $connections = ['mysql_jh', 'mysql_gs'];
        
        foreach ($connections as $conn) {
            // Update class_schedules status enum
            if (Schema::connection($conn)->hasTable('class_schedules')) {
                DB::connection($conn)->statement(
                    "ALTER TABLE `class_schedules` MODIFY `status` ENUM('pending', 'active', 'completed', 'deleted', 'inactive') DEFAULT 'active'"
                );
            }

            // Update faculty_loads status enum and remove department
            if (Schema::connection($conn)->hasTable('faculty_loads')) {
                // Remove old triggers before any updates, because they reference the department column
                DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `faculty_loads_after_insert`");
                DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `faculty_loads_after_update`");
                DB::connection($conn)->statement("DROP TRIGGER IF EXISTS `faculty_loads_after_delete`");

                // Allow legacy faculty load statuses while introducing the new enum options
                DB::connection($conn)->statement(
                    "ALTER TABLE `faculty_loads` MODIFY `status` ENUM('active', 'part-time', 'overloaded', 'available', 'not_available') DEFAULT 'available'"
                );

                // Normalize existing faculty load statuses before the final enum reduction
                DB::connection($conn)->statement(
                    "UPDATE `faculty_loads` SET `status` = 'available' WHERE `status` NOT IN ('available', 'not_available')"
                );

                // Drop the department column after the status values are normalized
                if (Schema::connection($conn)->hasColumn('faculty_loads', 'department')) {
                    DB::connection($conn)->statement(
                        "ALTER TABLE `faculty_loads` DROP COLUMN `department`"
                    );
                }

                DB::connection($conn)->statement(
                    "CREATE TRIGGER `faculty_loads_after_insert` AFTER INSERT ON `faculty_loads` FOR EACH ROW INSERT INTO `audit_logs` (`table_name`, `record_id`, `action`, `new_data`, `changed_by`, `changed_at`) VALUES ('faculty_loads', NEW.id, 'INSERT', JSON_OBJECT('id', NEW.id, 'faculty_id', NEW.faculty_id, 'subject', NEW.subject, 'classes_assigned', NEW.classes_assigned, 'load_hours', NEW.load_hours, 'status', NEW.status), @audit_user, NOW())"
                );

                DB::connection($conn)->statement(
                    "CREATE TRIGGER `faculty_loads_after_update` AFTER UPDATE ON `faculty_loads` FOR EACH ROW INSERT INTO `audit_logs` (`table_name`, `record_id`, `action`, `old_data`, `new_data`, `changed_by`, `changed_at`) VALUES ('faculty_loads', NEW.id, 'UPDATE', JSON_OBJECT('id', OLD.id, 'faculty_id', OLD.faculty_id, 'subject', OLD.subject, 'classes_assigned', OLD.classes_assigned, 'load_hours', OLD.load_hours, 'status', OLD.status), JSON_OBJECT('id', NEW.id, 'faculty_id', NEW.faculty_id, 'subject', NEW.subject, 'classes_assigned', NEW.classes_assigned, 'load_hours', NEW.load_hours, 'status', NEW.status), @audit_user, NOW())"
                );

                DB::connection($conn)->statement(
                    "CREATE TRIGGER `faculty_loads_after_delete` AFTER DELETE ON `faculty_loads` FOR EACH ROW INSERT INTO `audit_logs` (`table_name`, `record_id`, `action`, `old_data`, `changed_by`, `changed_at`) VALUES ('faculty_loads', OLD.id, 'DELETE', JSON_OBJECT('id', OLD.id, 'faculty_id', OLD.faculty_id, 'subject', OLD.subject, 'classes_assigned', OLD.classes_assigned, 'load_hours', OLD.load_hours, 'status', OLD.status), @audit_user, NOW())"
                );

                // Finalize the faculty load status enum
                DB::connection($conn)->statement(
                    "ALTER TABLE `faculty_loads` MODIFY `status` ENUM('available', 'not_available') DEFAULT 'available'"
                );
            }
        }
    }

    public function down(): void
    {
        $connections = ['mysql_jh', 'mysql_gs'];
        
        foreach ($connections as $conn) {
            // Revert class_schedules status enum
            if (Schema::connection($conn)->hasTable('class_schedules')) {
                DB::connection($conn)->statement(
                    "ALTER TABLE `class_schedules` MODIFY `status` ENUM('pending', 'active', 'completed') DEFAULT 'active'"
                );
            }

            // Revert faculty_loads status enum and add back department
            if (Schema::connection($conn)->hasTable('faculty_loads')) {
                DB::connection($conn)->statement(
                    "ALTER TABLE `faculty_loads` MODIFY `status` ENUM('active', 'part-time', 'overloaded') DEFAULT 'active'"
                );

                DB::connection($conn)->statement(
                    "ALTER TABLE `faculty_loads` ADD COLUMN `department` VARCHAR(255) NULLABLE AFTER `subject`"
                );
            }
        }
    }
};
