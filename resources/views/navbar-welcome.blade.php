<nav class="bg-[#7368ce] text-white shadow">
    <div class="navbar-header max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo and name -->
            <div class="flex items-center">
                <div class="navbar-name-logo flex-shrink-0">
                    <h1 class="text-xl text-white font-bold text-gray-800">FlashCards</h1>
                </div>
                <a href="/" class="ml-3">
                    <img class="navbar-img-logo w-10 h-9" src="{{ asset('img/juego-de-cartas.png') }}" alt="Logo FlashCards">
                </a>
            </div>

            <!-- Desktop view -->
            <div class="hidden md:flex items-center space-x-4">
                <div class="flex items-center space-x-3">
                    <a class="navbar-boton-log bg-white text-[#7267cb] border border-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors" 
                       data-method="get" 
                       href="{{ route('register') }}">
                       {{ __('Create Account') }}
                    </a>
                    <a class="navbar-boton-log bg-white text-[#7267cb] border border-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors" 
                       data-method="get" 
                       href="{{ route('login') }}">
                       {{ __('Log In') }}
                    </a>
                </div>
            </div>

            <!-- Toggle Button to show mobile menu -->
            <div class="md:hidden">
                <button type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        onclick="toggleMobileMenu()">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a class="block w-full text-center bg-white text-[#7267cb] border border-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors" 
                   data-method="get" 
                   href="{{ route('register') }}">
                   {{ __('Create Account') }}
                </a>
                <a class="block w-full text-center bg-white text-[#7267cb] border border-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors" 
                   data-method="get" 
                   href="{{ route('login') }}">
                   {{ __('Log In') }}
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    }
</script>