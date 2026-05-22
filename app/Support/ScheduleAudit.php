<?php

namespace App\Support;

use App\Models\ClassSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleAudit
{
    public static function setAuditUser(string $connection, ?string $userName): void
    {
        $name = trim((string) ($userName ?: 'system')) ?: 'system';
        $db = DB::connection($connection);
        $db->unprepared('SET @audit_user = ' . $db->getPdo()->quote($name));
    }

    public static function decodeChangeLog($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function appendChangeLog($existing, string $action, ?string $actorName, array $context = []): string
    {
        $log = self::decodeChangeLog($existing);

        $entry = [
            'action' => $action,
            'by' => $actorName ?: 'system',
            'at' => now()->toDateTimeString(),
        ];

        if (!empty($context['reason'])) {
            $entry['reason'] = $context['reason'];
        }

        if (!empty($context['details'])) {
            $entry['details'] = $context['details'];
        }

        if (!empty($context['changes']) && is_array($context['changes'])) {
            $entry['changes'] = $context['changes'];
        }

        $log[] = $entry;

        return json_encode($log, JSON_UNESCAPED_SLASHES);
    }

    public static function collectChanges(ClassSchedule $schedule, array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            if (in_array($field, ['change_log', 'version', 'last_modified_by_admin'], true)) {
                continue;
            }

            $from = $schedule->getRawOriginal($field);

            if (self::normalizeValue($from) === self::normalizeValue($value)) {
                continue;
            }

            $changes[$field] = [
                'from' => $from,
                'to' => $value,
            ];
        }

        return $changes;
    }

    public static function resolveUserDisplay($value, ?Collection $users = null): string
    {
        if ($value === null || $value === '') {
            return 'System';
        }

        if ($users && self::isNumericIdentifier($value) && isset($users[(int) $value])) {
            return $users[(int) $value]->name;
        }

        return (string) $value;
    }

    public static function approverName($value, ?Collection $users = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($users && self::isNumericIdentifier($value) && isset($users[(int) $value])) {
            return $users[(int) $value]->name;
        }

        return (string) $value;
    }

    public static function summarizeAuditLog(array $log, ?Collection $users = null): string
    {
        $newData = self::decodeJsonColumn($log['new_data'] ?? null);
        $oldData = self::decodeJsonColumn($log['old_data'] ?? null);
        $action = strtoupper((string) ($log['action'] ?? 'UPDATE'));

        if ($action === 'INSERT') {
            if (!empty($newData['change_log'])) {
                return self::formatLatestChangeLogEntry($newData['change_log'], $users);
            }

            return self::describeRecordSnapshot($newData, $users);
        }

        if ($action === 'DELETE') {
            if (!empty($oldData['change_log'])) {
                return self::formatLatestChangeLogEntry($oldData['change_log'], $users);
            }

            return 'Deleted record snapshot: ' . self::describeRecordSnapshot($oldData, $users);
        }

        if (!empty($newData['change_log'])) {
            return self::formatLatestChangeLogEntry($newData['change_log'], $users);
        }

        $changes = [];
        foreach ($newData as $field => $value) {
            $oldValue = $oldData[$field] ?? null;
            if (self::normalizeValue($oldValue) === self::normalizeValue($value)) {
                continue;
            }

            $changes[$field] = [
                'from' => $oldValue,
                'to' => $value,
            ];
        }

        if (empty($changes)) {
            return 'Record updated';
        }

        return self::formatFieldChanges($changes, $users);
    }

    public static function formatLatestChangeLogEntry($value, ?Collection $users = null): string
    {
        $entries = self::decodeChangeLog($value);

        if (empty($entries)) {
            return 'No recorded changes';
        }

        $entry = end($entries);

        return self::formatChangeEntry(is_array($entry) ? $entry : [], $users);
    }

    public static function formatChangeLog($value, ?Collection $users = null): string
    {
        $entries = self::decodeChangeLog($value);

        if (empty($entries)) {
            return 'No recorded changes';
        }

        return collect($entries)
            ->filter(fn ($entry) => is_array($entry))
            ->map(fn (array $entry) => self::formatChangeEntry($entry, $users))
            ->implode("\n");
    }

    public static function formatChangeEntry(array $entry, ?Collection $users = null): string
    {
        $parts = [Str::headline((string) ($entry['action'] ?? 'change'))];

        $parts[] = 'By: ' . self::resolveUserDisplay($entry['by'] ?? null, $users);

        if (!empty($entry['details'])) {
            $parts[] = 'Details: ' . $entry['details'];
        }

        if (!empty($entry['reason'])) {
            $parts[] = 'Reason: ' . $entry['reason'];
        }

        if (!empty($entry['changes']) && is_array($entry['changes'])) {
            $parts[] = 'Fields: ' . self::formatFieldChanges($entry['changes'], $users);
        }

        if (!empty($entry['at'])) {
            $parts[] = 'At: ' . $entry['at'];
        }

        return implode(' | ', $parts);
    }

    public static function formatFieldChanges(array $changes, ?Collection $users = null): string
    {
        return collect($changes)
            ->map(function ($change, $field) use ($users) {
                $change = is_array($change) ? $change : [];

                return self::prettifyFieldName((string) $field) . ': '
                    . self::formatFieldValue($change['from'] ?? null, (string) $field, $users)
                    . ' -> '
                    . self::formatFieldValue($change['to'] ?? null, (string) $field, $users);
            })
            ->implode('; ');
    }

    private static function describeRecordSnapshot(array $snapshot, ?Collection $users = null): string
    {
        $fields = [];

        foreach (['subject', 'grade_level', 'section_name', 'day_of_week', 'schedule_date', 'approved_by'] as $field) {
            if (!array_key_exists($field, $snapshot)) {
                continue;
            }

            $fields[] = self::prettifyFieldName($field) . ': ' . self::formatFieldValue($snapshot[$field], $field, $users);
        }

        return empty($fields) ? 'Record changed' : implode('; ', $fields);
    }

    private static function decodeJsonColumn($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function formatFieldValue($value, string $field, ?Collection $users = null): string
    {
        if (in_array($field, ['approved_by', 'reviewed_by', 'submitted_by', 'faculty_id'], true)) {
            return self::approverName($value, $users) ?? 'None';
        }

        if ($field === 'change_log') {
            return 'Updated change log';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    private static function prettifyFieldName(string $field): string
    {
        return Str::headline(str_replace('_', ' ', $field));
    }

    private static function normalizeValue($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return trim((string) $value);
    }

    private static function isNumericIdentifier($value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }
}