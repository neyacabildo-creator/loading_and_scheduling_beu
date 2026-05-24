<table class="mon-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Grade / Section</th>
            <th>Day</th>
            <th>Date</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td>#{{ $row['id'] }}</td>
                <td>{{ $row['subject'] }}</td>
                <td>{{ $row['faculty_name'] }}</td>
                <td>{{ $row['grade_level'] }} {{ $row['section_name'] }}</td>
                <td>{{ $row['day_of_week'] }}</td>
                <td>{{ $row['schedule_date'] ?? '—' }}</td>
                <td><a class="mon-link" href="{{ $scheduleUrl }}?highlight={{ $row['id'] }}">Fix in schedule</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
