@foreach($exams as $exam)
    <x-card-exam :exam="$exam" />
@endforeach
