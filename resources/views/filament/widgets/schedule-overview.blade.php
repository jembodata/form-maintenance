<x-filament-widgets::widget>
    <x-filament::section>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@9.0.15/codebase/dhtmlxgantt.min.css">

        <style>
            /* Weekend shading */
            .gantt_task_cell.weekend,
            .gantt_scale_cell.weekend {
                background: #f0f0f0;
            }

            :root {
                /* scales */
                --dhx-gantt-scale-background: #8E8E8E;
                --dhx-gantt-base-colors-border-light: #C5C5C5;
                --dhx-gantt-base-colors-border: #DFE0E1;
                --dhx-gantt-scale-color: #FFF;
                --dhx-gantt-base-colors-icons: #00000099;

                /* tasks */
                --dhx-gantt-task-background: #3db9d3;
                --dhx-gantt-task-color: #FFFFFF;
                --dhx-gantt-project-background: #6AA84F;
                --dhx-gantt-project-color: #FFFFFF;

                /* links */
                --dhx-gantt-link-background: #ffa011;
                --dhx-gantt-link-background-hover: #ffa011;

            }
        </style>

        <div x-data="{
            items: @js($tasks ?? []), // dari PHP: id, text/nama_mesin, plant, start_date(YYYY-MM-DD), duration(=1), hours
            currentScale: 'day',
        
            toGanttDMY(raw) {
                if (!raw) return null;
                const s = String(raw).trim();
                if (/^\d{2}-\d{2}-\d{4}$/.test(s)) return s;
                if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
                    const [Y, M, D] = s.slice(0, 10).split('-');
                    return `${D}-${M}-${Y}`;
                }
                const d = new Date(s);
                if (!isNaN(d)) {
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yy = d.getFullYear();
                    return `${dd}-${mm}-${yy}`;
                }
                return null;
            },
        
            // simple hash → index palette
            _hash(s) { let h = 0; for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) | 0; return Math.abs(h); },
            palette: ['#FFBE0B', '#FB5607', '#FF006E', '#8338EC', '#3A86FF'],
        
            // inject CSS warna bar per plant
            injectPlantColors(plants) {
                const el = document.getElementById('plant-colors') || Object.assign(document.createElement('style'), { id: 'plant-colors' });
                let css = '';
                plants.forEach(p => {
                    const idx = this._hash(p) % this.palette.length;
                    const col = this.palette[idx];
                    const cls = this.classForPlant(p);
                    css += `.gantt_task_line.${cls}{background:${col} !important; border-color:${col} !important;}
                                                                                                                        .gantt_task_line.${cls} .gantt_task_progress{background:rgba(255,255,255,.25) !important;}`;
                });
                el.textContent = css;
                document.head.appendChild(el);
            },
            classForPlant(p) { return 'plant_' + String(p || 'unknown').toLowerCase().replace(/[^a-z0-9]+/g, '_'); },
            plantColor(p) {
                const idx = this._hash(p) % this.palette.length;
                return this.palette[idx];
            },
        
            async loadLib() {
                if (window.gantt) return true;
                if (document.querySelector('script[data-gantt]')) {
                    for (let i = 0; i < 50; i++) {
                        if (window.gantt) return true;
                        await new Promise(r => setTimeout(r, 100));
                    }
                    return !!window.gantt;
                }
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/dhtmlx-gantt@9.0.15/codebase/dhtmlxgantt.min.js';
                s.async = true;
                s.defer = true;
                s.setAttribute('data-gantt', '1');
                const loaded = new Promise((res, rej) => {
                    s.onload = () => res(true);
                    s.onerror = () => rej(new Error('gantt js failed'));
                });
                document.head.appendChild(s);
                try {
                    await loaded;
                    await new Promise(r => setTimeout(r, 50));
                    return !!window.gantt;
                } catch { return false; }
            },
        
            // bangun data: tambahkan parent per-Plant + summary unik mesin
            buildGroupedData() {
                const raw = Array.isArray(this.items) ? this.items : [];
                // group by plant
                const byPlant = {};
                for (const t of raw) {
                    const plant = t.plant ?? t.plant_area ?? '-';
                    (byPlant[plant] ||= []).push(t);
                }
        
                // siapkan array parent+children + summary unik per plant (berdasar range data aktif)
                const data = [];
                const plants = Object.keys(byPlant);
        
                // injeksi warna
                this.injectPlantColors(plants);
        
                for (const plant of plants) {
                    const children = byPlant[plant];
                    // hitung unique mesin (nama) dalam data saat ini (semua bulan/tahun atau tersaring form)
                    const uniq = new Set(children.map(x => (x.text ?? x.nama_mesin ?? '').toString().trim())).size;
        
                    // parent row (project)
                    const pid = `plant::${plant}`;
                    data.push({
                        id: pid,
                        text: plant,
                        is_plant: true,
                        type: 'project',
                        open: true,
                        unique_count: uniq,
                        // start_date/duration optional di parent — biar bar parent auto-cover children
                    });
        
                    // anak: tiap baris checksheet → bar 1 hari, hours untuk tampil di grid
                    children.forEach((t, i) => {
                        const dmy = this.toGanttDMY(t.start_date ?? t.date);
                        if (!dmy) return;
                        data.push({
                            id: t.id,
                            parent: pid,
                            text: t.text ?? t.nama_mesin ?? 'Task',
                            plant: plant,
                            start_date: dmy,
                            duration: 1,
                            hours: Number(t.hours ?? t.duration ?? 1),
                            open: true,
                            // class untuk warna bar
                            $class: this.classForPlant(plant),
                            color: this.plantColor(plant),
                        });
                    });
                }
        
                return data;
            },
        
            // range untuk export cloud
            getDataRange() {
                const tasks = [];
                window.gantt.eachTask(t => tasks.push(t));
                if (!tasks.length) return {};
                const toIso = d => d?.split?.('-')?.reverse?.().join?.('-');
                const starts = tasks.filter(t => !t.is_plant && t.start_date).map(t => new Date(toIso(t.start_date))).filter(d => !isNaN(d));
                if (!starts.length) return {};
                const min = new Date(Math.min(...starts));
                const max = new Date(Math.max(...starts));
                return { start: min, end: new Date(max.getFullYear(), max.getMonth(), max.getDate() + 1) };
            },
        
            async init() {
                const ok = await this.loadLib();
                if (!ok) return;
                const gantt = window.gantt;
                if (gantt.plugins) gantt.plugins({ quick_info: true, export_api: true });
        
                gantt.config.readonly = true;
                gantt.config.autofit = true;
                gantt.config.fit_tasks = true;
                gantt.config.date_format = '%d-%m-%Y'; // bar = 1 hari (stabil export)
        
                // warna bar per-plant
                gantt.templates.task_class = (start, end, task) => task.$class || '';
        
                // weekend shading
                gantt.templates.scale_cell_class = function(date) {
                    if (date.getDay() == 0 || date.getDay() == 6) {
                        return 'weekend';
                    }
                };
                gantt.templates.timeline_cell_class = function(item, date) {
                    if (date.getDay() == 0 || date.getDay() == 6) {
                        return 'weekend'
                    }
                };
        
                // LEFT GRID columns
                const leftGrid = {
                    view: 'grid',
                    scrollX: 'scrollHor',
                    scrollY: 'scrollVer',
                    width: 420, // sebelumnya 360
                    config: {
                        columns: [{
                                name: 'text',
                                label: 'Plant / Mesin',
                                tree: true,
                                width: 260,
                                template: (t) => t.is_plant ? `${t.text}` : t.text
                            },
                            {
                                name: 'hours',
                                label: 'Dur (Jam)',
                                align: 'center',
                                width: 120,
                                template: (t) => t.is_plant ? '' : (Number(t.hours).toFixed?.(2) ?? '-')
                            },
                        ]
                    }
                };
        
                // TIMELINE
                const timeline = { view: 'timeline', scrollX: 'scrollHor', scrollY: 'scrollVer' };
        
                // Hidden text in bar
                gantt.templates.task_text = function(start, end, task) {
                    // kalau parent row (project) → tampilkan nama plant
                    if (task.type === 'project' || task.is_plant) {
                        return task.text;
                    }
        
                    // kalau task biasa → kosongkan
                    return '';
                };
        
                // SCROLLBARS
                const scrollVer = { view: 'scrollbar', id: 'scrollVer' };
                const scrollHor = { view: 'scrollbar', id: 'scrollHor', height: 16 };
        
                // Dynamic scales / zoom
                const zoomConfig = {
                    levels: [{
                            name: 'day',
                            scales: [{unit: 'month', step: 1, format: '%F %Y'}, { unit: 'day', step: 1, format: '%d' }],
                        },
                        {
                            name: 'week',
                            scale_height: 50,
                            min_column_width: 50,
                            scales: [{
                                    unit: 'week',
                                    step: 1,
                                    format: (date) => {
                                        const toStr = gantt.date.date_to_str('%d %M');
                                        const end = gantt.date.add(date, 7 - date.getDay(), 'day');
                                        const w = gantt.date.date_to_str('%W')(date);
                                        return '#' + w + ', ' + toStr(date) + ' - ' + toStr(end);
                                    }
                                },
                                { unit: 'day', step: 1, format: '%d' },
                            ],
                        },
                        {
                            name: 'month',
                            scales: [{ unit: 'month', format: '%F, %Y' }, { unit: 'week', format: 'Week #%W' }],
                        },
                        {
                            name: 'quarter',
                            scale_height: 50,
                            min_column_width: 90,
                            scales: [
                                { unit: 'month', step: 1, format: '%M' },
                                {
                                    unit: 'quarter',
                                    step: 1,
                                    format: (date) => {
                                        const toStr = gantt.date.date_to_str('%M');
                                        const end = gantt.date.add(date, 2 - (date.getMonth() % 3), 'month');
                                        return toStr(date) + ' - ' + toStr(end);
                                    }
                                },
                            ],
                        },
                        {
                            name: 'year',
                            scale_height: 50,
                            min_column_width: 30,
                            scales: [{ unit: 'year', step: 1, format: '%Y' }]
                        },
                    ]
                };
                gantt.ext.zoom.init(zoomConfig);
                gantt.ext.zoom.setLevel(this.currentScale);
                gantt.ext.zoom.attachEvent('onAfterZoom', (_, cfg) => this.currentScale = cfg.name);
        
                // === Layout dengan right-side grid ===
                gantt.config.layout = {
                    css: 'gantt_container',
                    rows: [{
                            cols: [
                                leftGrid,
                                { resizer: true, width: 6 },
                                timeline,
                                scrollVer
                            ]
                        },
                        scrollHor
                    ]
                };
        
                // init
                const el = this.$refs.gantt;
                if (!el.__ganttInited) {
                    gantt.init(el);
                    el.__ganttInited = true;
                }
        
                // build & render
                const data = this.buildGroupedData();
                gantt.clearAll();
                gantt.parse({ data, links: [] });
        
                // fokus ke data awal
                const toIso = dmy => dmy?.split?.('-')?.reverse?.().join?.('-');
                const dates = data.filter(x => !x.is_plant && x.start_date).map(x => new Date(toIso(x.start_date))).filter(d => !isNaN(d)).sort((a, b) => a - b);
                if (dates.length) gantt.showDate(dates[0]);
                gantt.render();
        
                // Export PDF Cloud – full range data
                this.exportPdfCloud = () => {
                    const { start, end } = this.getDataRange();
                    gantt.exportToPDF({
                        name: `checksheet-gantt-${new Date().toISOString().slice(0,10)}.pdf`,
                        raw: true,
                        skin: 'material',
                        ...(start && end ? { start, end } : {}),
                    });
                };
            },
        
            // controls
            zoomIn() { window.gantt?.ext?.zoom?.zoomIn?.(); },
            zoomOut() { window.gantt?.ext?.zoom?.zoomOut?.(); },
            setScale(level) {
                this.currentScale = level;
                window.gantt?.ext?.zoom?.setLevel?.(level);
            },
            exportPdfCloud() {},
        }" x-init="init()">
            <div class="flex flex-col gap-3 pb-4 pt-0">
                <div class="grid flex-1 gap-y-1">
                    <div class="text-lg font-bold">Realisasi</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Preventive Maintenance(PM) Mesin per Plant
                    </div>
                </div>
            </div>

            <div class="gap-3 mb-2">
                {{ $this->form }}
            </div>

            <div class="flex flex-wrap items-center gap-3 mb-2">
                <div class="flex gap-2">
                    <x-filament::button color="success" size="sm" icon="heroicon-m-arrow-down-tray"
                        x-on:click="exportPdfCloud()" type="button">
                        Export PDF
                    </x-filament::button>
                    {{-- <x-filament::button color="info" size="sm" icon="heroicon-m-magnifying-glass-plus"
                        x-on:click="zoomIn()" type="button">
                        Zoom In
                    </x-filament::button>
                    <x-filament::button color="info" size="sm" icon="heroicon-m-magnifying-glass-minus"
                        x-on:click="zoomOut()" type="button">
                        Zoom Out
                    </x-filament::button> --}}
                </div>
                <div class="flex items-center gap-3">
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="scale" value="day" @change="setScale('day')"
                            :checked="currentScale === 'day'"> Day
                    </label>
                    {{-- <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="scale" value="week" @change="setScale('week')"
                            :checked="currentScale === 'week'"> Week
                    </label> --}}
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="scale" value="month" @change="setScale('month')"
                            :checked="currentScale === 'month'"> Month
                    </label>
                    {{-- <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="scale" value="quarter" @change="setScale('quarter')"
                            :checked="currentScale === 'quarter'"> Quarter
                    </label> --}}
                    {{-- <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" name="scale" value="year" @change="setScale('year')"
                            :checked="currentScale === 'year'"> Year
                    </label> --}}
                </div>
            </div>

            <div wire:ignore>
                <div x-ref="gantt" style="width: 100%; height: 600px;"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
