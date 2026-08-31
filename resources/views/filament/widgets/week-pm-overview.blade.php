<x-filament-widgets::widget>
    <style>
        .pm-scheduler {
            display: grid;
            gap: .65rem;
        }

        .pm-scheduler-toolbar,
        .pm-scheduler-nav,
        .pm-scheduler-view-switcher,
        .pm-scheduler-filters,
        .pm-scheduler-statuses,
        .pm-scheduler-footer,
        .pm-scheduler-pagination {
            display: flex;
            align-items: center;
        }

        .pm-scheduler-toolbar,
        .pm-scheduler-filters,
        .pm-scheduler-footer {
            justify-content: space-between;
            gap: .75rem;
        }

        .pm-scheduler-nav,
        .pm-scheduler-statuses,
        .pm-scheduler-pagination {
            gap: .4rem;
        }

        .pm-scheduler-date {
            width: 10.25rem;
        }

        .pm-scheduler-plant {
            width: 12rem;
        }

        .pm-scheduler-view-switcher {
            overflow: hidden;
            padding: .2rem;
            border: 1px solid rgb(229 231 235);
            border-radius: .6rem;
            background: rgb(249 250 251);
        }

        .dark .pm-scheduler-view-switcher {
            border-color: rgb(255 255 255 / 10%);
            background: rgb(255 255 255 / 5%);
        }

        .pm-scheduler-view-button {
            padding: .35rem .65rem;
            border-radius: .42rem;
            color: rgb(107 114 128);
            font-size: .72rem;
            font-weight: 600;
            line-height: 1rem;
            transition: background-color .15s, color .15s, box-shadow .15s;
        }

        .pm-scheduler-view-button:hover {
            color: rgb(17 24 39);
        }

        .pm-scheduler-view-button.is-active {
            background: white;
            color: rgb(17 24 39);
            box-shadow: 0 1px 2px rgb(0 0 0 / 8%);
        }

        .dark .pm-scheduler-view-button {
            color: rgb(156 163 175);
        }

        .dark .pm-scheduler-view-button:hover,
        .dark .pm-scheduler-view-button.is-active {
            color: white;
        }

        .dark .pm-scheduler-view-button.is-active {
            background: rgb(255 255 255 / 10%);
        }

        .pm-scheduler-statuses {
            flex-wrap: wrap;
        }

        .pm-scheduler-status-button {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .34rem .55rem;
            border-radius: .5rem;
            color: rgb(75 85 99);
            font-size: .72rem;
            font-weight: 600;
            transition: background-color .15s, color .15s;
        }

        .pm-scheduler-status-button:hover {
            background: rgb(249 250 251);
        }

        .pm-scheduler-status-button.is-active {
            background: rgb(239 246 255);
            color: rgb(29 78 216);
        }

        .dark .pm-scheduler-status-button {
            color: rgb(156 163 175);
        }

        .dark .pm-scheduler-status-button:hover {
            background: rgb(255 255 255 / 5%);
        }

        .dark .pm-scheduler-status-button.is-active {
            background: rgb(59 130 246 / 12%);
            color: rgb(96 165 250);
        }

        .pm-scheduler-status-count {
            display: inline-flex;
            min-width: 1.2rem;
            height: 1.2rem;
            align-items: center;
            justify-content: center;
            padding: 0 .3rem;
            border-radius: 999px;
            background: rgb(229 231 235);
            color: rgb(75 85 99);
            font-size: .65rem;
        }

        .dark .pm-scheduler-status-count {
            background: rgb(255 255 255 / 10%);
            color: rgb(209 213 219);
        }

        .pm-scheduler-calendar-shell,
        .pm-scheduler-agenda {
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: .7rem;
            background: white;
        }

        .dark .pm-scheduler-calendar-shell,
        .dark .pm-scheduler-agenda {
            border-color: rgb(255 255 255 / 10%);
            background: rgb(17 24 39);
        }

        .pm-scheduler-scroll {
            overflow-x: auto;
        }

        .pm-scheduler-week {
            display: grid;
            min-width: 830px;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .pm-scheduler-week-day {
            min-width: 0;
            min-height: 13.25rem;
            border-left: 1px solid rgb(229 231 235);
        }

        .pm-scheduler-week-day:first-child {
            border-left: 0;
        }

        .dark .pm-scheduler-week-day {
            border-color: rgb(255 255 255 / 10%);
        }

        .pm-scheduler-day-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 3rem;
            padding: .45rem .55rem;
            border-bottom: 1px solid rgb(229 231 235);
            background: rgb(249 250 251);
        }

        .dark .pm-scheduler-day-heading {
            border-color: rgb(255 255 255 / 10%);
            background: rgb(255 255 255 / 4%);
        }

        .pm-scheduler-day-name {
            color: rgb(107 114 128);
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .pm-scheduler-day-number {
            display: inline-flex;
            width: 1.65rem;
            height: 1.65rem;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: rgb(31 41 55);
            font-size: .78rem;
            font-weight: 700;
        }

        .dark .pm-scheduler-day-number {
            color: rgb(229 231 235);
        }

        .pm-scheduler-day-heading.is-today .pm-scheduler-day-number,
        .pm-scheduler-month-number.is-today {
            background: rgb(37 99 235);
            color: white;
        }

        .pm-scheduler-day-events {
            display: grid;
            align-content: start;
            gap: .35rem;
            padding: .45rem;
        }

        .pm-scheduler-event {
            position: relative;
            min-width: 0;
            overflow: hidden;
            border-width: 1px 1px 1px 3px;
            border-style: solid;
            border-radius: .45rem;
            padding: .4rem .45rem;
            background: rgb(248 250 252);
            border-color: rgb(203 213 225);
            color: rgb(51 65 85);
        }

        .pm-scheduler-event[data-status="scheduled"] {
            background: rgb(239 246 255);
            border-color: rgb(147 197 253);
            border-left-color: rgb(37 99 235);
            color: rgb(30 64 175);
        }

        .pm-scheduler-event[data-status="today"] {
            background: rgb(255 251 235);
            border-color: rgb(253 230 138);
            border-left-color: rgb(245 158 11);
            color: rgb(146 64 14);
        }

        .pm-scheduler-event[data-status="overdue"] {
            background: rgb(254 242 242);
            border-color: rgb(254 202 202);
            border-left-color: rgb(220 38 38);
            color: rgb(153 27 27);
        }

        .pm-scheduler-event[data-status="completed"] {
            background: rgb(240 253 244);
            border-color: rgb(187 247 208);
            border-left-color: rgb(22 163 74);
            color: rgb(22 101 52);
        }

        .dark .pm-scheduler-event[data-status="scheduled"] {
            background: rgb(37 99 235 / 12%);
            border-color: rgb(59 130 246 / 28%);
            border-left-color: rgb(96 165 250);
            color: rgb(191 219 254);
        }

        .dark .pm-scheduler-event[data-status="today"] {
            background: rgb(245 158 11 / 12%);
            border-color: rgb(245 158 11 / 28%);
            border-left-color: rgb(251 191 36);
            color: rgb(253 230 138);
        }

        .dark .pm-scheduler-event[data-status="overdue"] {
            background: rgb(220 38 38 / 12%);
            border-color: rgb(239 68 68 / 28%);
            border-left-color: rgb(248 113 113);
            color: rgb(254 202 202);
        }

        .dark .pm-scheduler-event[data-status="completed"] {
            background: rgb(22 163 74 / 12%);
            border-color: rgb(34 197 94 / 28%);
            border-left-color: rgb(74 222 128);
            color: rgb(187 247 208);
        }

        .pm-scheduler-event:hover {
            filter: brightness(.98);
        }

        .pm-scheduler-event-hit {
            position: absolute;
            z-index: 2;
            inset: 0;
            width: 100%;
            height: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .pm-scheduler-event-machine,
        .pm-scheduler-event-meta {
            position: relative;
            z-index: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            pointer-events: none;
        }

        .pm-scheduler-event-machine {
            font-size: .72rem;
            font-weight: 700;
            line-height: 1rem;
        }

        .pm-scheduler-event-meta {
            margin-top: .08rem;
            opacity: .78;
            font-size: .62rem;
            line-height: .9rem;
        }

        .pm-scheduler-more {
            width: 100%;
            padding: .16rem .3rem;
            border-radius: .35rem;
            color: rgb(37 99 235);
            font-size: .65rem;
            font-weight: 700;
            text-align: left;
        }

        .pm-scheduler-more:hover {
            background: rgb(239 246 255);
        }

        .dark .pm-scheduler-more {
            color: rgb(96 165 250);
        }

        .dark .pm-scheduler-more:hover {
            background: rgb(59 130 246 / 10%);
        }

        .pm-scheduler-month-weekdays,
        .pm-scheduler-month {
            display: grid;
            min-width: 830px;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .pm-scheduler-month-weekday {
            padding: .38rem .5rem;
            border-left: 1px solid rgb(229 231 235);
            background: rgb(249 250 251);
            color: rgb(107 114 128);
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .pm-scheduler-month-weekday:first-child {
            border-left: 0;
        }

        .dark .pm-scheduler-month-weekday {
            border-color: rgb(255 255 255 / 10%);
            background: rgb(255 255 255 / 4%);
            color: rgb(156 163 175);
        }

        .pm-scheduler-month-day {
            min-width: 0;
            min-height: 5.15rem;
            padding: .28rem;
            border-top: 1px solid rgb(229 231 235);
            border-left: 1px solid rgb(229 231 235);
        }

        .pm-scheduler-month-day:nth-child(7n + 1) {
            border-left: 0;
        }

        .dark .pm-scheduler-month-day {
            border-color: rgb(255 255 255 / 10%);
        }

        .pm-scheduler-month-day.is-outside {
            background: rgb(249 250 251 / 65%);
        }

        .dark .pm-scheduler-month-day.is-outside {
            background: rgb(255 255 255 / 2%);
        }

        .pm-scheduler-month-day.is-outside .pm-scheduler-month-number,
        .pm-scheduler-month-day.is-outside .pm-scheduler-event {
            opacity: .48;
        }

        .pm-scheduler-month-number {
            display: inline-flex;
            width: 1.35rem;
            height: 1.35rem;
            align-items: center;
            justify-content: center;
            margin-bottom: .2rem;
            border-radius: 999px;
            color: rgb(75 85 99);
            font-size: .67rem;
            font-weight: 700;
        }

        .dark .pm-scheduler-month-number {
            color: rgb(209 213 219);
        }

        .pm-scheduler-month-events {
            display: grid;
            gap: .18rem;
        }

        .pm-scheduler-month .pm-scheduler-event {
            padding: .18rem .3rem;
            border-radius: .32rem;
        }

        .pm-scheduler-month .pm-scheduler-event-machine {
            font-size: .63rem;
            line-height: .86rem;
        }

        .pm-scheduler-month .pm-scheduler-event-meta {
            display: none;
        }

        .pm-scheduler-agenda-header,
        .pm-scheduler-agenda-row {
            display: grid;
            grid-template-columns: minmax(11rem, 1.35fr) minmax(7rem, .7fr) 8.5rem 7rem 8rem;
            align-items: center;
            gap: .75rem;
        }

        .pm-scheduler-agenda-header {
            padding: .42rem .7rem;
            background: rgb(249 250 251);
            color: rgb(107 114 128);
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .dark .pm-scheduler-agenda-header {
            background: rgb(255 255 255 / 4%);
            color: rgb(156 163 175);
        }

        .pm-scheduler-agenda-row {
            min-height: 2.9rem;
            padding: .38rem .7rem;
            border-top: 1px solid rgb(229 231 235);
        }

        .dark .pm-scheduler-agenda-row {
            border-color: rgb(255 255 255 / 10%);
        }

        .pm-scheduler-agenda-row:hover {
            background: rgb(249 250 251 / 70%);
        }

        .dark .pm-scheduler-agenda-row:hover {
            background: rgb(255 255 255 / 3%);
        }

        .pm-scheduler-empty {
            display: flex;
            min-height: 12rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px dashed rgb(209 213 219);
            border-radius: .7rem;
            padding: 1.25rem;
            text-align: center;
        }

        .dark .pm-scheduler-empty {
            border-color: rgb(55 65 81);
        }

        .pm-scheduler-footer {
            min-height: 1.9rem;
        }

        @media (max-width: 900px) {
            .pm-scheduler-toolbar,
            .pm-scheduler-filters {
                align-items: stretch;
                flex-direction: column;
            }

            .pm-scheduler-nav,
            .pm-scheduler-statuses {
                flex-wrap: wrap;
            }

            .pm-scheduler-view-switcher,
            .pm-scheduler-plant {
                width: 100%;
            }

            .pm-scheduler-view-button {
                flex: 1;
            }
        }

        @media (max-width: 767px) {
            .pm-scheduler-date {
                flex: 1 1 10rem;
                width: auto;
            }

            .pm-scheduler-status-button {
                flex: 1 1 auto;
                justify-content: center;
            }

            .pm-scheduler-agenda-header {
                display: none;
            }

            .pm-scheduler-agenda-row {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: .35rem .65rem;
                padding: .6rem;
            }

            .pm-scheduler-agenda-machine {
                grid-column: 1;
                grid-row: 1;
            }

            .pm-scheduler-agenda-status {
                grid-column: 2;
                grid-row: 1;
            }

            .pm-scheduler-agenda-plant,
            .pm-scheduler-agenda-date {
                grid-row: 2;
            }

            .pm-scheduler-agenda-action {
                grid-column: 1 / -1;
                grid-row: 3;
            }

            .pm-scheduler-agenda-action .fi-btn {
                width: 100%;
                justify-content: center;
            }

            .pm-scheduler-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .pm-scheduler-pagination {
                justify-content: space-between;
            }
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            PM Scheduler
        </x-slot>

        <x-slot name="description">
            {{ $periodLabel }}
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

        <div class="pm-scheduler">
            <div class="pm-scheduler-toolbar">
                <div class="pm-scheduler-nav" aria-label="Navigasi periode">
                    <x-filament::icon-button
                        type="button"
                        wire:click="previousPeriod"
                        wire:loading.attr="disabled"
                        wire:target="previousPeriod,nextPeriod,currentPeriod,calendarDate,setViewMode"
                        color="gray"
                        icon="heroicon-m-chevron-left"
                        label="Periode sebelumnya"
                        size="sm"
                    />

                    <x-filament::button
                        type="button"
                        wire:click="currentPeriod"
                        wire:loading.attr="disabled"
                        wire:target="previousPeriod,nextPeriod,currentPeriod,calendarDate,setViewMode"
                        :disabled="$isCurrentPeriod"
                        color="gray"
                        size="xs"
                    >
                        Hari ini
                    </x-filament::button>

                    <x-filament::icon-button
                        type="button"
                        wire:click="nextPeriod"
                        wire:loading.attr="disabled"
                        wire:target="previousPeriod,nextPeriod,currentPeriod,calendarDate,setViewMode"
                        color="gray"
                        icon="heroicon-m-chevron-right"
                        label="Periode berikutnya"
                        size="sm"
                    />

                    <div class="pm-scheduler-date">
                        {{ $this->form }}
                    </div>
                </div>

                <div class="pm-scheduler-view-switcher" role="tablist" aria-label="Mode kalender">
                    @foreach (['agenda' => 'Agenda', 'week' => 'Week', 'month' => 'Month'] as $mode => $label)
                        <button
                            type="button"
                            wire:click="setViewMode('{{ $mode }}')"
                            @class([
                                'pm-scheduler-view-button',
                                'is-active' => $viewMode === $mode,
                            ])
                            aria-selected="{{ $viewMode === $mode ? 'true' : 'false' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="pm-scheduler-filters">
                <div class="pm-scheduler-statuses" aria-label="Filter status PM">
                    @foreach ([
                        'all' => ['Semua', 'heroicon-m-squares-2x2'],
                        'pending' => ['Perlu dikerjakan', 'heroicon-m-wrench-screwdriver'],
                        'overdue' => ['Terlambat', 'heroicon-m-exclamation-triangle'],
                        'completed' => ['Selesai', 'heroicon-m-check-circle'],
                    ] as $status => [$label, $icon])
                        <button
                            type="button"
                            wire:click="setFilter('{{ $status }}')"
                            @class([
                                'pm-scheduler-status-button',
                                'is-active' => $this->filter === $status,
                            ])
                        >
                            <x-filament::icon :icon="$icon" class="h-3.5 w-3.5" />
                            {{ $label }}
                            <span class="pm-scheduler-status-count">{{ $counts[$status] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="pm-scheduler-plant">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="plant" aria-label="Filter plant">
                            <option value="">Semua plant</option>
                            @foreach ($plants as $plantName)
                                <option value="{{ $plantName }}">{{ $plantName }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div
                wire:loading.class="opacity-60"
                wire:target="previousPeriod,nextPeriod,currentPeriod,calendarDate,setViewMode,plant,setFilter,goToPage,showDate"
            >
                @if ($totalJobs === 0)
                    <div class="pm-scheduler-empty">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-8 w-8 text-gray-400" />
                        <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                            Tidak ada PM pada periode dan filter ini
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Pilih periode, status, atau plant yang lain.
                        </p>
                    </div>
                @elseif ($viewMode === 'agenda')
                    <div class="pm-scheduler-agenda">
                        <div class="pm-scheduler-agenda-header" aria-hidden="true">
                            <span>Mesin</span>
                            <span>Plant</span>
                            <span>Rencana</span>
                            <span>Status</span>
                            <span class="text-right">Tindakan</span>
                        </div>

                        @foreach ($agendaJobs as $job)
                            <div class="pm-scheduler-agenda-row" wire:key="agenda-pm-{{ $rangeStart->format('Ymd') }}-{{ $job['id'] }}">
                                <div class="pm-scheduler-agenda-machine min-w-0">
                                    <p class="truncate text-xs font-semibold text-gray-950 dark:text-white">
                                        {{ $job['machine'] }}
                                    </p>
                                    @if (filled($job['note']))
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $job['note'] }}">
                                            {{ $job['note'] }}
                                        </p>
                                    @endif
                                </div>

                                <div class="pm-scheduler-agenda-plant flex min-w-0 items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-building-office-2" class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                    <span class="truncate">{{ $job['plant'] }}</span>
                                </div>

                                <div class="pm-scheduler-agenda-date flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-calendar" class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                    <span class="font-medium">{{ $job['planned_date']->format('D, d M') }}</span>
                                </div>

                                <div class="pm-scheduler-agenda-status">
                                    <x-filament::badge :color="$job['status_color']" size="sm">
                                        {{ $job['status_label'] }}
                                    </x-filament::badge>
                                </div>

                                <div class="pm-scheduler-agenda-action text-right">
                                    @if ($job['can_open'])
                                        @if ($job['status'] === 'completed')
                                            <x-filament::button
                                                tag="a"
                                                :href="$job['action_url']"
                                                color="gray"
                                                icon="heroicon-m-eye"
                                                size="xs"
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
                                                size="xs"
                                            >
                                                Checklist
                                            </x-filament::button>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Tidak ada akses</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif ($viewMode === 'week')
                    <div class="pm-scheduler-calendar-shell">
                        <div class="pm-scheduler-scroll">
                            <div class="pm-scheduler-week">
                                @foreach ($calendarDays as $day)
                                    <section class="pm-scheduler-week-day" wire:key="week-day-{{ $day['date']->format('Ymd') }}">
                                        <header @class(['pm-scheduler-day-heading', 'is-today' => $day['is_today']])>
                                            <span class="pm-scheduler-day-name">{{ $day['date']->format('D') }}</span>
                                            <span class="pm-scheduler-day-number">{{ $day['date']->format('d') }}</span>
                                        </header>

                                        <div class="pm-scheduler-day-events">
                                            @foreach ($day['jobs'] as $job)
                                                <article
                                                    class="pm-scheduler-event"
                                                    data-status="{{ $job['status'] }}"
                                                    wire:key="week-event-{{ $job['id'] }}"
                                                >
                                                    @if ($job['can_open'])
                                                        @if ($job['status'] === 'completed')
                                                            <a
                                                                href="{{ $job['action_url'] }}"
                                                                class="pm-scheduler-event-hit"
                                                                aria-label="Lihat hasil {{ $job['machine'] }}"
                                                                title="Lihat hasil {{ $job['machine'] }}"
                                                            ></a>
                                                        @else
                                                            <button
                                                                type="button"
                                                                wire:click="openChecklist({{ $job['id'] }})"
                                                                class="pm-scheduler-event-hit"
                                                                aria-label="Buka checklist {{ $job['machine'] }}"
                                                                title="Buka checklist {{ $job['machine'] }}"
                                                            ></button>
                                                        @endif
                                                    @endif

                                                    <p class="pm-scheduler-event-machine">{{ $job['machine'] }}</p>
                                                    <p class="pm-scheduler-event-meta">
                                                        {{ $job['plant'] }} · {{ $job['status_label'] }}
                                                    </p>
                                                </article>
                                            @endforeach

                                            @if ($day['more'] > 0)
                                                <button
                                                    type="button"
                                                    wire:click="showDate('{{ $day['date']->toDateString() }}')"
                                                    class="pm-scheduler-more"
                                                >
                                                    +{{ $day['more'] }} lainnya
                                                </button>
                                            @endif
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pm-scheduler-calendar-shell">
                        <div class="pm-scheduler-scroll">
                            <div class="pm-scheduler-month-weekdays" aria-hidden="true">
                                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                                    <div class="pm-scheduler-month-weekday">{{ $dayName }}</div>
                                @endforeach
                            </div>

                            <div class="pm-scheduler-month">
                                @foreach ($calendarDays as $day)
                                    <section
                                        @class([
                                            'pm-scheduler-month-day',
                                            'is-outside' => ! $day['is_current_month'],
                                        ])
                                        wire:key="month-day-{{ $day['date']->format('Ymd') }}"
                                    >
                                        <button
                                            type="button"
                                            wire:click="showDate('{{ $day['date']->toDateString() }}')"
                                            @class(['pm-scheduler-month-number', 'is-today' => $day['is_today']])
                                            title="Buka agenda {{ $day['date']->format('d F Y') }}"
                                        >
                                            {{ $day['date']->format('d') }}
                                        </button>

                                        <div class="pm-scheduler-month-events">
                                            @foreach ($day['jobs'] as $job)
                                                <article
                                                    class="pm-scheduler-event"
                                                    data-status="{{ $job['status'] }}"
                                                    wire:key="month-event-{{ $job['id'] }}"
                                                >
                                                    @if ($job['can_open'])
                                                        @if ($job['status'] === 'completed')
                                                            <a
                                                                href="{{ $job['action_url'] }}"
                                                                class="pm-scheduler-event-hit"
                                                                aria-label="Lihat hasil {{ $job['machine'] }}"
                                                                title="{{ $job['machine'] }} · {{ $job['plant'] }} · {{ $job['status_label'] }}"
                                                            ></a>
                                                        @else
                                                            <button
                                                                type="button"
                                                                wire:click="openChecklist({{ $job['id'] }})"
                                                                class="pm-scheduler-event-hit"
                                                                aria-label="Buka checklist {{ $job['machine'] }}"
                                                                title="{{ $job['machine'] }} · {{ $job['plant'] }} · {{ $job['status_label'] }}"
                                                            ></button>
                                                        @endif
                                                    @endif

                                                    <p class="pm-scheduler-event-machine">{{ $job['machine'] }}</p>
                                                    <p class="pm-scheduler-event-meta">{{ $job['plant'] }}</p>
                                                </article>
                                            @endforeach

                                            @if ($day['more'] > 0)
                                                <button
                                                    type="button"
                                                    wire:click="showDate('{{ $day['date']->toDateString() }}')"
                                                    class="pm-scheduler-more"
                                                >
                                                    +{{ $day['more'] }} lainnya
                                                </button>
                                            @endif
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="pm-scheduler-footer">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($viewMode === 'agenda' && $totalJobs > 0)
                        {{ $fromJob }}–{{ $toJob }} dari {{ $totalJobs }} PM
                    @else
                        {{ $totalJobs }} PM pada periode ini
                    @endif
                </p>

                @if ($viewMode === 'agenda' && $totalPages > 1)
                    <div class="pm-scheduler-pagination">
                        <x-filament::icon-button
                            type="button"
                            wire:click="goToPage({{ $currentPage - 1 }})"
                            :disabled="$currentPage <= 1"
                            color="gray"
                            icon="heroicon-m-chevron-left"
                            label="Halaman sebelumnya"
                            size="sm"
                        />

                        <span class="min-w-16 text-center text-xs font-medium text-gray-600 dark:text-gray-300">
                            {{ $currentPage }} / {{ $totalPages }}
                        </span>

                        <x-filament::icon-button
                            type="button"
                            wire:click="goToPage({{ $currentPage + 1 }})"
                            :disabled="$currentPage >= $totalPages"
                            color="gray"
                            icon="heroicon-m-chevron-right"
                            label="Halaman berikutnya"
                            size="sm"
                        />
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
