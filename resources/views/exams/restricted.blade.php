@extends('layouts.app')

@section('title', 'Acceso restringido')

@section('content')
<div class="max-w-2xl mx-auto py-10 space-y-6">

    <h1 class="text-3xl font-bold text-center text-error">No puedes acceder a este examen</h1>

    <div class="alert alert-error flex flex-col space-y-1">
        <span class="font-medium">Motivos:</span>
        <ul class="list-disc list-inside">
            @foreach ($errors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    <div class="text-center">
        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-primary">
            ← Volver al dashboard
        </a>
    </div>

</div>
@endsection
