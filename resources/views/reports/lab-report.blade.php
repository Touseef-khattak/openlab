<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $visit->visit_number }} — Lab Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; padding: 24px 32px; }
        .header { text-align: center; border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 12px; }
        .header h1 { font-size: 20px; letter-spacing: 1px; }
        .header p { font-size: 9px; color: #555; margin-top: 2px; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 2px 4px; vertical-align: top; }
        .meta .label { color: #666; width: 90px; }
        .section-title { background: #eee; padding: 4px 8px; font-weight: bold; font-size: 12px; margin: 12px 0 4px; }
        .specimen { color: #555; font-size: 9px; margin-bottom: 4px; }
        table.results { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.results th { text-align: left; border-bottom: 1px solid #999; padding: 3px 6px; font-size: 10px; color: #444; }
        table.results td { padding: 3px 6px; border-bottom: 1px dotted #ccc; }
        .test-name { font-weight: bold; padding-top: 6px !important; }
        .flag { font-weight: bold; }
        .flag-high, .flag-low { color: #b45309; }
        .flag-critical_high, .flag-critical_low { color: #b91c1c; }
        .footer { margin-top: 28px; }
        .sign { margin-top: 40px; width: 100%; }
        .sign td { width: 50%; text-align: center; }
        .sign .line { border-top: 1px solid #333; margin: 0 40px; padding-top: 4px; font-size: 10px; }
        .disclaimer { margin-top: 16px; font-size: 8px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if ($branding->logo)
            <img src="{{ $branding->logo }}" alt="Logo" style="max-height: 56px; margin-bottom: 4px;">
        @endif
        <h1>{{ $branding->name ?: config('app.name') }}</h1>
        @if ($branding->address || $branding->phone || $branding->email)
            <p>{{ collect([$branding->address, $branding->phone, $branding->email])->filter()->implode(' · ') }}</p>
        @endif
        <p>{{ $branding->report_header ?: 'Clinical Laboratory Report' }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Patient</td>
            <td><strong>{{ $patient->name }}</strong> ({{ $patient->mrn }})</td>
            <td class="label">Visit</td>
            <td>{{ $visit->visit_number }}</td>
        </tr>
        <tr>
            <td class="label">Age / Gender</td>
            <td>
                @if ($patient->dob)
                    {{ $patient->dob->age }} y
                @elseif ($patient->age_years !== null)
                    {{ $patient->age_years }} y
                @else
                    —
                @endif
                / {{ ucfirst($patient->gender->value ?? $patient->gender) }}
            </td>
            <td class="label">Registered</td>
            <td>{{ $visit->visited_at->format('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Referred by</td>
            <td>{{ $visit->doctor?->name ?? 'Self' }}</td>
            <td class="label">Printed</td>
            <td>{{ now()->format('d M Y H:i') }}</td>
        </tr>
    </table>

    @foreach ($sections as $section)
        <div class="section-title">{{ $section['specimen_type'] }} — {{ $section['sample_number'] }}</div>
        <div class="specimen">
            Collected: {{ $section['collected_at'] ?? '—' }}
        </div>
        <table class="results">
            <thead>
                <tr>
                    <th style="width: 34%">Investigation</th>
                    <th style="width: 16%">Result</th>
                    <th style="width: 8%">Flag</th>
                    <th style="width: 14%">Unit</th>
                    <th style="width: 28%">Reference Range</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($section['tests'] as $test)
                    <tr>
                        <td class="test-name" colspan="5">{{ $test['name'] }}</td>
                    </tr>
                    @foreach ($test['parameters'] as $parameter)
                        <tr>
                            <td style="padding-left: 16px">{{ $parameter['name'] }}</td>
                            <td><strong>{{ $parameter['value'] ?? '—' }}</strong></td>
                            <td>
                                @if ($parameter['flag'] && $parameter['flag'] !== 'normal')
                                    <span class="flag flag-{{ $parameter['flag'] }}">
                                        {{ ['low' => 'L', 'high' => 'H', 'critical_low' => 'C↓', 'critical_high' => 'C↑'][$parameter['flag']] ?? '' }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $parameter['unit'] }}</td>
                            <td>
                                @if ($parameter['normal_text'])
                                    {{ $parameter['normal_text'] }}
                                @elseif ($parameter['range_low'] !== null && $parameter['range_high'] !== null)
                                    {{ $parameter['range_low'] }} – {{ $parameter['range_high'] }}
                                @elseif ($parameter['range_high'] !== null)
                                    &lt; {{ $parameter['range_high'] }}
                                @elseif ($parameter['range_low'] !== null)
                                    &gt; {{ $parameter['range_low'] }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <table class="sign">
            <tr>
                <td>
                    <div class="line">
                        Verified by{{ $verifierName ? ': ' . $verifierName : '' }}
                        @if ($verifiedAt)
                            <br>{{ $verifiedAt }}
                        @endif
                    </div>
                </td>
                <td><div class="line">Pathologist Signature</div></td>
            </tr>
        </table>
        <p class="disclaimer">
            This report contains only verified results. Results relate only to the sample(s) received.
            Kindly correlate clinically. Generated by {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>
