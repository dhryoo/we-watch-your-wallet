@props(['url', 'note' => true])
{{-- 표시 전용. 새 탭으로 revoke.cash 열기 — 우리 서비스는 트랜잭션을 생성·실행하지 않는다. --}}
<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="wt-btn wt-btn--solid wt-revoke">Open revoke page <span style="font-size:13px;">↗</span></a>
@if ($note)
    <p class="wt-revoke-note">Revoking happens in your own wallet — we never do it for you.</p>
@endif
