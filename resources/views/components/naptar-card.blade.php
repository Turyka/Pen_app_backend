@props(['naptar'])

<tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
    <tr class="text-gray-700 dark:text-gray-400">
      <td class="px-4 py-3">
        <div class="flex items-center text-sm">
          <!-- Avatar with inset shadow -->
          <div>
            <p class="font-semibold">{{ $naptar->title }}</p>
          </div>
        </div>
      </td>
      <td class="px-4 py-3 text-xs">
        @if ( $naptar->status == "Aktív")
        <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
            {{ $naptar->status }}
        </span>
        @elseif($naptar->status == "Függőben")
        <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:text-white dark:bg-orange-600">
            Függőben
        </span>
        @elseif($naptar->status == "Törölve")
        <span class="px-2 py-1 font-semibold leading-tight text-red-700 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-700">
            Törölve
          </span>
          @elseif($naptar->status == "Lezárt")
          <span class="px-2 py-1 font-semibold leading-tight text-gray-700 bg-gray-100 rounded-full dark:text-gray-100 dark:bg-gray-700">
            Lezárt
          </span>
        @endif
        
      </td>
      <td class="px-4 py-3 text-sm">
        {{ $naptar->date }}
      </td>
      <td class="px-4 py-3 text-sm">
        {{ $naptar->created }}
        </td>
        <td class="px-4 py-3 text-sm">
            {{ $naptar->edited }}
        </td>
      <td class="px-4 py-3">
        <div class="flex items-center space-x-4 text-sm">
          <button onclick="window.location.href='{{ route('naptar.edit', $naptar) }}'" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray" aria-label="Edit">
            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
            </svg>
          </button>
            <form action="{{ route('naptar.destroy', $naptar->id) }}" method="POST"
              onsubmit="return confirm('Biztosan törölni szeretnéd ezt az eseményt?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-purple-600 rounded-lg dark:text-gray-400 focus:outline-none focus:shadow-outline-gray"
                    aria-label="Delete">
                <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                          clip-rule="evenodd">
                    </path>
                </svg>
            </button>
        </form>
        </div>
      </td>
    </tr>
  </tbody>