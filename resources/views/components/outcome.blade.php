@props(['outcome' => null])
@if($outcome)
<span class="badge-{{ $outcome }}">{{ match($outcome) {
    'pass'       => '✓ Pass',
    'soft_pass'  => '~ Soft pass',
    'fail'       => '✗ Fail',
    'pending'    => '? Pending',
    default      => $outcome,
} }}</span>
@else
<span style="color:#94a3b8">—</span>
@endif
