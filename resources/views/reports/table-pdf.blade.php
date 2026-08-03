<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { margin: 0 0 4px; color: #4c1d95; font-size: 22px; }
        .meta { margin-bottom: 18px; color: #6b7280; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f3ff; color: #4c1d95; font-weight: bold; text-align: left; }
        th, td { border: 1px solid #e9d5ff; padding: 7px; vertical-align: top; }
        tr:nth-child(even) td { background: #fafafa; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ $scope ?? 'Elevanix' }} | Generated on {{ $generatedAt->format('M d, Y h:i A') }} | {{ $rows->count() }} records
        @if(! empty($filters))
            <br>Filters:
            @foreach($filters as $label => $value)
                {{ $label }}: {{ $value }}@if(! $loop->last), @endif
            @endforeach
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">No report data available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
