<div class="content-img">
    <!-- Primera imagen (normal) -->
    <img src="{{ asset('img/dnc-login.png') }}" alt="Login Logo" class="h-auto mx-auto w-[75%] md:w-[100%]">
    
    <!-- Segunda imagen (absoluta con condiciones responsive) -->
    <img 
        src="{{ asset('img/dnc-perso.png') }}" 
        alt="Personaje" 
        class="
            absolute md:left-[12%] xl:left-[23%] xxl:left-[25%] bottom-0 h-auto
            max-[767px]:hidden
            md:w-[35%]
            md:block xl:w-[26%] xxl:w-[25%]
        "
    >
</div>