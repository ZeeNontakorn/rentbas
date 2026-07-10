@props([
    'dirty' => false,
    'savedLabel' => 'บันทึกแล้ว',
    'dirtyLabel' => 'ยังไม่บันทึก',
    'savedClass' => 'border-green-200 bg-green-50 text-green-700',
    'dirtyClass' => 'border-yellow-200 bg-yellow-50 text-yellow-700',
])

<span {{ $attributes->merge([
    'class' => 'js-save-status inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ' . ($dirty ? $dirtyClass : $savedClass),
    'data-saved-label' => $savedLabel,
    'data-dirty-label' => $dirtyLabel,
    'data-saved-class' => $savedClass,
    'data-dirty-class' => $dirtyClass,
]) }}>
    {{ $dirty ? $dirtyLabel : $savedLabel }}
</span>
