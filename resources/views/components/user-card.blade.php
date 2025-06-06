@props(['user'])

        <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
          <tr class="text-gray-700 dark:text-gray-400">
            <td class="px-4 py-3">
              <div class="flex items-center text-sm">
                <!-- Avatar with inset shadow -->
                <div>
                  <p class="font-semibold">{{ $user->teljes_nev }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-sm">
                {{ $user->szak }}
            </td>
            <td class="px-4 py-3 text-xs">
                {{ $user->titulus }}
            </td>
          </tr>
        </tbody>
