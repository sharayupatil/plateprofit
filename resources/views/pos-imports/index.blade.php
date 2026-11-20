@extends('layouts.app')

@section('content')
<div class="container">
    <h1>POS imports</h1>

    @if (session('success'))
        <div>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <p>
        <a href="{{ route('pos-imports.create') }}">
            Import another CSV
        </a>
    </p>

    <table>
        <thead>
            <tr>
                <th>File</th>
                <th>Status</th>
                <th>Processed</th>
                <th>Successful</th>
                <th>Failed</th>
                <th>Uploaded</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($imports as $import)
                <tr>
                    <td>{{ $import->original_filename }}</td>
                    <td>{{ $import->status }}</td>
                    <td>{{ $import->rows_processed }}</td>
                    <td>{{ $import->rows_successful }}</td>
                    <td>{{ $import->rows_failed }}</td>
                    <td>{{ $import->created_at->format('d M Y H:i') }}</td>
                </tr>

                @if ($import->errors->isNotEmpty())
                    <tr>
                        <td colspan="6">
                            <strong>Errors</strong>

                            <ul>
                                @foreach ($import->errors as $error)
                                    <li>
                                        Row {{ $error->row_number }}:
                                        {{ $error->message }}
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6">
                        No POS files have been imported.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $imports->links() }}
</div>
@endsection