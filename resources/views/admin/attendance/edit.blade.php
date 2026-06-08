@extends('layouts.admin')

@section('content')
    <h1>Edit Attendance</h1>
    <a class="btn" href="/admin/attendance">Back to Attendance</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <h2>{{ $attendance->employee?->name }} - {{ $attendance->attendance_date?->toDateString() }}</h2>
        <form method="POST" action="/admin/attendance/{{ $attendance->id }}/update">
            @csrf
            <p>
                Client<br>
                <select name="client_id">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $attendance->client_id) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>
            <p>
                Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $attendance->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>
                Day Type<br>
                <select name="is_working_day">
                    <option value="1" {{ old('is_working_day', $attendance->is_working_day ? '1' : '0') === '1' ? 'selected' : '' }}>Working Day</option>
                    <option value="0" {{ old('is_working_day', $attendance->is_working_day ? '1' : '0') === '0' ? 'selected' : '' }}>Non Working Day</option>
                </select>
            </p>
            <p>Check In<br><input type="datetime-local" name="check_in_at" value="{{ old('check_in_at', $attendance->check_in_at?->format('Y-m-d\\TH:i')) }}"></p>
            <p>Check Out<br><input type="datetime-local" name="check_out_at" value="{{ old('check_out_at', $attendance->check_out_at?->format('Y-m-d\\TH:i')) }}"></p>
            <p>Note<br><textarea name="note">{{ old('note', $attendance->note) }}</textarea></p>
            <button class="btn" type="submit">Update Attendance</button>
        </form>
    </div>
@endsection
