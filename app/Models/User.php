<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** Users always live on the main app database (not principal/JH/GS DBs). */
    protected $connection = 'mysql';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'school_level',
        'shared_teacher_subjects',
        'is_active',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_encrypted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active_session_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'shared_teacher_subjects' => 'array',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    protected static function booted(): void
    {
        // Keep the principal all_users snapshot in sync whenever a user is saved or deleted.
        static::saved(function (User $user) {
            try {
                $roleName = \App\Models\Role::find($user->role_id)?->name ?? '';
                if ($roleName === 'super_admin') {
                    $roleName = 'principal';
                }
                \Illuminate\Support\Facades\DB::connection('mysql_principal')
                    ->table('all_users')
                    ->updateOrInsert(
                        ['id' => $user->id],
                        [
                            'name'         => $user->name,
                            'email'        => $user->email,
                            'role'         => $roleName,
                            'school_level' => $user->school_level ?? 'system',
                            'is_active'    => (int) $user->is_active,
                            'synced_at'    => now(),
                            'updated_at'   => now(),
                            'created_at'   => $user->created_at ?? now(),
                        ]
                    );
            } catch (\Exception) {}
        });

        static::deleted(function (User $user) {
            try {
                \Illuminate\Support\Facades\DB::connection('mysql_principal')
                    ->table('all_users')->where('id', $user->id)->delete();
            } catch (\Exception) {}
        });
    }

    public function loads()
    {
        return $this->hasMany(FacultyLoad::class, 'faculty_id');
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }
}
