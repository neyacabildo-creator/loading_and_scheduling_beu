# Database setup

Schema changes are managed **only** through Laravel migrations in `database/migrations/`.

## Connections

| Connection | Purpose |
|------------|---------|
| `mysql` | Users, roles, auth (main app DB) |
| `mysql_jh` | Junior High admin operational data |
| `mysql_gs` | Grade School admin operational data |
| `mysql_jh_teacher` / `mysql_gs_teacher` | Legacy teacher-portal tables (being phased out; use admin DB `teacher_requests`) |

## Fresh install

```bash
php artisan migrate
php artisan db:seed
```

Legacy one-off SQL scripts were removed; they duplicated migration logic and caused drift.
