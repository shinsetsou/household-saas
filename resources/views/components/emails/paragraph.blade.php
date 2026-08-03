@php
    $color = $muted ? '#475569' : '#1e293b';
@endphp
<p style="margin:0 0 16px;color:{{ $color }};font-size:15px;line-height:1.6;">{!! $slot !!}</p>
