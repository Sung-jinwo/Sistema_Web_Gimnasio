<div>

    <!-- Mobile menu overlay -->
            <div x-show="mobileMenuOpen" 
                x-cloak
                @click="mobileMenuOpen = false"
                class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

            <!-- Sidebar -->
            <aside 
                :class="sidebarOpen ? 'w-64' : 'w-20'"
                class="bg-gray-900 text-white transition-all duration-300 ease-in-out border-r border-gray-800 shadow-lg fixed lg:static h-full z-50 lg:z-0 flex flex-col"
                x-show="mobileMenuOpen || window.innerWidth >= 1024"
                @click.away="if (window.innerWidth < 1024) mobileMenuOpen = false"
                x-cloak>
                
                <!-- Logo -->
                <div class="p-6 border-b border-gray-800 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div x-show="sidebarOpen" class="flex items-center gap-3">
                           <img src="{{ asset('icon/icongym.png') }}" alt="Logo" class="w-10 h-10 rounded-lg">
                                <div>
                                    <h1 class="text-lg font-bold text-white">IVONNE GYM</h1>
                                    <p class="text-xs text-gray-400">
                                        <i class="{{ $userRole['icon'] }}"></i> {{ $userRole['text'] }}
                                    </p>
                                </div>
                        </div>
                        <button 
                            @click="sidebarOpen = !sidebarOpen"
                            class="p-2 hover:bg-gray-800 rounded-lg transition-colors hidden lg:flex">
                            <i x-show="sidebarOpen" class="fas fa-times"></i>
                            <i x-show="!sidebarOpen" class="fas fa-bars"></i>
                        </button>
                        <button 
                            @click="mobileMenuOpen = false"
                            class="p-2 hover:bg-gray-800 rounded-lg transition-colors lg:hidden">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- User Section -->
                <div class="p-4 border-b border-gray-800 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-pink-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-pink-500 font-bold">{{ substr(auth()->user()->name ?? 'U', 0, 1) }}</span>
                        </div>
                        <div x-show="sidebarOpen" class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ auth()->user()->name ?? 'Nombre de usuario' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email ?? 'usuario@mail.com' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                        @foreach($menuItems as $item)
                            @if($canAccessMenuItem($item))
                                <div>
                                    @if(isset($item['submenu']))
                                        {{-- Item con submenú --}}
                                        <button 
                                            @click="if (sidebarOpen) { toggleSubmenu('{{ $item['route'] }}') } else { sidebarOpen = true; toggleSubmenu('{{ $item['route'] }}') }"
                                            class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-gray-300 hover:bg-gray-800 {{ $isActive($item['route']) }}">
                                            <i class="fas {{ $item['icon'] }} w-5 flex-shrink-0"></i>
                                            <span x-show="sidebarOpen" class="text-sm font-medium flex-1 text-left">{{ $item['label'] }}</span>
                                            <i x-show="sidebarOpen && isExpanded('{{ $item['route'] }}')" class="fas fa-chevron-down w-4 flex-shrink-0"></i>
                                            <i x-show="sidebarOpen && !isExpanded('{{ $item['route'] }}')" class="fas fa-chevron-right w-4 flex-shrink-0"></i>
                                        </button>

                                        {{-- Submenú --}}
                                        <div x-show="isExpanded('{{ $item['route'] }}') && sidebarOpen" 
                                            x-cloak
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 -translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            class="ml-4 mt-1 space-y-1 border-l border-gray-700 pl-4">
                                            @foreach($item['submenu'] as $subitem)
                                                @if($canAccessMenuItem($subitem))
                                                    <a href="{{ route($subitem['route']) }}"
                                                    class="block w-full text-left px-4 py-2 rounded-lg text-sm transition-all {{ request()->routeIs($subitem['route']) ? 'bg-pink-500/20 text-pink-500 font-medium' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                                                        <i class="fas {{ $subitem['icon'] }} mr-2"></i>
                                                        {{ $subitem['label'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Item simple --}}
                                        @php
                                            $href = isset($item['dynamic_route']) 
                                                ? $item['dynamic_route']() 
                                                : route($item['route'] . '.index');
                                        @endphp
                                        <a href="{{ $href }}"
                                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all {{ $isActive($item['route']) ? 'bg-pink-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                                            <i class="fas {{ $item['icon'] }} w-5 flex-shrink-0"></i>
                                            <span x-show="sidebarOpen" class="text-sm font-medium">{{ $item['label'] }}</span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </nav>


                <!-- Logout Button -->
                <div class="p-4 border-t border-gray-800 flex-shrink-0">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-pink-500/10 transition-colors">
                            <i class="fas fa-right-from-bracket w-5 flex-shrink-0"></i>
                            <span x-show="sidebarOpen" class="text-sm font-medium">Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </aside>

</div>