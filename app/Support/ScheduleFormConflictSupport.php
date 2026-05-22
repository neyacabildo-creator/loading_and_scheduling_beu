<?php

namespace App\Support;

/**
 * Validates schedule grid cells for duplicate subject + teacher in one section.
 */
class ScheduleFormConflictSupport
{
    /**
     * @param  array<int, array{subject: string, faculty_id: string|null}>  $cellRows
     * @return string|null Error message when duplicate found
     */
    public static function duplicateSubjectTeacherInCell(array $cellRows): ?string
    {
        $seen = [];

        foreach ($cellRows as $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $facultyId = trim((string) ($row['faculty_id'] ?? ''));

            if ($subject === '' || $facultyId === '') {
                continue;
            }

            $key = strtolower($subject) . '|' . $facultyId;
            if (isset($seen[$key])) {
                return "Duplicate assignment: \"{$subject}\" is already assigned to the same teacher in this section slot.";
            }

            $seen[$key] = true;
        }

        return null;
    }

    /**
     * @param  array<int, array{subject: string, faculty_id: string|null}>  $cellRows
     */
    public static function collectCellRowsFromSlotData(array $data): array
    {
        $rows = [];
        $primarySubject = trim((string) ($data['subject'] ?? ''));
        $primaryFaculty = ! empty($data['faculty_id']) ? (string) $data['faculty_id'] : null;

        if ($primarySubject !== '') {
            $rows[] = ['subject' => $primarySubject, 'faculty_id' => $primaryFaculty];
        }

        foreach ($data['extra'] ?? [] as $extra) {
            $subject = trim((string) ($extra['subject'] ?? ''));
            $facultyId = ! empty($extra['faculty_id']) ? (string) $extra['faculty_id'] : null;
            if ($subject !== '') {
                $rows[] = ['subject' => $subject, 'faculty_id' => $facultyId];
            }
        }

        return $rows;
    }
}
