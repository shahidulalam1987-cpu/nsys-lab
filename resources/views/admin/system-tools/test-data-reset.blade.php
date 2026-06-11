@extends('layouts.admin')

@section('content')
    <div>
        <h1>Test Data Reset</h1>
        <p>Development-only cleanup for test records. Production reset is always disabled.</p>
    </div>

    <div class="card">
        @if($isProduction)
            <div class="alert alert-danger">Test data reset is disabled in production.</div>
        @else
            <div class="alert alert-danger">
                This tool deletes selected testing records. Type <strong>RESET TEST DATA</strong> before running it.
            </div>
        @endif

        <form method="POST" action="/admin/test-data-reset" style="display:grid;gap:16px;">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;">
                @foreach($options as $value => $label)
                    <label style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;padding:10px;">
                        <input type="checkbox" name="options[]" value="{{ $value }}" @disabled($isProduction)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <label>
                Confirmation
                <input type="text" name="confirmation" placeholder="RESET TEST DATA" @disabled($isProduction)>
            </label>

            <div style="display:flex;justify-content:flex-end;">
                <button class="btn btn-danger" type="submit" onclick="return confirm('Reset selected test data?');" @disabled($isProduction)>Reset Selected Data</button>
            </div>
        </form>
    </div>
@endsection
