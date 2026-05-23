@once
<style>
.teacher-dash-banner {
    background: linear-gradient(135deg, #1a5336 0%, #2d7a50 60%, #3d9970 100%);
    border-radius: 0.75rem;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.teacher-banner-eyebrow {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.82rem;
    margin: 0 0 0.3rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.teacher-banner-title {
    color: #fff;
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 0.3rem;
}
.teacher-banner-subtitle {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.875rem;
    margin: 0;
}
.teacher-dash-banner-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}
.teacher-banner-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 0.375rem;
    cursor: pointer;
    text-decoration: none;
    line-height: 1.2;
}
.teacher-banner-btn:hover { background: rgba(255, 255, 255, 0.25); }
.teacher-banner-print-btn { padding: 0.6rem 1.25rem; font-size: 0.875rem; border-radius: 0.45rem; }
.teacher-theme-toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    min-width: 42px;
    min-height: 42px;
}
.teacher-theme-toggle-btn .teacher-theme-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.95);
}
.teacher-dash-banner-actions .tp-notif-btn { color: rgba(255, 255, 255, 0.9) !important; }
.teacher-dash-banner-actions .tp-notif-btn:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #fff !important;
}
.teacher-dash-banner-actions .tp-notif-dot { border: 1px solid rgba(255, 255, 255, 0.5); }
</style>
@endonce
