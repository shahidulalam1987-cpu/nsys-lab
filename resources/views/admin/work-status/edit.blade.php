@extends('layouts.admin')

@section('content')
    <h1>Edit Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $workStatus->employee?->name }} - {{ $workStatus->work_date?->toDateString() }}</h2>
        <form method="POST" action="/admin/work-status/{{ $workStatus->id }}/update">
            @csrf
            <p>Client<br>
                <select name="client_id">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $workStatus->client_id) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>
            <p>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $workStatus->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Salary Count Value<br>
                <input type="number" step="0.5" min="0" max="1" name="salary_count_value" value="{{ old('salary_count_value', $workStatus->salary_count_value) }}">
            </p>
            <p>Note<br><textarea name="note">{{ old('note', $workStatus->note) }}</textarea></p>
            <button class="btn" type="submit">Update Work Status</button>
        </form>
    </div>
@endsection
