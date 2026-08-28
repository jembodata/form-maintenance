<x-filament-widgets::widget>
    <style>
        .pm-week-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .pm-week-filters {
            display: flex;
            flex: 1 1 36rem;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .pm-week-plant-filter {
            flex: 0 0 15rem;
            width: 15rem;
        }

        .pm-week-list {
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: .75rem;
        }

        .dark .pm-week-list {
            border-color: rgb(255 255 255 / 10%);
        }

        .pm-week-header,
        .pm-week-row {
            display: grid;
            grid-template-columns: minmax(14rem, 1.6fr) minmax(8rem, .8fr) 9rem 7.5rem 9.5rem;
            align-items: center;
            gap: 1rem;
        }

        .pm-week-header {
            padding: .7rem 1rem;
            background: rgb(249 250 251);
            color: rgb(107 114 128);
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .dark .pm-week-header {
            background: rgb(255 255 255 / 5%);
            color: rgb(156 163 175);
        }

        .pm-week-row {
            min-height: 4.75rem;
            padding: .85rem 1rem;
            border-top: 1px solid rgb(229 231 235);
        }

        .pm-week-row:first-of-type {
            border-top: 0;
        }

        .dark .pm-week-row {
            border-color: rgb(255 255 255 / 10%);
        }

        .pm-week-row:hover {
            background: rgb(249 250 251 / 70%);
        }

        .dark .pm-week-row:hover {
            background: rgb(255 255 255 / 3%);
        }

        .pm-week-machine,
        .pm-week-plant {
            min-width: 0;
        }

        .pm-week-meta {
            display: contents;
        }

        .pm-week-action {
            text-align: right;
        }

        @media (max-width: 767px) {
            .pm-week-filters {
                flex-basis: 100%;
            }

            .pm-week-plant-filter {
                flex-basis: 100%;
                width: 100%;
            }

            .pm-week-header {
                display: none;
            }

            .pm-week-row {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: .65rem 1rem;
                padding: 1rem;
            }

            .pm-week-machine {
                grid-column: 1;
                grid-row: 1;
            }

            .pm-week-status {
                grid-column: 2;
                grid-row: 1;
                align-self: start;
            }

            .pm-week-meta {
                display: flex;
                grid-column: 1 / -1;
                grid-row: 2;
                flex-wrap: wrap;
                gap: .5rem 1rem;
            }

            .pm-week-action {
                grid-column: 1 / -1;
                grid-row: 3;
                text-align: left;
            }

            .pm-week-action .fi-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            PM minggu ini
        </x-slot>

        <x-slot name="description">
            {{ $weekStart->translatedFormat('d M') }}–{{ $weekEnd->translatedFormat('d M Y') }}
        </x-slot>

        @if ($canViewSchedule)
            <x-slot name="headerEnd">
                <x-filament::button
                    tag="a"
                    :href="$scheduleUrl"
                    color="gray"
                    icon="heroicon-m-calendar-days"
                    size="sm"
                >
                    Buka jadwal
                </x-filament::button>
            </x-slot>
        @endif

        <div class="space-y-4">
            <div class="pm-week-toolbar">
                <div class="pm-week-filters" role="tablist" aria-label="Filter status PM">
                    <button
                        type="button"
                        wire:click="setFilter('pending')"
                        @class([
                            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-primary-50 text-primary-700 ring-1 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400' => $this->filter === 'pending',
                            'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5' => $this->filter !== 'pending',
                        ])
                    >
                        <x-filament::icon icon="heroicon-m-wrench-screwdriver" class="h-5 w-5" />
                        Perlu dikerjakan
                        <x-filament::badge size="sm">{{ $counts['pending'] }}</x-filament::badge>
                    </button>

                    <button
                        type="button"
                        wire:click="setFilter('overdue')"
                        @class([
                            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-danger-50 text-danger-700 ring-1 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400' => $this->filter === 'overdue',
                            'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5' => $this->filter !== 'overdue',
                        ])
                    >
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5" />
                        Terlambat
                        <x-filament::badge color="danger" size="sm">{{ $counts['overdue'] }}</x-filament::badge>
                    </button>

                    <button
                        type="button"
                        wire:click="setFilter('completed')"
                        @class([
                            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-success-50 text-success-700 ring-1 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400' => $this->filter === 'completed',
                            'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5' => $this->filter !== 'completed',
                        ])
                    >
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
                        Selesai
                        <x-filament::badge color="success" size="sm">{{ $counts['completed'] }}</x-filament::badge>
                    </button>
                </div>

                @if ($plants->isNotEmpty())
                    <div class="pm-week-plant-filter">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="plant" aria-label="Filter plant">
                                <option value="">Semua plant</option>
                                @foreach ($plants as $plantName)
                                    <option value="{{ $plantName }}">{{ $plantName }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>

            @if ($jobs->isEmpty())
                <div class="p-6 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-gray-700">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-10 w-10 text-gray-400" />
                    <p class="mt-3 text-sm font-semibold text-gray-950 dark:text-white">
                        Tidak ada PM pada filter ini
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Periksa jadwal atau pilih status yang lain.
                    </p>
                </div>
            @else
                <div class="pm-week-list">
                    <div class="pm-week-header" aria-hidden="true">
                        <span>Mesin</span>
                        <span>Plant</span>
                        <span>Rencana</span>
                        <span>Status</span>
                        <span style="text-align: right">Tindakan</span>
                    </div>

                    @foreach ($jobs as $job)
                        <div class="pm-week-row">
                            <div class="pm-week-machine">
                                <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $job['machine'] }}
                                </p>
                                @if (filled($job['note']))
                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $job['note'] }}">
                                        {{ $job['note'] }}
                                    </p>
                                @endif
                            </div>

                            <div class="pm-week-meta">
                                <div class="pm-week-plant flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-building-office-2" class="h-4 w-4 shrink-0 text-gray-400" />
                                    <span class="truncate">{{ $job['plant'] }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 shrink-0 text-gray-400" />
                                    <span class="font-medium">{{ $job['planned_date']->translatedFormat('D, d M') }}</span>
                                </div>
                            </div>

                            <div class="pm-week-status">
                                <x-filament::badge :color="$job['status_color']">
                                    {{ $job['status_label'] }}
                                </x-filament::badge>
                            </div>

                            <div class="pm-week-action">
                                @if ($job['can_open'])
                                    @if ($job['status'] === 'completed')
                                        <x-filament::button
                                            tag="a"
                                            :href="$job['action_url']"
                                            color="gray"
                                            icon="heroicon-m-eye"
                                            size="sm"
                                        >
                                            Lihat hasil
                                        </x-filament::button>
                                    @else
                                        <x-filament::button
                                            type="button"
                                            wire:click="openChecklist({{ $job['id'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openChecklist({{ $job['id'] }})"
                                            color="primary"
                                            icon="heroicon-m-clipboard-document-check"
                                            size="sm"
                                        >
                                            Checklist
                                        </x-filament::button>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Tidak ada akses
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($totalJobs > $jobs->count())
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                        Menampilkan 8 dari {{ $totalJobs }} data. Buka jadwal untuk melihat semua data.
                    </p>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
