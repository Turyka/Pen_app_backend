<aside class="z-20 hidden w-64 overflow-y-auto bg-white dark:bg-gray-800 md:block flex-shrink-0">
    <div class="py-4 text-gray-500 dark:text-gray-400">
      <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="#">
        PEN APP DASHBOARD
      </a>
      <ul class="mt-6">
        <li class="relative px-6 py-3">
          <span class="{{ Route::is('dashboard') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : 'bsolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}" aria-hidden="true"></span>
          <a class=
            "{{ Route::is('dashboard') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100' :
            'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}" href="{{ route('dashboard') }}">
            <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
              <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span class="ml-4">Főmenü</span>
          </a>
        </li>
      </ul>
      <ul>
        <li class="relative px-6 py-3">
          <span class="{{ Route::is('naptar') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : 'bsolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}" aria-hidden="true"></span>
          <a class=
            "{{ Route::is('naptar') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100' :
            'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}" href="{{ route('naptar') }}">
           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
</svg>
            <span class="ml-4">Naptár tábla</span>
          </a>
        </li>

        <li class="relative px-6 py-3">
          <span class="{{ Route::is('kozlemeny') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : 'bsolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}" aria-hidden="true"></span>
          <a class=
            "{{ Route::is('kozlemeny') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100' :
            'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}" href="{{ route('kozlemeny') }}">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-megaphone" viewBox="0 0 16 16">
  <path d="M13 2.5a1.5 1.5 0 0 1 3 0v11a1.5 1.5 0 0 1-3 0v-.214c-2.162-1.241-4.49-1.843-6.912-2.083l.405 2.712A1 1 0 0 1 5.51 15.1h-.548a1 1 0 0 1-.916-.599l-1.85-3.49-.202-.003A2.014 2.014 0 0 1 0 9V7a2.02 2.02 0 0 1 1.992-2.013 75 75 0 0 0 2.483-.075c3.043-.154 6.148-.849 8.525-2.199zm1 0v11a.5.5 0 0 0 1 0v-11a.5.5 0 0 0-1 0m-1 1.35c-2.344 1.205-5.209 1.842-8 2.033v4.233q.27.015.537.036c2.568.189 5.093.744 7.463 1.993zm-9 6.215v-4.13a95 95 0 0 1-1.992.052A1.02 1.02 0 0 0 1 7v2c0 .55.448 1.002 1.006 1.009A61 61 0 0 1 4 10.065m-.657.975 1.609 3.037.01.024h.548l-.002-.014-.443-2.966a68 68 0 0 0-1.722-.082z"/>
</svg>
            <span class="ml-4">Közlemény tábla</span>
          </a>
        </li>

        <li class="relative px-6 py-3">
          <span class="{{ Route::is('kepfeltoltes') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : 'bsolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}" aria-hidden="true"></span>
          <a class=
            "{{ Route::is('kepfeltoltes') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100' :
            'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}" href="{{ route('kepfeltoltes') }}">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-megaphone" viewBox="0 0 16 16">
  <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.998 2M14 2H4a1 1 0 0 0-1 1h9.002a2 2 0 0 1 2 2v7A1 1 0 0 0 15 11V3a1 1 0 0 0-1-1M2.002 4a1 1 0 0 0-1 1v8l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71a.5.5 0 0 1 .577-.094l1.777 1.947V5a1 1 0 0 0-1-1z"/>
</svg>
            <span class="ml-4">Esemény tábla</span>
          </a>
        </li>

      </ul>
      <div class="px-6 my-6">
        <button
        onclick="window.location='{{ route('keszit_naptar') }}'"
        type="button"
        class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
      📅 Naptár Poszt készítés
      <span class="ml-2" aria-hidden="true">></span>
    </button>
      </div>

      <div class="px-6 my-6">
        <button
        onclick="window.location='{{ route('keszit_kozlemeny') }}'"
        type="button"
        class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
      📣 Közlemény Poszt készítés
      <span class="ml-2" aria-hidden="true">></span>
    </button>
      </div>

      <div class="px-6 my-6">
        <button
        onclick="window.location='{{ route('kepfeltoltes.create') }}'"
        type="button"
        class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple">
        📸 Esemény Fénykép felöltés
      <span class="ml-2" aria-hidden="true">></span>
    </button>
      </div>
      

      {{-- ADMIN / ELNÖK SECTION --}}
@auth
  @if (Auth::user()->titulus === 'Admin' || Auth::user()->titulus === 'Elnök')
    <hr class="my-4 border-gray-300 dark:border-gray-700">

    <p class="px-6 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
      👑 Admin / Elnök menü
    </p>

    <ul class="mt-2">
      <li class="relative px-6 py-3">
        <span class="{{ Route::is('users.index') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : '' }}" aria-hidden="true"></span>
        <a class="{{ Route::is('users.index') 
          ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 dark:text-gray-100' 
          : 'inline-flex items-center w-full text-sm font-semibold text-gray-500 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}"
          href="{{ route('users.index') }}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
            <path d="M13 7c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2M9 8.5a3.5 3.5 0 1 0-7 0A3.5 3.5 0 0 0 9 8.5m3 3c-1.1 0-2.1.4-2.83 1.17C8.4 13.1 8 14.1 8 15h7c0-.9-.4-1.9-1.17-2.83C13.1 11.4 12.1 11 11 11z"/>
          </svg>
          <span class="ml-4">Felhasználók kezelése</span>
        </a>
      </li>

      <li class="relative px-6 py-3">
        <span class="{{ Route::is('users.create') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg' : '' }}" aria-hidden="true"></span>
        <a class="{{ Route::is('users.create') 
          ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 dark:text-gray-100' 
          : 'inline-flex items-center w-full text-sm font-semibold text-gray-500 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}"
          href="{{ route('users.create') }}">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16">
            <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-2 5c0-.68.275-1.318.764-1.803A2.7 2.7 0 0 1 6 10c.68 0 1.318.275 1.803.764A2.7 2.7 0 0 1 8.5 13H4z"/>
            <path fill-rule="evenodd" d="M11 5.5a.5.5 0 0 1 .5-.5H13V3.5a.5.5 0 0 1 1 0V5h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V6h-1.5a.5.5 0 0 1-.5-.5"/>
          </svg>
          <span class="ml-4">Új felhasználó hozzáadása</span>
        </a>
      </li>
    </ul>
  @endif
@endauth
    </div>
  </aside>
