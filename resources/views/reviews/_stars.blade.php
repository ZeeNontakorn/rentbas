{{--
    แสดงดาวแบบอ่านอย่างเดียว
    ใช้: @include('reviews._stars', ['score' => 4.3])
         @include('reviews._stars', ['score' => 4.3, 'size' => 'h-4 w-4', 'showValue' => true])
--}}
@php
    $size = $size ?? 'h-5 w-5';
    $showValue = $showValue ?? false;
    $filled = (int) round($score);
@endphp

<span class="inline-flex items-center gap-0.5 align-middle">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} {{ $i <= $filled ? 'text-yellow-400' : 'text-gray-300' }}"
             fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.362-1.118l-3.977-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
    @endfor

    @if ($showValue)
        <span class="ml-1 text-sm font-semibold text-gray-700">{{ number_format((float) $score, 1) }}</span>
    @endif
</span>
