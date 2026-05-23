{{-- Junior High Teacher: Provide Feedback --}}
@extends('layouts.teacher')

@section('title', 'Provide Feedback')

@section('content')
<style>
    .header-bar{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white;padding:2rem;border-radius:.75rem;margin-bottom:2rem}
    .header-title{font-size:1.875rem;font-weight:bold;margin:0 0 .5rem}
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
    @media(max-width:768px){.two-col{grid-template-columns:1fr}}
    .card{background:var(--bg-secondary);border-radius:.75rem;padding:1.75rem;border:1px solid var(--border-color)}
    .card-title{font-size:1.1rem;font-weight:600;color:var(--text-primary);margin:0 0 1.25rem}
    .form-group{margin-bottom:1.1rem}
    .form-group label{display:block;font-size:.875rem;font-weight:500;color:var(--text-primary);margin-bottom:.4rem}
    .form-group select,.form-group textarea{width:100%;padding:.65rem .85rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-primary);color:var(--text-primary);font-size:.875rem;font-family:inherit;box-sizing:border-box}
    .form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--green-primary);box-shadow:0 0 0 3px rgba(45,122,80,.1)}
    .star-group{display:flex;gap:.5rem;margin-top:.25rem}
    .star{font-size:1.75rem;cursor:pointer;color:#d1d5db;transition:color .15s;line-height:1}
    .star.selected,.star:hover{color:#f59e0b}
    .btn-submit{background:var(--green-primary);color:white;padding:.75rem 2rem;border:none;border-radius:.5rem;font-weight:600;font-size:.95rem;cursor:pointer;transition:background .2s;width:100%;margin-top:.5rem}
    .btn-submit:hover{background:#1a5c3a}
    .alert-success{background:rgba(16,185,129,.1);color:#065f46;border:1px solid rgba(16,185,129,.3);border-radius:.5rem;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:.875rem}
    /* History table */
    .hist-table{width:100%;border-collapse:collapse}
    .hist-table thead{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white}
    .hist-table th,.hist-table td{padding:.75rem 1rem;text-align:left;font-size:.85rem}
    .hist-table tbody tr{border-top:1px solid var(--border-color)}
    .hist-table tbody tr:hover{background:rgba(45,122,80,.04)}
    .badge{display:inline-block;padding:.2rem .65rem;border-radius:.25rem;font-size:.75rem;font-weight:600}
    .badge-submitted{background:rgba(59,130,246,.1);color:#1d4ed8}
    .badge-reviewed{background:rgba(245,158,11,.1);color:#b45309}
    .badge-resolved{background:rgba(16,185,129,.1);color:#065f46}
    .stars-display{color:#f59e0b;letter-spacing:.1rem}
    .empty-state{text-align:center;padding:2rem;color:var(--text-secondary);font-size:.875rem}
    .category-label{background:rgba(45,122,80,.1);color:var(--green-primary);padding:.15rem .5rem;border-radius:.25rem;font-size:.75rem;font-weight:600}
</style>

<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Provide Feedback</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Share your feedback on schedule clarity, workload fairness, and system usability</p>
</div>

<div class="two-col">
    <!-- Feedback Form -->
    <div class="card">
        <p class="card-title">Submit Feedback</p>
        <form method="POST" action="{{ route('teacher.feedback.submit') }}">
            @csrf
            <div class="form-group">
                <label for="category">Category *</label>
                <select name="category" id="category" required>
                    <option value="">Select a category...</option>
                    <option value="schedule_clarity" {{ old('category') === 'schedule_clarity' ? 'selected' : '' }}>Schedule Clarity</option>
                    <option value="workload_fairness" {{ old('category') === 'workload_fairness' ? 'selected' : '' }}>Workload Fairness</option>
                    <option value="system_usability" {{ old('category') === 'system_usability' ? 'selected' : '' }}>System Usability</option>
                    <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('category')<p style="color:#ef4444;font-size:.75rem;margin-top:.25rem">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label>Rating *</label>
                <div class="star-group" id="starGroup">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="star {{ old('rating') >= $i ? 'selected' : '' }}" data-value="{{ $i }}" onclick="setRating({{ $i }})">★</span>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="{{ old('rating', 0) }}" required>
                @error('rating')<p style="color:#ef4444;font-size:.75rem;margin-top:.25rem">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea name="message" id="message" rows="5" required maxlength="2000" placeholder="Describe your experience, suggestions, or concerns...">{{ old('message') }}</textarea>
                @error('message')<p style="color:#ef4444;font-size:.75rem;margin-top:.25rem">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-submit">Submit Feedback</button>
        </form>
    </div>

    <!-- Guidelines & Info -->
    <div>
        <div class="card" style="margin-bottom:1.5rem">
            <p class="card-title">Feedback Guidelines</p>
            <ul style="color:var(--text-secondary);font-size:.875rem;margin:0;padding-left:1.25rem;line-height:1.75">
                <li><strong>Schedule Clarity</strong> – Is your assigned schedule clear and accurate?</li>
                <li><strong>Workload Fairness</strong> – Is your teaching load balanced and reasonable?</li>
                <li><strong>System Usability</strong> – Is the scheduling system easy to use?</li>
                <li><strong>Other</strong> – Any other suggestions or concerns.</li>
            </ul>
            <div style="margin-top:1rem;padding:.85rem;background:rgba(45,122,80,.07);border-left:3px solid var(--green-primary);border-radius:.375rem;font-size:.8rem;color:var(--text-primary);">
                Your feedback is reviewed by the administrator and helps improve scheduling fairness and system performance.
            </div>
        </div>

        <!-- My Feedback History -->
        <div class="card">
            <p class="card-title">My Recent Submissions</p>
            @if($myFeedbacks->isEmpty())
                <p class="empty-state">No feedback submitted yet.</p>
            @else
                <div style="overflow-x:auto">
                    <table class="hist-table">
                        <thead>
                            <tr><th>Category</th><th>Rating</th><th>Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($myFeedbacks as $fb)
                            <tr>
                                <td><span class="category-label">{{ str_replace('_', ' ', ucwords($fb->category, '_')) }}</span></td>
                                <td><span class="stars-display">{{ str_repeat('★', $fb->rating) }}<span style="color:var(--text-secondary)">{{ str_repeat('★', 5 - $fb->rating) }}</span></span></td>
                                <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($fb->created_at)->format('M d, Y') }}</td>
                                <td><span class="badge badge-{{ $fb->status }}">{{ ucfirst($fb->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function setRating(value) {
    document.getElementById('ratingInput').value = value;
    document.querySelectorAll('.star').forEach((star, idx) => {
        star.classList.toggle('selected', idx < value);
    });
}
// Hover effect
document.querySelectorAll('.star').forEach((star, idx, stars) => {
    star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.style.color = i <= idx ? '#f59e0b' : '');
    });
    star.addEventListener('mouseleave', () => {
        const val = parseInt(document.getElementById('ratingInput').value || 0);
        stars.forEach((s, i) => s.style.color = i < val ? '#f59e0b' : '#d1d5db');
    });
});
// Init color from old value
const initVal = parseInt(document.getElementById('ratingInput').value || 0);
document.querySelectorAll('.star').forEach((s, i) => s.style.color = i < initVal ? '#f59e0b' : '#d1d5db');
</script>
@endsection
