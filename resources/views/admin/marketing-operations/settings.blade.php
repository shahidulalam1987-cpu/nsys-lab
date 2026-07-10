@extends('layouts.admin')

@section('content')
    <h1>Marketing Operations Settings</h1>
    <p>Central timing configuration for submission windows, review windows, SLA buffers, and reminders.</p>

    <div class="card">
        <form method="POST" action="/admin/marketing-operations/settings" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            @csrf
            @foreach($settings as $key => $value)
                <label>
                    {{ $labels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}<br>
                    @if(str_contains($key, '_start') || str_contains($key, '_end'))
                        <input type="time" name="{{ $key }}" value="{{ $value }}" required>
                    @elseif(str_contains($key, '_minutes'))
                        <input type="text" name="{{ $key }}" value="{{ $value }}" required>
                    @else
                        <input type="text" name="{{ $key }}" value="{{ $value }}" required>
                    @endif
                </label>
            @endforeach
            <div style="grid-column:1/-1;">
                <button class="btn" type="submit">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Active Modules</h2>
        <table>
            <tr><th>Module</th><th>Status</th></tr>
            @foreach($modules as $module)
                <tr><td>{{ $module }}</td><td><span class="badge badge-success">Active</span></td></tr>
            @endforeach
        </table>
    </div>
@endsection
