<div class="py-4 text-gray-500 dark:text-gray-400">
    <a class="ml-6 text-lg font-bold text-gray-800 dark:text-gray-200" href="#">
        PEN APP DASHBOARD
    </a>
    <ul class="mt-6">
      <li class="relative px-6 py-3">
        <span class="{{ Route::is('dashboard') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg'
         : 'absolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}"
          aria-hidden="true"></span>
        <a class="{{ Route::is('dashboard') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100'
         : 'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}"href="{{ route('dashboard') }}">
          <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round"
            stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
            </path>
          </svg>
          <span class="ml-4">Dashboard</span>
        </a>
      </li>
    </ul>
    <ul>
      <li class="relative px-6 py-3">
        <span class="{{ Route::is('naptar') ? 'absolute inset-y-0 left-0 w-1 bg-purple-600 rounded-tr-lg rounded-br-lg'
         : 'absolute inset-y-0 left-0 w-1 rounded-tr-lg rounded-br-lg' }}"
          aria-hidden="true"></span>
        <a class="{{ Route::is('naptar') ? 'inline-flex items-center w-full text-sm font-semibold text-gray-800 transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100'
         : 'inline-flex items-center w-full text-sm font-semibold transition-colors duration-150 hover:text-gray-800 dark:hover:text-gray-200' }}"href="{{ route('naptar') }}">
          <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round"
            stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
          </svg>
          <span class="ml-4">Naptár tábla</span>
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
  </div>