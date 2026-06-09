@extends('layouts.employee')

@section('content')
    <h1>My Documents</h1>

    @php
        $documents = [
            'appointment_letter_file' => 'Appointment Letter',
            'agreement_file' => 'Agreement',
            'nid_front_file' => 'NID Front',
            'nid_back_file' => 'NID Back',
            'cv_file' => 'CV / Uploaded Documents',
        ];
    @endphp

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @foreach($documents as $field => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $employee->{$field} ? 'Uploaded' : 'Not Uploaded' }}</td>
                        <td>
                            @if($employee->{$field})
                                <a class="btn" href="{{ \Illuminate\Support\Facades\Storage::url($employee->{$field}) }}" target="_blank">View / Download</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
