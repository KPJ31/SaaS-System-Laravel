@extends('layouts.app')

@section('title', $title.' - Elevanix')

@section('content')
@include('partials.page-header', [
    'eyebrow' => 'Report Preview',
    'title' => $title,
    'description' => 'Generated '.$generatedAt->format('M d, Y h:i A').'. Check the data below before downloading.',
    'actions' => new Illuminate\Support\HtmlString(
        '<a class="btn btn-outline-primary" href="'.route('super-admin.reports.export', $report).'"><i class="fa-solid fa-file-csv"></i> Download CSV</a>'.
        '<a class="btn btn-primary" href="'.route('super-admin.reports.pdf', $report).'"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>'
    ),
])

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h2>Report Records</h2>
            <p>{{ $rows->count() }} records found.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
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
                        <td colspan="{{ count($headers) }}" class="empty-cell">
                            @include('partials.empty-state', ['icon' => 'fa-file-lines', 'title' => 'No report data', 'message' => 'There are no records for this report yet.'])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
