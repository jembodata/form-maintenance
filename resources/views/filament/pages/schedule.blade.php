<x-filament-panels::page>
    <x-filament::card>
        <div x-data="window.ganttPage()" x-init="init()" x-on:schedule-reload.window="reloadData()"
            x-on:close-modal.window="setTimeout(() => { refreshSizes() }, 200)" wire:ignore>

            <!-- Toolbar -->
            <div class="flex items-center gap-2 mb-3" style="margin-bottom: 0.75rem;">
                <x-filament::button x-on:click="setScale('day')" icon="heroicon-o-calendar-days">Day</x-filament::button>
                <x-filament::button x-on:click="setScale('month')" icon="heroicon-o-calendar">Month</x-filament::button>

                <x-filament::button color="gray" x-on:click="reloadData()"
                    icon="heroicon-o-arrow-path">Refresh</x-filament::button>

                <!-- Toggle Resource Panel ala demo -->
                <x-filament::button color="warning" x-on:click="toggleResourcePanel()"
                    icon="heroicon-o-rectangle-group">
                    Toggle Resource
                </x-filament::button>

                <x-filament::button color="success" x-on:click="exportPDF()" icon="heroicon-o-arrow-down-tray"
                    class="ml-auto">
                    Export PDF
                </x-filament::button>

                {{-- <x-filament::button color="success" x-on:click="exportToExcel()" icon="heroicon-o-arrow-down-tray"
                    class="ml-auto">
                    Export excel
                </x-filament::button> --}}

                <div>{{ $this->form }}</div>
            </div>

            {{-- dhtmlx-gantt v9 (export_api via plugins) --}}
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@9.0.15/codebase/dhtmlxgantt.min.css">
            <script src="https://cdn.jsdelivr.net/npm/dhtmlx-gantt@9.0.15/codebase/dhtmlxgantt.min.js"></script>

            <style>
                #gantt_here {
                    height: 79vh;
                    min-height: 520px;
                }

                .gantt_grid_scale .gantt_grid_head_cell,
                .gantt_task .gantt_task_scale .gantt_scale_cell {
                    font-weight: 600;
                    font-size: 14px;
                    color: rgba(0, 0, 0, .75);
                }

                /* Warna per-plant */
                .plant-a .gantt_task_content {
                    background: #FFBE0B;
                }

                .plant-b .gantt_task_content {
                    background: #FB5607;
                }

                .plant-c .gantt_task_content {
                    background: #FF006E;
                }

                .plant-d .gantt_task_content {
                    background: #8338EC;
                }

                .plant-e .gantt_task_content {
                    background: #3A86FF;
                }

                .gantt_project .gantt_task_content {
                    font-weight: 700;
                }

                /* Resource layer */
                .gantt_resource_marker {
                    opacity: .9;
                    border-radius: 2px;
                    font-size: 12px;
                }

                .gantt_resource_marker_ok {
                    background: rgba(31, 251, 111, .745);
                    border: 1px solid rgba(32, 237, 107, .5);
                }

                .gantt_resource_marker_overtime {
                    background: rgba(255, 7, 7, .758);
                    border: 1px solid rgba(239, 68, 68, .5);
                }

                /* Hide progress UI */
                .gantt_task_progress,
                .gantt_task_progress_drag,
                .gantt_task_progress_wrapper {
                    display: none !important;
                }

                /* Weekend shading */
                .gantt_task_cell.weekend,
                .gantt_scale_cell.weekend {
                    background: #f0f0f0;
                }
            </style>

            <div id="gantt_here" wire:ignore></div>

            <script>
                // Ambil style untuk export PDF
                // const styles = []
                // for (el in document.styleSheets) {
                //     try {
                //         const rules = (document.styleSheets[el]).cssRules;
                //         for (rule in rules) {
                //             styles.push(rules[rule].cssText)
                //         }
                //     } catch (e) {}
                // }
                // ========= GLOBAL HELPERS =========
                const __frag = () => document.createDocumentFragment();

                function safeToYmd(value) {
                    try {
                        const fmt = gantt.date.date_to_str("%Y-%m-%d");
                        if (value instanceof Date) return fmt(value);
                        if (!value) return null;
                        const d = gantt.date.parseDate(value, gantt.config.date_format);
                        if (d instanceof Date && !isNaN(d)) return fmt(d);
                        return null;
                    } catch {
                        return null;
                    }
                }

                function ymdFromAny(v) {
                    try {
                        const fmt = gantt.date.date_to_str("%Y-%m-%d");
                        if (v instanceof Date) return fmt(v);
                        if (!v) return null;
                        const d = gantt.date.parseDate(v, gantt.config.date_format);
                        return (d instanceof Date && !isNaN(d)) ? fmt(d) : null;
                    } catch {
                        return null;
                    }
                }

                window.ganttCalcResLoad = function(tasks, scale) {
                    if (!scale) return [];
                    const stepUnit = "day";
                    const buckets = {};
                    for (const t of (tasks || [])) {
                        if (!t || t.type === "project") continue;
                        const y = ymdFromAny(t.start_date);
                        if (!y) continue;
                        const h = (typeof t.hours === "number" && !Number.isNaN(t.hours)) ? t.hours : 0;
                        buckets[y] = (buckets[y] || 0) + h;
                    }
                    const out = [];
                    for (const y in buckets) {
                        const start = gantt.date.parseDate(y, "%Y-%m-%d");
                        const end = gantt.date.add(start, 1, stepUnit);
                        out.push({
                            start_date: start,
                            end_date: end,
                            value: buckets[y]
                        });
                    }
                    return out;
                };

                window.ganttRenderResLine = function(resource, timeline) {
                    const frag = __frag();
                    if (!timeline || !resource || !gantt.$root) return frag;

                    const tasks = gantt.getTaskBy("user", resource.id) || [];
                    const grid = window.ganttCalcResLoad(tasks, timeline.getScale()) || [];
                    if (!grid.length) return frag;

                    const row = document.createElement("div");
                    for (const span of grid) {
                        const sizes = timeline.getItemPosition(resource, span.start_date, span.end_date);
                        if (!sizes) continue;

                        const el = document.createElement("div");
                        el.className = (span.value <= 8) ?
                            "gantt_resource_marker gantt_resource_marker_ok" :
                            "gantt_resource_marker gantt_resource_marker_overtime";
                        el.style.cssText = [
                            `left:${sizes.left}px`,
                            `width:${sizes.width}px`,
                            `position:absolute`,
                            `height:${gantt.config.row_height - 1}px`,
                            `line-height:${sizes.height}px`,
                            `top:${sizes.top}px`,
                            `text-align:center`
                        ].join(";");
                        el.textContent = span.value;
                        row.appendChild(el);
                    }
                    return row;
                };

                // ========= ALPINE APP =========
                window.ganttPage = function() {
                    return {
                        // ---- State ----
                        payload: @json($this->getGanttPayload()),
                        currentScale: 'day',
                        resourcesStore: null,
                        _dragSaveTimer: null,
                        _lastSavedKey: null,
                        _isSaving: false,
                        _resourcePanelShown: true,

                        // Cached layouts ala demo
                        _resourceLayout: null,
                        _noResourceLayout: null,

                        init() {

                            // Plugins (v9: export_api & quick_info)
                            gantt.plugins({
                                export_api: true,
                                quick_info: true
                            });

                            gantt.config.autofit = true;
                            gantt.config.fit_tasks = true;
                            gantt.config.date_format = "%Y-%m-%d";
                            gantt.config.work_time = true;
                            gantt.setWorkTime({
                                day: 0,
                                work_time: false
                            }); // Sun
                            gantt.setWorkTime({
                                day: 6,
                                work_time: false
                            }); // Sat

                            // Weekend shading
                            gantt.templates.scale_cell_class = d => ([0, 6].includes(d.getDay()) ? "weekend" : "");
                            gantt.templates.timeline_cell_class = (item, d) => ([0, 6].includes(d.getDay()) ? "weekend" : "");

                            // UI trims
                            gantt.config.progress = false;
                            gantt.config.drag_progress = false;
                            gantt.config.show_quick_info = true;
                            gantt.templates.progress_text = () => "";
                            gantt.config.details_on_dblclick = false;
                            gantt.config.details_on_create = false;
                            gantt.attachEvent("onTaskDblClick", () => false);
                            gantt.attachEvent("onBeforeLightbox", () => false);

                            // Link dependency config
                            gantt.config.show_links = true;
                            gantt.config.drag_links = true;
                            gantt.config.links = {
                                finish_to_start: "0",
                                start_to_start: "1",
                                finish_to_finish: "2",
                                start_to_finish: "3"
                            };

                            // quick_info content (ambil "note" dari keterangan JSON bila ada)
                            gantt.templates.quick_info_content = function(start, end, task) {
                                if (task.type === "project") return task.text || "";
                                let note = "";
                                try {
                                    const raw = task.keterangan;
                                    if (typeof raw === "string" && raw.trim() !== "") {
                                        const obj = JSON.parse(raw);
                                        note = obj.note || "";
                                    }
                                } catch {
                                    note = (task.keterangan ?? "").toString();
                                }
                                return note.trim() !== "" ? note : "(Tidak ada keterangan)";
                            };
                            gantt.config.quickinfo_buttons = ["edit_filament", "delete_filament"];
                            gantt.locale.labels["edit_filament"] = "Edit";
                            gantt.locale.labels["delete_filament"] = "Delete";

                            // === Datastore resources HARUS ada sebelum layout ===
                            if (!this.resourcesStore) {
                                this.resourcesStore = gantt.createDatastore({
                                    name: "resources",
                                    initItem: (item) => {
                                        item.id = (item.id ?? item.key ?? gantt.uid());
                                        item.label = (item.label ?? item.text ?? ""); // amanin label
                                        return item;
                                    }
                                });
                            }
                            this.resourcesStore.clearAll();
                            this.resourcesStore.parse([]);

                            // === Grid configs ===
                            const mainGridConfig = {
                                columns: [{
                                    name: "text",
                                    label: "Task / Plant",
                                    tree: true,
                                    width: 260,
                                    resize: true
                                }]
                            };
                            const resourcePanelConfig = {
                                columns: [{
                                        name: "name",
                                        label: "Name",
                                        template: (res) => res.label
                                    },
                                    {
                                        name: "workload",
                                        label: "Workload (h)",
                                        template: (res) => {
                                            const tasks = gantt.getTaskBy("user", res.id) || [];
                                            let total = 0;
                                            for (const t of tasks) total += (typeof t.hours === "number" ? t.hours :
                                                0);
                                            return total.toFixed(2).replace(/\.00$/, "");
                                        }
                                    }
                                ]
                            };

                            // === Layouts ala demo ===
                            this._resourceLayout = {
                                css: "gantt_container",
                                rows: [{
                                        cols: [{
                                                view: "grid",
                                                group: "grids",
                                                config: mainGridConfig,
                                                scrollY: "scrollVer"
                                            },
                                            {
                                                resizer: true,
                                                width: 1,
                                                group: "vertical"
                                            },
                                            {
                                                view: "timeline",
                                                id: "timeline",
                                                scrollX: "scrollHor",
                                                scrollY: "scrollVer"
                                            },
                                            {
                                                view: "scrollbar",
                                                id: "scrollVer",
                                                group: "vertical"
                                            }
                                        ],
                                        gravity: 2
                                    },
                                    {
                                        resizer: true,
                                        height: 8
                                    },
                                    {
                                        config: resourcePanelConfig,
                                        cols: [{
                                                view: "grid",
                                                id: "resourceGrid",
                                                group: "grids",
                                                bind: "resources",
                                                scrollY: "resourceVScroll"
                                            },
                                            {
                                                resizer: true,
                                                width: 1,
                                                group: "vertical"
                                            },
                                            {
                                                view: "timeline",
                                                id: "resourceTimeline",
                                                bind: "resources",
                                                bindLinks: null,
                                                layers: [window.ganttRenderResLine, "taskBg"],
                                                scrollX: "scrollHor",
                                                scrollY: "resourceVScroll"
                                            },
                                            {
                                                view: "scrollbar",
                                                id: "resourceVScroll",
                                                group: "vertical"
                                            }
                                        ],
                                        gravity: 1,
                                        height: 180
                                    },
                                    {
                                        view: "scrollbar",
                                        id: "scrollHor"
                                    }
                                ]
                            };

                            this._noResourceLayout = {
                                css: "gantt_container",
                                rows: [{
                                        cols: [{
                                                view: "grid",
                                                group: "grids",
                                                config: mainGridConfig,
                                                scrollY: "scrollVer"
                                            },
                                            {
                                                resizer: true,
                                                width: 1,
                                                group: "vertical"
                                            },
                                            {
                                                view: "timeline",
                                                id: "timeline",
                                                scrollX: "scrollHor",
                                                scrollY: "scrollVer"
                                            },
                                            {
                                                view: "scrollbar",
                                                id: "scrollVer",
                                                group: "vertical"
                                            }
                                        ],
                                        gravity: 2
                                    },
                                    {
                                        view: "scrollbar",
                                        id: "scrollHor"
                                    }
                                ]
                            };

                            // Inisialisasi Gantt pertama
                            gantt.config.layout = this._resourcePanelShown ? this._resourceLayout : this._noResourceLayout;
                            if (!window.__gantt_inited__) {
                                gantt.init("gantt_here");
                                this.resourcesStore.clearAll();
                                this.resourcesStore.parse(this.payload.resources || []);
                                this.resourcesStore.refresh();
                                window.__gantt_inited__ = true;
                            }
                            this.applyScale(this.currentScale, true);
                            gantt.config.drag_move = true;
                            gantt.config.drag_resize = false;

                            // Warna per-plant
                            const plantClassMap = {
                                'PLANT A': 'plant-a',
                                'PLANT B': 'plant-b',
                                'PLANT C': 'plant-c',
                                'PLANT D': 'plant-d',
                                'PLANT E': 'plant-e'
                            };
                            gantt.templates.task_class = (s, e, t) => (t.type === 'project') ? '' : (plantClassMap[(t.plant ||
                                '').toUpperCase()] || '');

                            // Load pertama: tasks -> resources
                            gantt.clearAll();
                            gantt.parse({
                                data: this._filtered(this.payload.data),
                                links: this.payload.links || []
                            });
                            this.resourcesStore.parse(this.payload.resources || []);
                            this.resourcesStore.refresh();
                            gantt.render();

                            // Sinkron refresh resource jika task store berubah
                            const taskStore = gantt.getDatastore("task");
                            taskStore.attachEvent("onStoreUpdated", () => {
                                this.resourcesStore.refresh();
                            });

                            // Persist movement (debounced & guarded)
                            const lw = @this;
                            const queuePersistMove = (id) => {
                                const t = gantt.getTask(id);
                                if (!t) return;
                                const ymd = safeToYmd(t.start_date);
                                if (!ymd) return;
                                const key = id + ":" + ymd;
                                if (this._isSaving && this._lastSavedKey === key) return;
                                if (this._dragSaveTimer) clearTimeout(this._dragSaveTimer);
                                this._dragSaveTimer = setTimeout(async () => {
                                    if (this._isSaving) return;
                                    this._isSaving = true;
                                    this._lastSavedKey = key;
                                    try {
                                        await lw.updateTask({
                                            id: id,
                                            start_date: ymd
                                        });
                                        this.resourcesStore.refresh();
                                        gantt.render();
                                    } catch (e) {
                                        console.error("Persist move failed:", e);
                                    } finally {
                                        this._isSaving = false;
                                    }
                                }, 150);
                            };
                            gantt.attachEvent("onAfterTaskDrag", (id, mode, item) => {
                                if (!id || !item) return true;
                                if (String(id).startsWith('-') || item.type === 'project') return true;
                                if (mode === "move") queuePersistMove(id);
                                return true;
                            });

                            // ====== Link handlers (ADD) ======
                            if (!this._linkEventsBound) {
                                this._linkEventsBound = true;
                                const lw = @this;

                                gantt.attachEvent("onBeforeLinkAdd", (id, link) => {
                                    const src = gantt.getTask(link.source),
                                        tgt = gantt.getTask(link.target);
                                    if (!src || !tgt) return false;
                                    if (src.type === "project" || tgt.type === "project") return false;
                                    if (String(link.source) === String(link.target)) return false;
                                    return true;
                                });
                                gantt.attachEvent("onAfterLinkAdd", (id, link) => {
                                    const payload = {
                                        source: Number(link.source),
                                        target: Number(link.target),
                                        type: String(link.type ?? "0")
                                    };
                                    lw.call('addLink', payload).catch((e) => {
                                        console.error("addLink failed:", e);
                                        gantt.deleteLink(id);
                                        gantt.message({
                                            type: "error",
                                            text: "Gagal menyimpan link"
                                        });
                                    });
                                    return true;
                                });
                                gantt.attachEvent("onAfterLinkDelete", (id, link) => {
                                    const payload = {
                                        source: Number(link.source),
                                        target: Number(link.target)
                                    };
                                    lw.call('deleteLink', payload).catch((e) => {
                                        console.error("deleteLink failed:", e);
                                        gantt.addLink(link);
                                        gantt.message({
                                            type: "error",
                                            text: "Gagal menghapus link"
                                        });
                                    });
                                    return true;
                                });
                                gantt.attachEvent("onAfterLinkUpdate", (id, link) => {
                                    const payload = {
                                        source: Number(link.source),
                                        target: Number(link.target),
                                        type: String(link.type ?? "0")
                                    };
                                    lw.call('updateLink', payload).catch((e) => {
                                        console.error("updateLink failed:", e);
                                        gantt.message({
                                            type: "error",
                                            text: "Gagal meng-update link"
                                        });
                                    });
                                    return true;
                                });
                            }

                            // Quick info buttons → Filament actions
                            const lwCtx = @this;
                            gantt.$click.buttons.edit_filament = function(taskId) {
                                lwCtx.call('openEditAction', {
                                    id: Number(taskId)
                                });
                                return false;
                            };
                            gantt.$click.buttons.delete_filament = function(taskId) {
                                lwCtx.call('openDeleteAction', {
                                    id: Number(taskId)
                                });
                                return false;
                            };
                        },

                        // === API kecil ===
                        applyScale(mode, renderNow = false) {
                            this.currentScale = mode;
                            if (mode === 'day') {
                                gantt.config.scales = [{
                                        unit: "month",
                                        step: 1,
                                        format: "%F %Y"
                                    },
                                    {
                                        unit: "day",
                                        step: 1,
                                        format: "%j %D"
                                    }
                                ];
                                gantt.config.min_column_width = 40;
                            } else {
                                gantt.config.scales = [{
                                        unit: "month",
                                        step: 1,
                                        format: "%F %Y"
                                    },
                                    {
                                        unit: "week",
                                        step: 1,
                                        format: (d) => gantt.date.date_to_str("W%W")(d)
                                    }
                                ];
                                gantt.config.min_column_width = 60;
                            }
                            if (renderNow && window.__gantt_inited__) gantt.render();
                        },
                        setScale(mode) {
                            this.applyScale(mode, true);
                        },

                        toggleResourcePanel() {
                            this._resourcePanelShown = !this._resourcePanelShown;
                            gantt.config.layout = this._resourcePanelShown ? this._resourceLayout : this._noResourceLayout;
                            gantt.init("gantt_here"); // seperti demo: re-init untuk apply layout
                            this.resourcesStore.clearAll();
                            this.resourcesStore.parse(this.payload.resources || []);
                            this.resourcesStore.refresh();
                            // reparse data agar sinkron (aman untuk v9)
                            gantt.clearAll();
                            gantt.parse({
                                data: this._filtered(this.payload.data || []),
                                links: this.payload.links || []
                            });
                            this.resourcesStore.refresh();
                            gantt.render();
                        },

                        reloadData() {
                            const lw = @this;
                            lw.getGanttPayload().then((payload) => {
                                this.payload = payload;
                                gantt.clearAll();
                                gantt.parse({
                                    data: this._filtered(payload.data || []),
                                    links: payload.links || []
                                });
                                this.resourcesStore.parse(payload.resources || []);
                                this.resourcesStore.refresh();
                                gantt.setSizes();
                            });
                        },

                        _filtered(data) {
                            const hasDate = (t) => !!(t.start_date && t.end_date);
                            return (data || []).filter(t => t.type === 'project' || hasDate(t));
                        },

                        refreshSizes() {
                            if (window.__gantt_inited__) gantt.setSizes();
                        },

                        // exportToExcel(){
                        //     gantt.exportToExcel({
                        //         name:"document.xlsx",
                        //     })
                        // },

                        // ==== Export ala demo: switch ke no-resource, export, lalu restore ====
                        exportPDF() {
                            const prevShown = this._resourcePanelShown;
                            const prevLayout = this._resourcePanelShown ? this._resourceLayout : this._noResourceLayout;

                            // 1) switch layout -> no resource
                            this._resourcePanelShown = false;
                            gantt.config.layout = this._noResourceLayout;
                            gantt.init("gantt_here");
                            this.resourcesStore.clearAll();
                            this.resourcesStore.parse(this.payload.resources || []);
                            this.resourcesStore.refresh();

                            // 2) Export
                            gantt.exportToPDF({
                                raw: false,
                                name: "schedule-resource.pdf",
                                header: @js("
                                    <div style='width:100%; border:1px solid black; border-collapse:collapse; font-family:Arial, sans-serif; font-size:14px;'>
                                        <div style='display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid black; padding:4px 8px;'>
                                            <span style='font-style:italic; font-weight:bold;'>PT JEMBO CABLE COMPANY Tbk.</span>
                                            <span style='font-weight:bold;'>PAGE : 1/2</span>
                                        </div>

                                        <div style='text-align:center; padding:16px 0;'>
                                            <div style='font-weight:bold; font-style:italic;'>MONTHLY PREVENTIVE MAINTENANCE SCHEDULE</div>
                                            <div style='font-weight:bold; font-style:italic;'>PRODUCTION MACHINE</div>
                                        </div>

                                        <div style='border-top:1px solid black;'></div>
                                    </div>
                                    "),
                                footer: @js("
                                    <div style='width:100%; border:1px solid black; font-family:Arial, sans-serif; font-size:14px;'>
                                        <!-- Top row: Copy (left) and Date/Place (right) -->
                                        <div style='display:flex; justify-content:space-between; align-items:flex-start; padding:6px 10px; border-bottom:1px solid #ccc;'>
                                            <div>
                                            <span style='font-style:italic;'>Copy</span>
                                            <span> : P1M, P2M, PIM, FILE.</span>
                                            </div>
                                            <div style='font-style:italic;'>Tangerang, {$this->exportDate}</div>
                                        </div>

                                        <!-- Bottom signatures -->
                                        <div style='display:flex; justify-content:space-between; padding:16px 24px 24px 24px;'>
                                            <!-- Left sign -->
                                            <div style='text-align:center; width:40%;'>
                                            <div style='height:40px;'></div>
                                            <div style='font-weight:bold; text-decoration:underline;'>SOFYAN</div>
                                            <div style='margin-top:2px;'>MTM</div>
                                            </div>

                                            <!-- Right sign -->
                                            <div style='text-align:center; width:40%;'>
                                            <div style='height:40px;'></div>
                                            <div style='font-weight:bold; text-decoration:underline;'>BAMBANG PP</div>
                                            <div style='margin-top:2px;'>MN</div>
                                            </div>
                                        </div>
                                    </div>
                                "),
                                additional_settings: {
                                    format: "A4",
                                    landscape: true,
                                    margins: {
                                        top: 5,
                                        bottom: 10,
                                        left: 10,
                                        right: 10
                                    },
                                }
                            });

                            // 3) Restore layout sebelumnya
                            this._resourcePanelShown = prevShown;
                            gantt.config.layout = prevLayout;
                            gantt.init("gantt_here");
                            this.resourcesStore.clearAll();
                            this.resourcesStore.parse(this.payload.resources || []);
                            this.resourcesStore.refresh();

                            // 4) Re-parse supaya tampilan balik utuh
                            gantt.clearAll();
                            gantt.parse({
                                data: this._filtered(this.payload.data || []),
                                links: this.payload.links || []
                            });
                            this.resourcesStore.refresh();
                            gantt.render();
                        },


                    }
                };
            </script>
        </div>
    </x-filament::card>
</x-filament-panels::page>
