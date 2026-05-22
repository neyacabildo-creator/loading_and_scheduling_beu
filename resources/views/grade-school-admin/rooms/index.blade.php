{{-- resources/views/grade-school-admin/rooms/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Grade School - Rooms Management')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Grade School Rooms Management</h1>
        </div>
    </div>

    <!-- Content -->
    <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0; font-size: 1.25rem;">Classroom Rooms (Grade School Only)</h2>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary" style="background: var(--primary); color: white; padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; cursor: pointer; text-decoration: none;">Back to Dashboard</a>
        </div>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--bg-primary); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Room Code</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Room Name</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Capacity</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600;">School Level</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Status</th>
                </tr>
            </thead>
            <tbody id="rooms-table">
                <tr>
                    <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-secondary);">Loading rooms...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        // Fetch rooms from API
        fetch('/api/grade-school-admin/rooms')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('rooms-table');
                if (data.data && data.data.length > 0) {
                    container.innerHTML = data.data.map(room => `
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem; font-weight: 500;">${room.code}</td>
                            <td style="padding: 1rem;">${room.name}</td>
                            <td style="padding: 1rem;">${room.capacity || 'N/A'}</td>
                            <td style="padding: 1rem;">
                                <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.875rem;">Grade School</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.875rem;">Active</span>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    container.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-secondary);">No rooms found.</td></tr>';
                }
            })
            .catch(error => {
                document.getElementById('rooms-table').innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #ef4444;">Error loading rooms</td></tr>';
                console.error(error);
            });
    </script>
@endsection
