@php $nbNotifications = Auth::user()->voitNotifications() ? Auth::user()->notificationsApp()->nonVues()->count() : 0; @endphp
<style>
    /* Menu mobile : texte et icônes à la couleur de texte du thème (blanc ou sombre selon la teinte). */
    .nm-bouton { color: var(--tblr-primary-fg) !important; background: none; border: 0; }
    .nm-bouton:hover, .nm-bouton:focus-visible { background: color-mix(in srgb, var(--tblr-primary-fg) 16%, transparent); }
    .nm-panneau { border-top: 1px solid color-mix(in srgb, var(--tblr-primary-fg) 28%, transparent); padding-bottom: .5rem; }
    .nm-lien { display: flex; align-items: center; gap: .8rem; width: 100%; padding: .7rem 1rem; border: 0; border-left: 4px solid transparent; background: none;
               color: var(--tblr-primary-fg) !important; font-size: 1rem; font-weight: 500; text-align: left; text-decoration: none; }
    .nm-lien:hover, .nm-lien:focus-visible { background: color-mix(in srgb, var(--tblr-primary-fg) 12%, transparent); }
    .nm-lien.on { background: color-mix(in srgb, var(--tblr-primary-fg) 18%, transparent); border-left-color: var(--tblr-primary-fg); }
    .nm-lien svg { flex: none; opacity: .95; }
    .nm-compte { margin-top: .4rem; padding: .8rem 1rem .5rem 1.25rem; border-top: 1px solid color-mix(in srgb, var(--tblr-primary-fg) 28%, transparent); }
    .nm-nom { font-weight: 600; color: var(--tblr-primary-fg); }
    .nm-mail { font-size: .85rem; color: var(--tblr-primary-fg); opacity: .8; overflow-wrap: anywhere; }
