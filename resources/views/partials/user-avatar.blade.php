@php
    use App\Support\UserProfileSupport;
    $user = $user ?? null;
    $size = (int) ($size ?? 36);
    $name = UserProfileSupport::displayName($user);
    $initials = UserProfileSupport::initials($user);
    $photoUrl = UserProfileSupport::photoUrl($user);
@endphp
<span class="user-avatar-chip" style="width:{{ $size }}px;height:{{ $size }}px;border-radius:50%;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2d7a50,#1a5336);color:#fff;font-weight:700;font-size:{{ max(10, (int) round($size * 0.32)) }}px;flex-shrink:0;">
    @if($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $name }}" style="width:100%;height:100%;object-fit:cover;">
    @else
        {{ $initials }}
    @endif
</span>
