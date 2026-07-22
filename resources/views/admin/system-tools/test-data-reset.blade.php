@extends('layouts.admin')

@section('content')
    <style>
        .reset-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .reset-option-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 10px;
        }

        .reset-option-card {
            align-items: flex-start;
            background: rgba(255,255,255,.03);
            border: 1px solid var(--line);
            border-radius: 10px;
            display: flex;
            gap: 10px;
            padding: 12px;
        }

        .reset-option-meta {
            color: var(--muted);
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 12px;
            margin-top: 6px;
        }

        .reset-acknowledgement {
            align-items: flex-start;
            background: rgba(245, 158, 11, .1);
            border: 1px solid rgba(245, 158, 11, .45);
            border-radius: 10px;
            display: flex;
            gap: 10px;
            padding: 12px;
        }

        .reset-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
    </style>

    <div class="reset-header">
        <div>
            <h1>Test Data Reset</h1>
            <p>Development-only cleanup for test records. Production reset is always disabled.</p>
        </div>
        <span class="badge {{ $isProduction ? 'badge-danger' : 'badge-warning' }}">Environment: {{ $environment }}</span>
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
            <div class="reset-option-grid">
                @foreach($options as $value => $label)
                    @php($isHighRisk = in_array($value, $highRiskOptions, true))
                    <label class="reset-option-card">
                        <input type="checkbox" name="options[]" value="{{ $value }}" @disabled($isProduction)>
                        <span>
                            <strong>{{ $label }}</strong>
                            <span class="reset-option-meta">
                                <span>Records: {{ number_format((int) ($optionCounts[$value] ?? 0)) }}</span>
                                @if($isHighRisk)
                                    <span class="badge badge-warning">High Risk</span>
                                @else
                                    <span class="badge badge-info">Test Scoped</span>
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            <label class="reset-acknowledgement">
                <input type="checkbox" name="acknowledge_high_risk" value="1" @disabled($isProduction)>
                <span>
                    <strong>I understand selected high-risk options may delete all records in that module.</strong>
                    <br><span style="color:var(--muted);">Use only for local/testing cleanup after confirming record counts.</span>
                </span>
            </label>

            <label>
                Confirmation
                <input type="text" name="confirmation" placeholder="RESET TEST DATA" @disabled($isProduction)>
            </label>

            <div class="reset-actions">
                <button class="btn btn-danger" type="submit" onclick="return confirm('Reset selected test data?');" @disabled($isProduction)>Reset Selected Data</button>
            </div>
        </form>
    </div>
@endsection
