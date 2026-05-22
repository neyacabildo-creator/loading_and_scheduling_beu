<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Fetch all department-specific roles
        $roles = Role::whereIn('name', [
            'teacher_grade_school',
            'teacher_junior_high',
            'admin_grade_school',
            'admin_junior_high'
        ])->get();
        
        return view('auth.register', compact('roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role_id' => ['required', 'exists:roles,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Get the role and extract department from role name
        $role = Role::find($request->role_id);
        $schoolLevel = 'grade_school'; // default
        
        if ($role) {
            if (strpos($role->name, 'junior_high') !== false) {
                $schoolLevel = 'junior_high';
            } elseif (strpos($role->name, 'grade_school') !== false) {
                $schoolLevel = 'grade_school';
            }
        }

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'position' => $request->position,
            'school_level' => $schoolLevel,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        event(new Registered($user));

        return redirect(route('login', absolute: false))->with('status', 'Registration successful! Please log in with your credentials.');
    }
}
