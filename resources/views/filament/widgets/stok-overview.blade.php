<x-filament-widgets::widget>
    <x-filament::section heading="Ringkasan Stok">
        {{-- SM: horizontal grid + snap; MD/XL: grid 5 kolom rapih --}}
        <div
            class="
                grid grid-flow-col auto-cols-[minmax(200px,1fr)]
                gap-4 overflow-x-auto snap-x snap-mandatory
                px-1
                md:grid-flow-row md:grid-cols-5 md:auto-cols-auto
            "
        >
            {{-- Kosong --}}
            <x-filament::card class="snap-start">
                <div class="text-sm text-gray-500">Kosong (Stok = 0)</div>
                <div class="text-2xl font-bold" style="color: #dc2626;">{{ $kosong }}</div>
                <div class="text-xs text-gray-400">
                    {{ $total > 0 ? number_format($kosong / $total * 100, 1) : 0 }}% dari total
                </div>
            </x-filament::card>

            {{-- Hampir Habis --}}
            <x-filament::card class="snap-start">
                <div class="text-sm text-gray-500">Hampir Habis (≤ 5)</div>
                <div class="text-2xl font-bold" style="color: #ca8a04;">{{ $hampir }}</div>
                <div class="text-xs text-gray-400">
                    {{ $total > 0 ? number_format($hampir / $total * 100, 1) : 0 }}% dari total
                </div>
            </x-filament::card>

            {{-- Menipis --}}
            <x-filament::card class="snap-start">
                <div class="text-sm text-gray-500">Menipis (≤ 20)</div>
                <div class="text-2xl font-bold" style="color: #ea580c;">{{ $menipis }}</div>
                <div class="text-xs text-gray-400">
                    {{ $total > 0 ? number_format($menipis / $total * 100, 1) : 0 }}% dari total
                </div>
            </x-filament::card>

            {{-- Aman --}}
            <x-filament::card class="snap-start">
                <div class="text-sm text-gray-500">Aman (> 20)</div>
                <div class="text-2xl font-bold" style="color: #16a34a;">{{ $aman }}</div>
                <div class="text-xs text-gray-400">
                    {{ $total > 0 ? number_format($aman / $total * 100, 1) : 0 }}% dari total
                </div>
            </x-filament::card>

            {{-- Total --}}
            <x-filament::card class="snap-start">
                <div class="text-sm text-gray-500">Total Seluruh Item</div>
                <div class="text-2xl font-bold text-gray-800">{{ $total }}</div>
                <div class="text-xs text-gray-400">100% dari data</div>
            </x-filament::card>
        </div>

        {{-- Kalau tetap mau total full-width di bawah pada layar besar, taruh ini (opsional) --}}
        {{-- <x-filament::card class="mt-4 hidden md:block">
            <div class="text-sm text-gray-500">Total Seluruh Item</div>
            <div class="text-3xl font-bold text-gray-800">{{ $total }}</div>
        </x-filament::card> --}}
    </x-filament::section>
</x-filament-widgets::widget>