</style>
<nav x-data="{ open: false }" class="shadow bg-primary text-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" style="width: 115px; height: 115px; margin-right: -25px;">
                        <x-application-logo class="block w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg  xmlns="http://www.w3.org/2000/svg"  class="icon me-1" width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-home"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                            <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                            <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                        </svg>
                        {{ __('Tableau de bord') }}
                    </x-nav-link>
                </div>

                @if(auth()->user()->accedeProjetsTaches())
                <div class="hidden sm:-my-px sm:ms-10 sm:flex items-center">
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle {{ request()->routeIs('projets.*', 'taches.*') ? 'active' : '' }}" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" /></svg>
                            Projets &amp; tâches
                        </a>
                        <div class="dropdown-menu">
                            <a href="{{ route('projets.index') }}" class="dropdown-item {{ request()->routeIs('projets.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" /></svg>
                                Projets
                            </a>
                            <a href="{{ route('taches.index') }}" class="dropdown-item {{ request()->routeIs('taches.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5" /><path d="M3.5 11.5l1.5 1.5l2.5 -2.5" /><path d="M3.5 17.5l1.5 1.5l2.5 -2.5" /><path d="M11 6l9 0" /><path d="M11 12l9 0" /><path d="M11 18l9 0" /></svg>
                                Tâches
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->is_admin)
                <div class="hidden sm:-my-px sm:ms-10 sm:flex items-center">
                    <div class="dropdown">  
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <svg  xmlns="http://www.w3.org/2000/svg"  class="icon me-1" width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-timeline">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M4 16l6 -7l5 5l5 -6" />
                                <path d="M15 14m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M10 9m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M4 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M20 8m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                            </svg>
                            Planning
                        </a>
                        <div class="dropdown-menu">
                            <a href="{{ route('evenements.index') }}" class="dropdown-item {{ request()->routeIs('evenements.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-week">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                    <path d="M16 3v4" />
                                    <path d="M8 3v4" />
                                    <path d="M4 11h16" />
                                    <path d="M7 14h.013" />
                                    <path d="M10.01 14h.005" />
                                    <path d="M13.01 14h.005" />
                                    <path d="M16.015 14h.005" />
                                    <path d="M13.015 17h.005" />
                                    <path d="M7.01 17h.005" />
                                    <path d="M10.01 17h.005" />
                                </svg>
                                Événements
                            </a>

                            <a href="{{ route('changements_horaires.index') }}" class="dropdown-item {{ request()->routeIs('changements-horaires.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clock">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                                Changements d'horaires
                            </a>

                            <a href="{{ route('conges.index') }}" class="dropdown-item {{ request()->routeIs('conges.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-beach">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M17.553 16.75a7.5 7.5 0 0 0 -10.606 0" />
                                    <path d="M18 3.804a6 6 0 0 0 -8.196 2.196l10.392 6a6 6 0 0 0 -2.196 -8.196z" />
                                    <path d="M16.732 10c1.658 -2.87 2.225 -5.644 1.268 -6.196c-.957 -.552 -3.075 1.326 -4.732 4.196" />
                                    <path d="M15 9l-3 5.196" />
                                    <path d="M3 19.25a2.4 2.4 0 0 1 1 -.25a2.4 2.4 0 0 1 2 1a2.4 2.4 0 0 0 2 1a2.4 2.4 0 0 0 2 -1a2.4 2.4 0 0 1 2 -1a2.4 2.4 0 0 1 2 1a2.4 2.4 0 0 0 2 1a2.4 2.4 0 0 0 2 -1a2.4 2.4 0 0 1 2 -1a2.4 2.4 0 0 1 1 .25" />
                                </svg>
                                Congés
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:-my-px sm:ms-10 sm:flex items-center">
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                            <svg  xmlns="http://www.w3.org/2000/svg"  class="icon me-1" width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-database"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
                                <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
                                <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
                            </svg>
                            Données
                        </a>
                        <div class="dropdown-menu">
                            <a href="{{ route('users.index') }}" class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                </svg>
                                Utilisateurs
                            </a>

                            <a href="{{ route('salles.index') }}" class="dropdown-item {{ request()->routeIs('salles.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-door">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 12v.01" />
                                    <path d="M3 21h18" />
                                    <path d="M6 21v-16a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v16" />
                                </svg>
                                Salles
                            </a>

                            <a href="{{ route('materiels.index') }}" class="dropdown-item {{ request()->routeIs('materiels.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-laptop">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 19l18 0" />
                                    <path d="M5 6m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" />
                                </svg>
                                Matériels
                            </a>

                            <a href="{{ route('objets.index') }}" class="dropdown-item {{ request()->routeIs('objets.*') ? 'active' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cube">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M21 16.008v-8.018a1.98 1.98 0 0 0 -1 -1.717l-7 -4.008a2.016 2.016 0 0 0 -2 0l-7 4.008c-.619 .355 -1 1.01 -1 1.718v8.018c0 .709 .381 1.363 1 1.717l7 4.008a2.016 2.016 0 0 0 2 0l7 -4.008c.619 -.355 1 -1.01 1 -1.718z" />
                                    <path d="M12 22v-10" />
                                    <path d="M12 12l8.73 -5.04" />
                                    <path d="M3.27 6.96l8.73 5.04" />
                                </svg>
                                Objets 3D
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('export')" :active="request()->routeIs('export')">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-table-export">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12.5 21h-7.5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v7.5" />
                            <path d="M3 10h18" />
                            <path d="M10 3v18" />
                            <path d="M16 19h6" />
                            <path d="M19 16l3 3l-3 3" />
                        </svg>
                        {{ __('Exporter') }}
                    </x-nav-link>
                </div>
                @endif
                
                <div class="hidden sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('presence')" :active="request()->routeIs('presence')" target="_blank" rel="noopener noreferrer">
                        {{ __('Floppy Là') }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-external-link" style="padding-left:5px;">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                            <path d="M11 13l9 -9" />
                            <path d="M15 4h5v5" />
                        </svg>
                    </x-nav-link>
                </div>
            </div> 


            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                {{ __('Se déconnecter') }}
                            </x-dropdown-link>
                        </form>

                    </x-slot>
                </x-dropdown>

                @if(Auth::user()->voitNotifications())
                    {{-- Cloche : ouvre le tiroir de notifications (à droite du bouton de profil) --}}
                    <button type="button" style="position: relative;" class="js-nav-bell inline-flex items-center justify-center ms-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications" aria-label="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                        <span class="js-notif-badge {{ $nbNotifications ? '' : 'd-none' }}">{{ $nbNotifications > 9 ? '9+' : $nbNotifications }}</span>
                    </button>
                @endif
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                @if(Auth::user()->voitNotifications())
                    <button type="button" style="position: relative;" class="nm-bouton inline-flex items-center justify-center p-2 me-1 rounded-md focus:outline-none"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotifications" aria-controls="offcanvasNotifications" aria-label="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                        <span class="js-notif-badge {{ $nbNotifications ? '' : 'd-none' }}">{{ $nbNotifications > 9 ? '9+' : $nbNotifications }}</span>
                    </button>
                @endif
                <button @click="open = ! open" class="nm-bouton inline-flex items-center justify-center p-2 rounded-md focus:outline-none" aria-label="Menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu mobile : accès de consultation uniquement (les écrans de saisie ne sont pas adaptés au téléphone) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden nm-panneau">
        <a href="{{ route('dashboard') }}" class="nm-lien {{ request()->routeIs('dashboard') ? 'on' : '' }}"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg> Tableau de bord</a>

        @if(auth()->user()->accedeProjetsTaches())
            <a href="{{ route('projets.index') }}" class="nm-lien {{ request()->routeIs('projets.*') ? 'on' : '' }}"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg> Projets</a>
            <a href="{{ route('taches.index') }}" class="nm-lien {{ request()->routeIs('taches.*') ? 'on' : '' }}"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 5.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 11.5l1.5 1.5l2.5 -2.5"/><path d="M3.5 17.5l1.5 1.5l2.5 -2.5"/><path d="M11 6l9 0"/><path d="M11 12l9 0"/><path d="M11 18l9 0"/></svg> Tâches</a>
        @endif

        <a href="{{ route('presence') }}" class="nm-lien {{ request()->routeIs('presence') ? 'on' : '' }}" target="_blank" rel="noopener noreferrer"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6"/><path d="M11 13l9 -9"/><path d="M15 4h5v5"/></svg> Floppy Là</a>

        <div class="nm-compte">
            <div class="nm-nom">{{ Auth::user()->name }}</div>
            <div class="nm-mail">{{ Auth::user()->email }}</div>
        </div>

        <a href="{{ route('profile.edit') }}" class="nm-lien {{ request()->routeIs('profile.*') ? 'on' : '' }}"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg> Profil</a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nm-lien nm-bouton-lien"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/></svg> Se déconnecter</button>
        </form>
    </div>
</nav>
