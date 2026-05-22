<?php

namespace App\Models\Traits;

/**
 * Trait UseSchoolConnection
 *
 * Allows a model to dynamically resolve its database connection
 * based on the current school context set by SetSchoolDatabase middleware.
 *
 * Connection key is stored in config('database.school_connection'):
 *   - 'mysql_jh'  → Junior High database (loading_scheduling_jh)
 *   - 'mysql_gs'  → Grade School database (loading_scheduling_gs)
 *
 * Usage: add `use UseSchoolConnection;` to any operational model.
 */
trait UseSchoolConnection
{
    /**
     * Resolve the connection name for this model.
     * Falls back to the model's $connection property, then the default connection.
     */
    public function getConnectionName(): string
    {
        $schoolConn = config('database.school_connection');

        if ($schoolConn && array_key_exists($schoolConn, config('database.connections', []))) {
            return $schoolConn;
        }

        return $this->connection ?? config('database.default');
    }
}
