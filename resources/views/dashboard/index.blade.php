<!DOCTYPE html>
<html :class="{ 'theme-dark': dark }" x-data="data()" lang="HU">

<head>
  <title>Főmenü</title>
  <script>
    window.deviceChartData = @json($eszkozok);
    window.napilogin = @json($napilogin);
  </script> 
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
      <main class="h-full overflow-y-auto">
        <div class="container px-6 mx-auto grid">
          <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            Dashboard
          </h2>
          <!-- Cards -->
          <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Card -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                  <path fill-rule="evenodd" d="M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4Zm12 12V5H7v11h10Zm-5 1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Z" clip-rule="evenodd"/>
                </svg>                
              </div>
              <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                  Összes eddigi eszközök
                </p>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                  {{$eszkozok_szamok}}
                </p>
              </div>
            </div>
            <!-- Card -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.5A2.493 2.493 0 0 1 7.51 20H7.5a2.468 2.468 0 0 1-2.4-3.154 2.98 2.98 0 0 1-.85-5.274 2.468 2.468 0 0 1 .92-3.182 2.477 2.477 0 0 1 1.876-3.344 2.5 2.5 0 0 1 3.41-1.856A2.5 2.5 0 0 1 12 5.5m0 13v-13m0 13a2.493 2.493 0 0 0 4.49 1.5h.01a2.468 2.468 0 0 0 2.403-3.154 2.98 2.98 0 0 0 .847-5.274 2.468 2.468 0 0 0-.921-3.182 2.477 2.477 0 0 0-1.875-3.344A2.5 2.5 0 0 0 14.5 3 2.5 2.5 0 0 0 12 5.5m-8 5a2.5 2.5 0 0 1 3.48-2.3m-.28 8.551a3 3 0 0 1-2.953-5.185M20 10.5a2.5 2.5 0 0 0-3.481-2.3m.28 8.551a3 3 0 0 0 2.954-5.185"/>
                </svg>                
              </div>
              <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                  Létrehozott Quiz
                </p>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                  Ide majd számot
                </p>
              </div>
            </div>
            <!-- Card -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7h1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h11.5M7 14h6m-6 3h6m0-10h.5m-.5 3h.5M7 7h3v3H7V7Z"/>
                </svg>                
              </div>
              <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                  HÍrek száma
                </p>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                  {{$hir_Szamok}}
                </p>
              </div>
            </div>
            <!-- Card -->
            <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <div class="p-3 mr-4 text-teal-500 bg-teal-100 rounded-full dark:text-teal-100 dark:bg-teal-500">
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h8m-4-7V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                </svg>                
              </div>
              <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                  Naptár postok
                </p>
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                  {{$naptar_szamok}}
                </p>
              </div>
            </div>
          </div>



          
<div class="w-full overflow-hidden rounded-lg shadow-xs">
  <div class="w-full overflow-x-auto">
    <table class="w-full whitespace-no-wrap">
      <thead>
        <tr
          class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
          <th class="px-4 py-3">Felhasználók</th>
          <th class="px-4 py-3">Szak</th>
          <th class="px-4 py-3">Titulus</th>
        </tr>
      </thead>
          <!-- New Table -->
          @foreach ($users as $user)
          <x-user-card :user="$user" />
          @endforeach


        </table>
      </div>
    </div>

          <!-- Charts -->
          <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
            Diagramok
          </h2>
          <div class="grid gap-6 mb-8 md:grid-cols-2">
            <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
                Összes Eszköz Márkái
              </h4>


              <canvas id="pie"></canvas>

              <div class="flex justify-center mt-4 space-x-3 text-sm text-gray-600 dark:text-gray-400">
                <!-- Chart legend -->
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #0694a2"></span>
                  <span>Samsung</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #7e3af2"></span>
                  <span>iPhone</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #1c64f2"></span>
                  <span>Redmi</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #10b981"></span>
                  <span>Huawei</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #f59e0b"></span>
                  <span>OnePlus</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #f43f5e"></span>
                  <span>Nokia</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 rounded-full" style="background-color: #6366f1"></span>
                  <span>Többi</span>
                </div>

              </div>
            </div>


            <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
              <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
                Forgalom (Nem működik még sadge)
              </h4>
              <canvas id="line"></canvas>
              <div class="flex justify-center mt-4 space-x-3 text-sm text-gray-600 dark:text-gray-400">
                <!-- Chart legend -->
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 bg-teal-600 rounded-full"></span>
                  <span>nemtudom</span>
                </div>
                <div class="flex items-center">
                  <span class="inline-block w-3 h-3 mr-1 bg-purple-600 rounded-full"></span>
                  <span>nemtudom</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>

</html>