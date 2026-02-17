<nav x-data="{ open: false }" class="nav-header-view flex items-center h-[75px] bg-primary text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
        <div class="flex justify-between items-center w-full">
            <!-- Logo y menú izquierdo -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}">
                    <x-dashboard-logo class="block fill-current" />
                </a>
            </div>

            <!-- Botones de perfil y logout -->
            <div class="flex items-center space-x-6">
                <!-- Ir al perfil -->
                <a href="{{ route('profile.edit') }}" class="flex items-center h-full" title="Perfil">
                    <i class="fa-duotone fa-solid fa-user text-[1.5rem]"></i>
                </a>

                <!-- Cerrar sesión -->
                <form method="POST" action="{{ route('logout') }}" class="flex items-center h-full">
                    @csrf
                    <button type="submit" class="focus:outline-none flex items-center h-full" title="Salir">
                        <i class="fa-sharp-duotone fa-solid fa-arrow-up-left-from-circle fa-rotate-by text-[1.5rem]" style="--fa-rotate-angle: 135deg;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>