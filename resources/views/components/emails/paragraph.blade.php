@php
    $color = $muted ? '#475569' : '#0f172a';
@endphp
<p style="margin:0 0 16px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{{ $color }};font-size:15px;line-height:1.6;">{!! $slot !!}</p>
