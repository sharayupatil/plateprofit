@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Import POS sales</h1>

    <p>
        Upload a CSV containing order, customer and product data.
        Slash-separated order dates use DD/MM/YYYY, for example 21/11/2026 12:45.
    </p>

    @if ($errors->any())
        <div>
            <strong>The file could not be imported.</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('pos-imports.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        <div>
            <label for="csv_file">POS CSV file</label>

            <input
                id="csv_file"
                name="csv_file"
                type="file"
                accept=".csv,text/csv"
                required
            >
        </div>

        <button type="submit">
            Upload and import
        </button>
    </form>
</div>
@endsection