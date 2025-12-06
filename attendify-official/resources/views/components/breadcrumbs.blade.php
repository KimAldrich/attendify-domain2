@props(['items' => []])

<nav class="flex items-center text-sm text-slate-500 mb-2" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1">
        @foreach ($items as $item)
            <li class="inline-flex items-center">
                @if (!$loop->first)
                    <span class="mx-1 text-slate-400">/</span>
                @endif

                @if(isset($item['url']))
                    <a href="{{ $item['url'] }}"
                       class="text-slate-600 hover:text-[#0052CC] font-medium">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-slate-400">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
