<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="HU">
<head>
  <title>Felhasználókezelés</title>
  @include('dashboard.Parts._head')
</head>

<body>
  <div class="flex h-screen bg-gray-50 dark:bg-gray-900" :class="{ 'overflow-hidden': isSideMenuOpen }">
    @include('dashboard.Parts._slide')

    <div x-show="isSideMenuOpen" x-transition:enter="transition ease-in-out duration-150"
      x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in-out duration-150"
      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
      class="fixed inset-0 z-10 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center"></div>

    <aside class="fixed inset-y-0 z-20 flex-shrink-0 w-64 mt-16 overflow-y-auto bg-white dark:bg-gray-800 md:hidden"
      x-show="isSideMenuOpen" x-transition:enter="transition ease-in-out duration-150"
      x-transition:enter-start="opacity-0 transform -translate-x-20" x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in-out duration-150"
      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 transform -translate-x-20"
      @click.away="closeSideMenu" @keydown.escape="closeSideMenu">

      @include('dashboard.Parts._slide_mobil')
    </aside>

    <div class="flex flex-col flex-1 w-full">
      @include('dashboard.Parts._header')

      <main class="h-full pb-16 overflow-y-auto">
        <div class="container grid px-6 mx-auto">
          <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            Felhasználók kezelése
          </h2>

          @if (session('success'))
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
              {{ session('success') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200">
              {{ $errors->first() }}
            </div>
          @endif

          <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
              <table class="w-full whitespace-no-wrap">
                <thead>
                  <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">Felhasználónév</th>
                    <th class="px-4 py-3">Teljes név</th>
                    <th class="px-4 py-3">Szak</th>
                    <th class="px-4 py-3">Titulus</th>
                    <th class="px-4 py-3">Művelet</th>
                  </tr>
                </thead>

                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                  @foreach ($users as $user)
                    <tr class="text-gray-700 dark:text-gray-400">
                      <td class="px-4 py-3">{{ $user->name }}</td>
                      <td class="px-4 py-3">{{ $user->teljes_nev }}</td>
                      <td class="px-4 py-3">{{ $user->szak }}</td>
                      <td class="px-4 py-3">{{ $user->titulus }}</td>

                      <td class="px-4 py-3">
                        <div class="flex items-center space-x-4 text-sm">
                          
                          <form action="{{ route('users.destroy', $user) }}" method="POST"
                            onsubmit="return confirm('Biztosan törlöd a felhasználót?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline"
                              @if(Auth::user()->titulus === 'Elnök' && $user->titulus === 'Admin') disabled title="Admin nem törölhető" @endif>
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
         viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 7h12M9 7V4h6v3m-7 4v6m4-6v6m5-10H4l1 14h14l1-14z"/>
    </svg>
                            </button>
                          </form>
                          <form action="{{ route('users.edit', $user) }}" method="GET">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:underline">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
         viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5h2M12 20h9M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z"/>
    </svg>
                            </button>
                          </form>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
              <span class="flex items-center col-span-3">
                Megjelenít {{ $users->firstItem() }} - {{ $users->lastItem() }} a {{ $users->total() }} felhasználóból
              </span>
              <span class="col-span-2"></span>
              <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                {{ $users->links('vendor.pagination.tailwind-custom') }}
              </span>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
