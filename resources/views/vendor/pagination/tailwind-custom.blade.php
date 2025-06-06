@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-end">
        <ul class="inline-flex items-center">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="px-3 py-1 text-gray-400">&laquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-purple text-gray-600 hover:text-purple-700" aria-label="@lang('pagination.previous')">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li aria-disabled="true"><span class="px-3 py-1">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span class="px-3 py-1 text-white bg-purple-600 border border-purple-600 rounded-md">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-purple text-gray-600 hover:text-purple-700">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-1 rounded-md focus:outline-none focus:shadow-outline-purple text-gray-600 hover:text-purple-700" aria-label="@lang('pagination.next')">&raquo;</a>
                </li>
            @else
                <li aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="px-3 py-1 text-gray-400">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif