{{-- resources/views/junior-high-admin/shared-teachers.blade.php --}}
@extends('layouts.admin')

@section('title', 'Shared Teachers')

@section('content')
<style>
.sht-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--shadow-sm);}
.sht-title{font-size:1.1rem;font-weight:700;color:var(--text-primary);margin:0 0 1rem;}
.sht-label{font-size:.78rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.35rem;}
.sht-input{width:100%;padding:.6rem .85rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.875rem;box-sizing:border-box;}
.sht-input:focus{outline:none;border-color:var(--green-primary);box-shadow:0 0 0 3px rgba(45,122,80,.1);}
.sht-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1rem;}
.sht-btn{padding:.6rem 1.4rem;border:none;border-radius:.375rem;font-weight:600;font-size:.875rem;cursor:pointer;}
.sht-btn-primary{background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;}
.sht-btn-primary:hover{opacity:.9;}
.sht-table{width:100%;border-collapse:collapse;font-size:.85rem;}
.sht-table th{padding:.65rem 1rem;background:var(--bg-tertiary);border:1px solid var(--border-color);text-align:left;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);}
.sht-table td{padding:.65rem 1rem;border:1px solid var(--border-color);vertical-align:middle;color:var(--text-primary);}
.sht-table tr:hover td{background:var(--bg-tertiary);}
.sht-badge-active{display:inline-block;background:rgba(34,197,94,.15);color:#15803d;border-radius:.25rem;padding:.15rem .55rem;font-size:.72rem;font-weight:700;}
.sht-badge-inactive{display:inline-block;background:rgba(156,163,175,.15);color:#6b7280;border-radius:.25rem;padding:.15rem .55rem;font-size:.72rem;font-weight:700;}
.sht-badge-shared{display:inline-block;background:rgba(59,130,246,.12);color:#2563eb;border-radius:.25rem;padding:.15rem .55rem;font-size:.72rem;font-weight:700;}
.sht-action-btn{padding:.3rem .7rem;border-radius:.25rem;font-size:.75rem;font-weight:600;cursor:pointer;border:1px solid;text-decoration:none;display:inline-block;}
.sht-toggle-btn{border-color:#d1d5db;background:var(--bg-secondary);color:var(--text-secondary);}
.sht-toggle-btn:hover{border-color:var(--green-primary);color:var(--green-primary);}
.sht-del-btn{border-color:#fca5a5;background:rgba(239,68,68,.07);color:#dc2626;margin-left:.35rem;}
.sht-del-btn:hover{background:rgba(239,68,68,.15);}
.sht-empty{text-align:center;padding:2.5rem;color:var(--text-secondary);font-size:.875rem;}
</style>

<div class="header">
    <div class="header-left">
        <svg width="22" height="22" fill="none" stroke="#2d7a50" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <h1 class="page-title">Shared Teachers</h1>
    </div>
</div>

{{-- Add Form --}}
<div class="sht-card">
    <div class="sht-title">Register Shared Teacher</div>
    <p style="font-size:.82rem;color:var(--text-secondary);margin:0 0 1.2rem;">Add a teacher who comes from Grade School to also teach in Junior High (or vice versa). They will be marked as <span class="sht-badge-shared">Shared</span> in schedule creation.</p>
    <form method="POST" action="{{ route('admin.shared-teachers.store') }}">
        @csrf
        <div class="sht-grid">
            <div>
                <label class="sht-label">Teacher Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="teacher_name" class="sht-input" placeholder="Full name" required value="{{ old('teacher_name') }}">
                @error('teacher_name')<span style="color:#dc2626;font-size:.75rem;">{{ $message }}</span>@enderror
            </div>
            <div>
                <label class="sht-label">Link to User Account (optional)</label>
                <select name="faculty_id" class="sht-input">
                    <option value="">— Select Teacher Account —</option>
                    @foreach($jhTeachers as $t)
                    <option value="{{ $t->id }}" {{ old('faculty_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->first_name }} {{ $t->last_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sht-label">Department / Subject Area</label>
                <input type="text" name="department" class="sht-input" placeholder="e.g. Mathematics" value="{{ old('department') }}">
            </div>
            <div>
                <label class="sht-label">Email (optional)</label>
                <input type="email" name="email" class="sht-input" placeholder="teacher@school.edu" value="{{ old('email') }}">
            </div>
        </div>
        <div style="margin-bottom:1rem;">
            <label class="sht-label">Notes (optional)</label>
            <input type="text" name="notes" class="sht-input" placeholder="e.g. Available Mon–Wed only" value="{{ old('notes') }}">
        </div>
        <button type="submit" class="sht-btn sht-btn-primary">+ Add Shared Teacher</button>
    </form>
</div>

{{-- List --}}
<div class="sht-card">
    <div class="sht-title">Registered Shared Teachers <span style="font-size:.82rem;color:var(--text-secondary);font-weight:400;">({{ $sharedTeachers->count() }} total)</span></div>

    @if($sharedTeachers->isEmpty())
        <div class="sht-empty">No shared teachers registered yet. Add one above.</div>
    @else
    <table class="sht-table">
        <thead>
            <tr>
                <th>Teacher Name</th>
                <th>Department</th>
                <th>Email</th>
                <th>Notes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sharedTeachers as $st)
            <tr>
                <td>
                    <strong>{{ $st->teacher_name }}</strong>
                    @if($st->faculty_id)
                        <span style="font-size:.7rem;color:var(--text-secondary);display:block;">Linked to user #{{ $st->faculty_id }}</span>
                    @endif
                </td>
                <td>{{ $st->department ?: '—' }}</td>
                <td style="font-size:.78rem;">{{ $st->email ?: '—' }}</td>
                <td style="font-size:.78rem;max-width:200px;">{{ $st->notes ?: '—' }}</td>
                <td>
                    @if($st->is_active)
                        <span class="sht-badge-active">Active</span>
                    @else
                        <span class="sht-badge-inactive">Inactive</span>
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    <form method="POST" action="{{ route('admin.shared-teachers.toggle', $st->id) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="sht-action-btn sht-toggle-btn" title="{{ $st->is_active ? 'Mark inactive' : 'Mark active' }}">
                            {{ $st->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.shared-teachers.destroy', $st->id) }}" style="display:inline;" onsubmit="return confirm('Remove {{ addslashes($st->teacher_name) }} from shared teachers?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="sht-action-btn sht-del-btn">Remove</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
