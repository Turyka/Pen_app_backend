<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="HU">

<head>
  <title>Képfeltöltés</title>
  @include('dashboard.Parts._head')
</head>

<body>
  <div class="flex h-screen bg-gray-50 dark:bg-gray-900" :class="{ 'overflow-hidden': isSideMenuOpen }">
    
    @include('dashboard.Parts._slide')
    <!-- Mobile sidebar -->
    <!-- Backdrop -->
    <div x-show="isSideMenuOpen" x-transition:enter="transition ease-in-out duration-150"
      x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="fixed inset-0 z-10 flex items-end bg-black bg-opacity-50 sm:items-center sm:justify-center"></div>
    <aside class="fixed inset-y-0 z-20 flex-shrink-0 w-64 mt-16 overflow-y-auto bg-white dark:bg-gray-800 md:hidden"
      x-show="isSideMenuOpen" x-transition:enter="transition ease-in-out duration-150"
      x-transition:enter-start="opacity-0 transform -translate-x-20" x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in-out duration-150" x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0 transform -translate-x-20" @click.away="closeSideMenu"
      @keydown.escape="closeSideMenu">

      @include('dashboard.Parts._slide_mobil')
    </aside>
    <div class="flex flex-col flex-1 w-full">

      @include('dashboard.Parts._header')
      

      <main class="h-full pb-16 overflow-y-auto">
        <div class="container grid px-6 mx-auto">
          <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            Közlemény Táblák
          </h2>


          <!-- With actions -->
          <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
              <table class="w-full whitespace-no-wrap">
                <thead>
                  <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                    <th class="px-4 py-3">Cím</th>
                    <th class="px-4 py-3">Elérés</th>
                    <th class="px-4 py-3">Művelet</th>
                  </tr>
                </thead>
          
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                  @foreach ($kepfeltoltesek as $kepfeltoltes)
                    <x-kepfeltoltes-card :kepfeltoltes="$kepfeltoltes" />
                  @endforeach
                </tbody>
              </table>
            </div>
          
            <!-- Pagination Footer -->
            <div class="grid px-4 py-3 text-xs font-semibold tracking-wide text-gray-500 uppercase border-t dark:border-gray-700 bg-gray-50 sm:grid-cols-9 dark:text-gray-400 dark:bg-gray-800">
              <span class="flex items-center col-span-3">
                Megjelenít {{ $kepfeltoltesek->firstItem() }} - {{ $kepfeltoltesek->lastItem() }} a {{ $kepfeltoltesek->total() }} -ből
              </span>
              <span class="col-span-2"></span>
          
              <!-- Laravel Pagination Links -->
              <span class="flex col-span-4 mt-2 sm:mt-auto sm:justify-end">
                {{ $kepfeltoltesek->links('vendor.pagination.tailwind-custom') }}
              </span>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>

</html>
