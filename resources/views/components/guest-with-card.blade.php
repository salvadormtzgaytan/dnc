<x-guest-layout>
    @section('left-column')
        @include('components.login-card')
    @endsection
    
    @section('content')
        {{ $slot }}
    @endsection
</x-guest-layout>