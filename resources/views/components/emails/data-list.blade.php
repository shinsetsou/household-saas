<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background-color:#ffffff;">
    @foreach ($rows as $index => $row)
        @php
            $label = array_key_first($row);
            $value = $row[$label];
            $bg = $index % 2 === 0 ? '#f8fafc' : '#ffffff';
        @endphp
        <tr>
            <td style="padding:12px 16px;background-color:{{ $bg }};font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;font-weight:600;color:#64748b;letter-spacing:0.04em;text-transform:uppercase;width:40%;vertical-align:middle;">
                {{ $label }}
            </td>
            <td style="padding:12px 16px;background-color:{{ $bg }};font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;font-weight:600;color:#0f172a;vertical-align:middle;">
                {!! $value !!}
            </td>
        </tr>
    @endforeach
</table>
