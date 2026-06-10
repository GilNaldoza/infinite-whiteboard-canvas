<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $board?->name ?? 'New board' }} | Infinite Whiteboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/konva@9/konva.min.js"></script>
    <style>
        /* ── Reset & base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #e8ecf2;
            --glass:        rgba(255,255,255,0.82);
            --glass-border: rgba(180,196,220,0.5);
            --glass-shadow: 0 8px 32px rgba(18,38,72,0.12), 0 1px 4px rgba(18,38,72,0.06);
            --text:         #18243e;
            --muted:        #64748b;
            --accent:       #2563eb;
            --accent-dim:   rgba(37,99,235,0.12);
            --accent-ring:  rgba(37,99,235,0.3);
            --danger:       #dc2626;
            --danger-dim:   rgba(220,38,38,0.12);
            --success:      #16a34a;
            --r:            12px;
            --r-sm:         8px;
            --r-xs:         6px;
            --ease:         cubic-bezier(0.4,0,0.2,1);
            --t:            0.16s;
        }

        html, body {
            height: 100%; overflow: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            -webkit-font-smoothing: antialiased;
        }

        /* subtle dot-grid on canvas bg */
        body::before {
            content: '';
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background-image: radial-gradient(circle, rgba(140,160,200,.28) 1px, transparent 1px);
            background-size: 26px 26px;
        }

        #container { position: relative; z-index: 1; width: 100vw; height: 100vh; }

        /* ── Glassmorphism card ── */
        .glass {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--r);
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(18px) saturate(1.6);
            -webkit-backdrop-filter: blur(18px) saturate(1.6);
        }

        /* ── Panel entrance animation ── */
        .glass { animation: panelIn .3s cubic-bezier(.34,1.4,.64,1) both; }
        .tool-rail  { animation-delay: .06s; }
        .util-panel { animation-delay: .1s; }
        @keyframes panelIn {
            from { opacity:0; transform: translateY(-6px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* ═══════════════════════════════
           TOP BAR
        ═══════════════════════════════ */
        .topbar {
            position: fixed; top: 12px; left: 12px; right: 12px; z-index: 100;
            display: grid;
            grid-template-columns: minmax(190px, 260px) 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
        }

        /* Board name */
        .board-name-wrap { display: flex; align-items: center; gap: 8px; }
        .board-logo {
            flex-shrink: 0; width: 28px; height: 28px;
            background: linear-gradient(135deg,#2563eb,#7c3aed);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
        }
        #boardName {
            flex: 1; min-width: 0;
            height: 34px; padding: 0 10px;
            border: 1.5px solid transparent;
            border-radius: var(--r-xs);
            background: transparent;
            color: var(--text);
            font: 500 13px 'Inter', sans-serif;
            outline: none;
            transition: border-color var(--t) var(--ease), background var(--t) var(--ease);
        }
        #boardName:hover  { border-color: var(--glass-border); background: rgba(255,255,255,.5); }
        #boardName:focus  { border-color: var(--accent); background: #fff; }

        /* Centre style row */
        .style-row {
            display: flex; align-items: center; gap: 7px; justify-content: center; flex-wrap: wrap;
        }

        /* Divider */
        .sep { width: 1px; height: 20px; background: rgba(120,150,200,.22); flex-shrink: 0; }

        /* Color swatches */
        .swatches { display: flex; gap: 4px; align-items: center; }
        .swatch {
            width: 19px; height: 19px; border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: transform var(--t) var(--ease), box-shadow var(--t) var(--ease);
            position: relative;
        }
        .swatch:hover { transform: scale(1.2); box-shadow: 0 2px 8px rgba(0,0,0,.22); }
        .swatch.on { border-color: #fff; box-shadow: 0 0 0 2.5px var(--accent); transform: scale(1.12); }

        .color-wheel {
            width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
            background: conic-gradient(red,yellow,lime,cyan,blue,magenta,red);
            border: 2px solid var(--glass-border);
            cursor: pointer; overflow: hidden; position: relative;
            transition: transform var(--t) var(--ease);
        }
        .color-wheel:hover { transform: scale(1.14); }
        .color-wheel input[type="color"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        #colorPicker { display: none; }   /* real picker hidden; triggered by color-wheel */

        /* Stroke-width segmented control */
        .stroke-group { display: flex; gap: 2px; }
        .stroke-btn {
            display: flex; align-items: center; justify-content: center;
            width: 30px; height: 30px;
            border: 1.5px solid var(--glass-border);
            border-radius: var(--r-xs); background: transparent;
            cursor: pointer;
            transition: background var(--t) var(--ease), border-color var(--t) var(--ease);
        }
        .stroke-btn:first-child { border-radius: var(--r-xs) 0 0 var(--r-xs); }
        .stroke-btn:last-child  { border-radius: 0 var(--r-xs) var(--r-xs) 0; }
        .stroke-btn:not(:first-child) { margin-left: -1px; }
        .stroke-btn:hover { background: var(--accent-dim); border-color: var(--accent-ring); z-index: 1; }
        .stroke-btn.on { background: var(--accent-dim); border-color: var(--accent); z-index: 2; }
        /* real select hidden */
        #strokeWidth { display: none; }

        /* Generic pill button */
        .pill {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 5px; height: 30px; padding: 0 11px;
            border: 1.5px solid var(--glass-border);
            border-radius: var(--r-xs);
            background: transparent; color: var(--text);
            font: 500 12px 'Inter', sans-serif;
            cursor: pointer; white-space: nowrap; text-decoration: none;
            transition: background var(--t) var(--ease), border-color var(--t) var(--ease),
                        transform var(--t) var(--ease), color var(--t) var(--ease);
        }
        .pill:hover  { background: var(--accent-dim); border-color: var(--accent-ring); }
        .pill:active { transform: scale(.96); }
        .pill.on     { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
        .pill svg    { flex-shrink: 0; }

        /* Eraser size select (visible only when eraser is active) */
        #eraserSizePill {
            display: none;
            height: 30px; padding: 0 10px;
            border: 1.5px solid var(--danger);
            border-radius: var(--r-xs);
            background: var(--danger-dim);
            color: var(--danger);
            font: 500 12px 'Inter', sans-serif;
            cursor: pointer; outline: none;
        }
        #eraserSizePill option { background: #fff; color: var(--text); }

        /* Zoom badge */
        #zoomLabel {
            display: inline-flex; align-items: center;
            height: 28px; padding: 0 10px;
            border-radius: 999px;
            background: rgba(100,116,139,.1);
            color: var(--muted);
            font: 500 12px 'Inter', sans-serif;
            white-space: nowrap; cursor: default;
            transition: background var(--t) var(--ease), color var(--t) var(--ease);
        }
        #zoomLabel:hover { background: var(--accent-dim); color: var(--accent); }

        /* Status badge */
        #status {
            display: inline-flex; align-items: center; gap: 6px;
            height: 28px; padding: 0 10px;
            border-radius: 999px;
            font: 500 12px 'Inter', sans-serif;
            white-space: nowrap;
            transition: background .3s var(--ease), color .3s var(--ease);
        }
        #status::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
            transition: background .3s var(--ease);
        }
        #status[data-state="dirty"]  { background: #fef9e7; color: #92400e; }
        #status[data-state="dirty"]::before  { background: #f59e0b; }
        #status[data-state="saving"] { background: #eff6ff; color: #1d4ed8; }
        #status[data-state="saving"]::before { background: var(--accent); animation: blink 1s ease infinite; }
        #status[data-state="saved"]  { background: #f0fdf4; color: var(--success); }
        #status[data-state="saved"]::before  { background: #22c55e; }
        #status[data-state="error"]  { background: #fef2f2; color: var(--danger); }
        #status[data-state="error"]::before  { background: var(--danger); }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

        /* Save button */
        .btn-save {
            height: 30px; padding: 0 14px;
            border: none; border-radius: var(--r-xs);
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff; font: 600 12px 'Inter', sans-serif;
            cursor: pointer; white-space: nowrap;
            box-shadow: 0 2px 8px rgba(22,163,74,.35);
            transition: opacity var(--t) var(--ease), transform var(--t) var(--ease),
                        box-shadow var(--t) var(--ease);
        }
        .btn-save:hover   { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(22,163,74,.4); }
        .btn-save:active  { transform: scale(.96); }
        .btn-save:disabled { cursor: progress; opacity: .6; transform: none; }

        /* Status group */
        .status-group { display: flex; align-items: center; gap: 7px; justify-content: flex-end; }

        /* ═══════════════════════════════
           TOOL RAIL (left sidebar)
        ═══════════════════════════════ */
        .tool-rail {
            position: fixed; left: 12px; top: 74px; z-index: 100;
            display: flex; flex-direction: column; gap: 3px;
            padding: 8px;
        }

        .tool-btn {
            position: relative;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px;
            width: 52px; height: 52px;
            border: 1.5px solid transparent;
            border-radius: 10px;
            background: transparent; color: var(--muted);
            cursor: pointer;
            font: 500 10px 'Inter', sans-serif;
            transition: background var(--t) var(--ease), border-color var(--t) var(--ease),
                        color var(--t) var(--ease), transform var(--t) var(--ease);
        }
        .tool-btn svg { flex-shrink: 0; }
        .tool-btn span { line-height: 1; }
        .tool-btn:hover {
            background: var(--accent-dim); border-color: var(--accent-ring);
            color: var(--accent); transform: translateX(2px);
        }
        .tool-btn.active {
            background: var(--accent-dim); border-color: var(--accent);
            color: var(--accent);
        }
        /* Eraser active = red */
        .tool-btn[data-tool="eraser"].active {
            background: var(--danger-dim); border-color: var(--danger); color: var(--danger);
        }
        .tool-btn[data-tool="eraser"]:hover {
            background: var(--danger-dim); border-color: rgba(220,38,38,.3); color: var(--danger);
        }

        /* Tooltip on hover */
        .tool-btn::after {
            content: attr(data-tip);
            position: absolute; left: calc(100% + 10px); top: 50%;
            transform: translateY(-50%);
            background: rgba(15,23,42,.9); color: #fff;
            font: 500 11px 'Inter', sans-serif;
            padding: 5px 9px; border-radius: 6px;
            white-space: nowrap; pointer-events: none;
            opacity: 0; transition: opacity .15s ease; z-index: 200;
        }
        .tool-btn:hover::after { opacity: 1; }

        /* ═══════════════════════════════
           UTILITY PANEL (right sidebar)
        ═══════════════════════════════ */
        .util-panel {
            position: fixed; right: 12px; top: 74px; z-index: 100;
            display: flex; flex-direction: column; gap: 8px;
            padding: 10px 10px;
            min-width: 130px;
        }

        .util-section { display: flex; flex-direction: column; gap: 4px; }
        .util-section-title {
            font: 600 9.5px 'Inter', sans-serif;
            text-transform: uppercase; letter-spacing: .07em;
            color: var(--muted); padding: 0 2px;
        }
        .util-row { display: flex; gap: 4px; }

        .util-btn {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;
            height: 32px;
            border: 1.5px solid var(--glass-border);
            border-radius: var(--r-xs);
            background: transparent; color: var(--text);
            font: 500 11px 'Inter', sans-serif;
            cursor: pointer; white-space: nowrap;
            transition: background var(--t) var(--ease), border-color var(--t) var(--ease),
                        color var(--t) var(--ease), transform var(--t) var(--ease);
        }
        .util-btn:hover  { background: var(--accent-dim); border-color: var(--accent-ring); }
        .util-btn:active { transform: scale(.95); }
        .util-btn:disabled { opacity: .35; cursor: not-allowed; transform: none !important; }
        .util-btn.on { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
        .util-btn svg { flex-shrink: 0; }

        /* checkbox toggles styled as buttons */
        .util-toggle { display: none; }
        .util-toggle-lbl {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 4px;
            height: 32px;
            border: 1.5px solid var(--glass-border);
            border-radius: var(--r-xs);
            background: transparent; color: var(--text);
            font: 500 11px 'Inter', sans-serif;
            cursor: pointer; user-select: none;
            transition: background var(--t) var(--ease), border-color var(--t) var(--ease),
                        color var(--t) var(--ease);
        }
        .util-toggle-lbl:hover { background: var(--accent-dim); border-color: var(--accent-ring); }
        .util-toggle:checked + .util-toggle-lbl,
        .util-toggle-lbl.on {
            background: var(--accent-dim); border-color: var(--accent); color: var(--accent);
        }

        /* ═══════════════════════════════
           ERASER CURSOR BUBBLE
        ═══════════════════════════════ */
        .cursor-eraser { cursor: none !important; }
        #eraserCursor {
            position: fixed; pointer-events: none; z-index: 999;
            border-radius: 50%;
            border: 2px solid var(--danger);
            background: var(--danger-dim);
            transform: translate(-50%,-50%);
            display: none;
            transition: width .1s ease, height .1s ease;
        }

        /* ═══════════════════════════════
           RESPONSIVE
        ═══════════════════════════════ */
        @media (max-width: 760px) {
            .topbar {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .style-row   { justify-content: flex-start; overflow-x: auto; }
            .status-group { justify-content: flex-start; }
            .tool-rail {
                left: 12px; right: 12px;
                top: auto; bottom: 12px;
                flex-direction: row; overflow-x: auto;
            }
            .tool-btn { width: 46px; height: 46px; }
            .tool-btn::after { display: none; }
            .util-panel {
                left: 12px; right: 12px;
                top: auto; bottom: 80px;
                flex-direction: row; flex-wrap: wrap; overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<!-- ══════════════ TOP BAR ══════════════ -->
<div class="topbar glass">

    {{-- Board name --}}
    <div class="board-name-wrap">
        <div class="board-logo" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/>
            </svg>
        </div>
        <input id="boardName" type="text" placeholder="Board name"
               value="{{ $board?->name ?? $defaultName ?? 'Untitled board' }}"
               autocomplete="off" spellcheck="false">
    </div>

    {{-- Style controls (centre) --}}
    <div class="style-row">

        {{-- Color swatches --}}
        <div class="swatches" id="swatchRow">
            <div class="swatch on"  style="background:#2563eb" data-color="#2563eb" title="Blue"></div>
            <div class="swatch"     style="background:#dc2626" data-color="#dc2626" title="Red"></div>
            <div class="swatch"     style="background:#f97316" data-color="#f97316" title="Orange"></div>
            <div class="swatch"     style="background:#eab308" data-color="#eab308" title="Yellow"></div>
            <div class="swatch"     style="background:#16a34a" data-color="#16a34a" title="Green"></div>
            <div class="swatch"     style="background:#9333ea" data-color="#9333ea" title="Purple"></div>
            <div class="swatch"     style="background:#18243e" data-color="#18243e" title="Black"></div>
        </div>

        {{-- Custom colour picker --}}
        <div class="color-wheel" title="Custom colour">
            <input type="color" id="colorPicker" value="#2563eb" aria-label="Custom colour">
        </div>

        <div class="sep"></div>

        {{-- Stroke width --}}
        <div class="stroke-group">
            <button class="stroke-btn" data-w="2" title="Thin">
                <svg width="22" height="12" viewBox="0 0 22 12">
                    <line x1="2" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="stroke-btn on" data-w="4" title="Medium">
                <svg width="22" height="12" viewBox="0 0 22 12">
                    <line x1="2" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="stroke-btn" data-w="8" title="Thick">
                <svg width="22" height="12" viewBox="0 0 22 12">
                    <line x1="2" y1="6" x2="20" y2="6" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <select id="strokeWidth" aria-label="Stroke width">
            <option value="2">Thin</option>
            <option value="4" selected>Medium</option>
            <option value="8">Thick</option>
        </select>

        <div class="sep"></div>

        {{-- Eraser size (shown only when eraser active) --}}
        <select id="eraserSize" aria-label="Eraser size" title="Eraser size" style="display:none">
            <option value="16">Eraser S</option>
            <option value="32" selected>Eraser M</option>
            <option value="56">Eraser L</option>
        </select>

        {{-- View controls --}}
        <button class="pill" id="resetView" title="Reset pan & zoom">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
            </svg>
            Reset
        </button>

        <span id="zoomLabel">100%</span>
    </div>

    {{-- Status & save (right) --}}
    <div class="status-group">
        <span id="status" data-state="saved">Ready</span>
        <button class="btn-save" id="saveBoard">Save</button>
        <a href="{{ route('boards.index') }}" class="pill">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Boards
        </a>
    </div>
</div>

<!-- ══════════════ TOOL RAIL ══════════════ -->
<div class="tool-rail glass" aria-label="Drawing tools">

    <button class="tool-btn active" data-tool="select" data-tip="Select  (V)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3l14 9-7 1-4 7z"/></svg>
        <span>Select</span>
    </button>

    <button class="tool-btn" data-tool="freehand" data-tip="Pen  (P)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17c3.333-3.667 5-6.833 5-10a4 4 0 0 1 8 0c0 3-.667 5-2 7l6 6"/><path d="M17 21H7"/></svg>
        <span>Pen</span>
    </button>

    <button class="tool-btn" data-tool="rect" data-tip="Rectangle  (R)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
        <span>Rect</span>
    </button>

    <button class="tool-btn" data-tool="circle" data-tip="Ellipse  (C)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="10" ry="7"/></svg>
        <span>Circle</span>
    </button>

    <button class="tool-btn" data-tool="line" data-tip="Line  (L)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="19" x2="19" y2="5"/></svg>
        <span>Line</span>
    </button>

    <button class="tool-btn" data-tool="arrow" data-tip="Arrow  (A)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="19" x2="19" y2="5"/><polyline points="9 5 19 5 19 15"/></svg>
        <span>Arrow</span>
    </button>

    <button class="tool-btn" data-tool="text" data-tip="Text  (T)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
        <span>Text</span>
    </button>

    <button class="tool-btn" data-tool="eraser" data-tip="Eraser  (E)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20H7L3 16l13.293-13.293a1 1 0 0 1 1.414 0L20.707 6.12a1 1 0 0 1 0 1.415L11 17.5"/><line x1="6.5" y1="17.5" x2="20" y2="20"/></svg>
        <span>Eraser</span>
    </button>
</div>

<!-- ══════════════ UTILITY PANEL ══════════════ -->
<div class="util-panel glass" aria-label="Canvas utilities">

    <div class="util-section">
        <div class="util-section-title">History</div>
        <div class="util-row">
            <button class="util-btn" id="undoButton" title="Undo (Ctrl+Z)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                Undo
            </button>
            <button class="util-btn" id="redoButton" title="Redo (Ctrl+Y)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
                Redo
            </button>
        </div>
    </div>

    <div class="util-section">
        <div class="util-section-title">Selection</div>
        <div class="util-row">
            <button class="util-btn" id="duplicateButton" title="Duplicate (Ctrl+D)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Dupe
            </button>
            <button class="util-btn" id="exportButton" title="Export as PNG">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                PNG
            </button>
        </div>
        <div class="util-row">
            <button class="util-btn" id="bringForwardButton" title="Bring forward" disabled>↑ Fwd</button>
            <button class="util-btn" id="sendBackwardButton" title="Send backward" disabled>↓ Back</button>
        </div>
        <div class="util-row">
            <button class="util-btn" id="bringFrontButton" title="Bring to front" disabled>⤒ Front</button>
            <button class="util-btn" id="sendBackButton" title="Send to back" disabled>⤓ Back</button>
        </div>
        <div class="util-row">
            <button class="util-btn" id="fillToggle">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7"/><path d="m21 19-1.5-1.5L21 16l1.5 1.5z"/><path d="M18 21.5 16.5 20l1.5-1.5"/></svg>
                Fill Off
            </button>
        </div>
    </div>

    <div class="util-section">
        <div class="util-section-title">Canvas</div>
        <div class="util-row">
            <input type="checkbox" class="util-toggle" id="gridToggle">
            <label for="gridToggle" class="util-toggle-lbl" id="gridLabel">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                Grid
            </label>
            <input type="checkbox" class="util-toggle" id="snapToggle">
            <label for="snapToggle" class="util-toggle-lbl" id="snapLabel">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
                Snap
            </label>
        </div>
        <div class="util-row">
            <button class="util-btn" id="fitView" title="Fit content (F)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                Fit
            </button>
        </div>
    </div>
</div>

<!-- Eraser cursor bubble -->
<div id="eraserCursor"></div>

<div id="container"></div>

<script>
    /* ════════════════════════════════════════════════
       CONSTANTS & DOM REFS
    ════════════════════════════════════════════════ */
    const savedData    = @json($board?->canvas_data ?? null);
    let   boardId      = @json($board?->id ?? null);
    const apiBase      = '/api/boards';
    const csrfToken    = document.querySelector('meta[name="csrf-token"]').content;

    const boardName        = document.getElementById('boardName');
    const statusLabel      = document.getElementById('status');
    const zoomLabel        = document.getElementById('zoomLabel');
    const colorPicker      = document.getElementById('colorPicker');
    const strokeWidth      = document.getElementById('strokeWidth');
    const toolButtons      = document.querySelectorAll('[data-tool]');
    const saveButton       = document.getElementById('saveBoard');
    const undoButton       = document.getElementById('undoButton');
    const redoButton       = document.getElementById('redoButton');
    const duplicateButton  = document.getElementById('duplicateButton');
    const exportButton     = document.getElementById('exportButton');
    const bringForwardButton  = document.getElementById('bringForwardButton');
    const sendBackwardButton  = document.getElementById('sendBackwardButton');
    const bringFrontButton    = document.getElementById('bringFrontButton');
    const sendBackButton      = document.getElementById('sendBackButton');
    const fillToggle       = document.getElementById('fillToggle');
    const fitViewButton    = document.getElementById('fitView');
    const gridToggle       = document.getElementById('gridToggle');
    const snapToggle       = document.getElementById('snapToggle');
    const gridLabel        = document.getElementById('gridLabel');
    const snapLabel        = document.getElementById('snapLabel');
    const eraserSizeSelect = document.getElementById('eraserSize');
    const eraserCursor     = document.getElementById('eraserCursor');

    /* ════════════════════════════════════════════════
       STATE
    ════════════════════════════════════════════════ */
    let activeTool   = 'select';
    let eraserSize   = 32;
    let isErasing    = false;
    let isDrawing    = false;
    let isPanning    = false;
    let startPoint   = null;
    let currentShape = null;
    let dirty        = false;
    let saving       = false;
    let fillShapes   = false;
    let gridVisible  = false;
    let snapEnabled  = false;
    let lastSavedAt  = null;
    let suppressHistory = false;
    let textEditor   = null;
    let clipboard    = null;
    const history    = [];
    const redoHistory = [];
    const maxHistory = 60;
    const gridSize   = 40;

    /* ════════════════════════════════════════════════
       STATUS
    ════════════════════════════════════════════════ */
    function setStatus(message, state = 'saved') {
        statusLabel.textContent   = message;
        statusLabel.dataset.state = state;
    }

    function updateSavedAge() {
        if (dirty || saving || !lastSavedAt || statusLabel.dataset.state !== 'saved') return;
        const s = Math.max(0, Math.floor((Date.now() - lastSavedAt) / 1000));
        if (s < 5)  return setStatus('Saved just now', 'saved');
        if (s < 60) return setStatus(`Saved ${s}s ago`, 'saved');
        setStatus(`Saved ${Math.floor(s/60)}m ago`, 'saved');
    }

    /* ════════════════════════════════════════════════
       STAGE
    ════════════════════════════════════════════════ */
    function makeStage() {
        return new Konva.Stage({
            container: 'container',
            width: window.innerWidth, height: window.innerHeight,
            x: 0, y: 0, scaleX: 1, scaleY: 1,
        });
    }

    let stage = makeStage();
    if (savedData) {
        try {
            stage.destroy();
            stage = Konva.Node.create(JSON.parse(savedData), 'container');
            stage.width(window.innerWidth);
            stage.height(window.innerHeight);
        } catch (err) {
            console.error(err);
            stage = makeStage();
            setStatus('Could not load board data.', 'error');
        }
    }

    let layer = stage.findOne('.drawLayer');
    if (!layer) {
        layer = stage.findOne('Layer') || new Konva.Layer({ name: 'drawLayer' });
        layer.name('drawLayer');
        if (!layer.getStage()) stage.add(layer);
    }

    /* ── Background ── */
    function ensureBackground() {
        let bg = layer.findOne('.background');
        if (!bg) {
            bg = new Konva.Rect({
                name: 'background', x: -100000, y: -100000,
                width: 200000, height: 200000, fill: '#ffffff', listening: true,
            });
            layer.add(bg);
            bg.moveToBottom();
        }
        return bg;
    }

    const background = ensureBackground();

    /* ── Grid ── */
    const gridGroup = ensureGrid();
    function ensureGrid() {
        let g = layer.findOne('.gridGroup');
        if (!g) {
            g = new Konva.Group({ name: 'gridGroup', listening: false, visible: false });
            for (let v = -4000; v <= 4000; v += gridSize) {
                g.add(new Konva.Line({ name:'gridNode', points:[v,-4000,v,4000], stroke:'#dde4ef', strokeWidth: v===0?1.5:.6, listening:false }));
                g.add(new Konva.Line({ name:'gridNode', points:[-4000,v,4000,v], stroke:'#dde4ef', strokeWidth: v===0?1.5:.6, listening:false }));
            }
            layer.add(g);
            g.moveToBottom();
            background.moveToBottom();
        }
        return g;
    }

    /* ── Transformer ── */
    const transformer = new Konva.Transformer({
        name: 'selectionTransformer',
        rotateEnabled: true, ignoreStroke: true,
        borderStroke: '#2563eb', borderStrokeWidth: 1.5,
        anchorStroke: '#2563eb', anchorFill: '#fff', anchorSize: 9, anchorCornerRadius: 3,
        boundBoxFunc: (oldBox, newBox) => (newBox.width < 8 || newBox.height < 8) ? oldBox : newBox,
    });
    layer.add(transformer);
    layer.draw();

    /* ════════════════════════════════════════════════
       HISTORY
    ════════════════════════════════════════════════ */
    function drawableSnapshot() {
        return JSON.stringify(selectableNodes().map(n => n.toJSON()));
    }

    function restoreDrawableSnapshot(snapshot) {
        suppressHistory = true;
        transformer.nodes([]);
        selectableNodes().forEach(n => n.destroy());
        JSON.parse(snapshot).forEach(nodeJson => {
            const node = Konva.Node.create(JSON.parse(nodeJson));
            layer.add(node);
            enableShape(node);
        });
        background.moveToBottom();
        gridGroup.moveToBottom();
        background.moveToBottom();
        transformer.moveToTop();
        layer.draw();
        suppressHistory = false;
        updateSelectionControls();
        markDirty();
    }

    function pushHistory() {
        if (suppressHistory) return;
        const snap = drawableSnapshot();
        if (history[history.length - 1] === snap) return;
        history.push(snap);
        if (history.length > maxHistory) history.shift();
        redoHistory.length = 0;
        updateHistoryControls();
    }

    function undo() {
        if (history.length <= 1) return;
        redoHistory.push(history.pop());
        restoreDrawableSnapshot(history[history.length - 1]);
        updateHistoryControls();
    }

    function redo() {
        if (!redoHistory.length) return;
        const snap = redoHistory.pop();
        history.push(snap);
        restoreDrawableSnapshot(snap);
        updateHistoryControls();
    }

    function updateHistoryControls() {
        undoButton.disabled = history.length <= 1;
        redoButton.disabled = redoHistory.length === 0;
    }

    /* ════════════════════════════════════════════════
       DIRTY / SAVE
    ════════════════════════════════════════════════ */
    function markDirty() {
        dirty = true;
        if (!saving) setStatus('Unsaved', 'dirty');
    }

    /* ════════════════════════════════════════════════
       TOOLS & NODES
    ════════════════════════════════════════════════ */
    function setTool(tool) {
        activeTool = tool;
        transformer.nodes([]);
        toolButtons.forEach(b => b.classList.toggle('active', b.dataset.tool === tool));
        // Eraser size selector visibility
        eraserSizeSelect.style.display = tool === 'eraser' ? '' : 'none';
        updateCursor();
        layer.draw();
    }

    toolButtons.forEach(b => b.addEventListener('click', () => setTool(b.dataset.tool)));

    function pointerPosition() {
        const p = stage.getPointerPosition();
        if (!p) return { x:0, y:0 };
        return stage.getAbsoluteTransform().copy().invert().point(p);
    }

    function snapValue(v) { return snapEnabled ? Math.round(v / gridSize) * gridSize : v; }
    function snapPoint(p) { return { x: snapValue(p.x), y: snapValue(p.y) }; }

    function currentStyle() {
        return {
            stroke: colorPicker.value,
            fill: fillShapes ? colorPicker.value : transparentFill(colorPicker.value),
            strokeWidth: Number(strokeWidth.value),
        };
    }

    function isBackground(t) {
        return t === stage || t === background || t.hasName?.('background');
    }

    function selectableNodes() {
        return layer.children.filter(n =>
            !n.hasName('background') &&
            !n.hasName('selectionTransformer') &&
            !n.hasName('gridGroup') &&
            !n.hasName('gridNode')
        );
    }

    function selectNode(node, append = false) {
        if (!node || isBackground(node)) {
            transformer.nodes([]);
        } else if (append) {
            const nodes = transformer.nodes();
            const exists = nodes.includes(node);
            transformer.nodes(exists ? nodes.filter(x => x !== node) : [...nodes, node]);
        } else {
            transformer.nodes([node]);
        }
        syncStyleFromSelection();
        updateSelectionControls();
        layer.draw();
    }

    function updateSelectionControls() {
        const has = transformer.nodes().length > 0;
        [duplicateButton, bringForwardButton, sendBackwardButton, bringFrontButton, sendBackButton]
            .forEach(b => { b.disabled = !has; });
    }

    function syncStyleFromSelection() {
        const sel = transformer.nodes()[0];
        if (!sel) return;
        const stroke = sel.stroke?.() || sel.fill?.();
        if (stroke && /^#[0-9a-f]{6}$/i.test(stroke)) {
            colorPicker.value = stroke;
            syncSwatches(stroke);
        }
        if (sel.strokeWidth?.()) {
            strokeWidth.value = String(sel.strokeWidth());
            syncStrokeBtns(String(sel.strokeWidth()));
        }
    }

    /* ════════════════════════════════════════════════
       CURSOR
    ════════════════════════════════════════════════ */
    function updateCursor(cursor = null) {
        const c = stage.container();
        if (activeTool === 'eraser') {
            c.classList.add('cursor-eraser');
            eraserCursor.style.display = 'block';
            syncEraserBubble();
            return;
        }
        c.classList.remove('cursor-eraser');
        eraserCursor.style.display = 'none';
        if (cursor)                       { c.style.cursor = cursor; return; }
        if (activeTool === 'select')      { c.style.cursor = 'grab'; return; }
        c.style.cursor = activeTool === 'text' ? 'text' : 'crosshair';
    }

    function syncEraserBubble() {
        const d = eraserSize * stage.scaleX();
        eraserCursor.style.width  = `${d}px`;
        eraserCursor.style.height = `${d}px`;
    }

    document.addEventListener('mousemove', e => {
        if (activeTool !== 'eraser') return;
        eraserCursor.style.left = `${e.clientX}px`;
        eraserCursor.style.top  = `${e.clientY}px`;
        syncEraserBubble();
    });
    document.addEventListener('mouseleave', () => { eraserCursor.style.display = 'none'; });
    document.addEventListener('mouseenter', () => {
        if (activeTool === 'eraser') eraserCursor.style.display = 'block';
    });

    /* ════════════════════════════════════════════════
       SHAPES
    ════════════════════════════════════════════════ */
    function enableShape(node) {
        // Guard: only attach listeners once per node
        if (node._listenersAttached) {
            node.draggable(activeTool === 'select');
            return;
        }
        node._listenersAttached = true;
        node.draggable(activeTool === 'select');
        node.on('dragstart', () => { if (activeTool === 'select') selectNode(node); });
        node.on('dragmove', () => {
            if (!snapEnabled || node.getClassName() === 'Line' || node.getClassName() === 'Arrow') return;
            node.position(snapPoint(node.position()));
        });
        node.on('dragend transformend', () => { markDirty(); pushHistory(); });
        node.on('click tap', e => {
            if (activeTool === 'eraser') {
                // clicking a shape with eraser tool deletes it
                e.cancelBubble = true;
                node.destroy();
                transformer.nodes([]);
                markDirty();
                pushHistory();
                layer.draw();
                return;
            }
            if (activeTool !== 'select') return;
            e.cancelBubble = true;
            selectNode(node, e.evt?.shiftKey);
        });
        if (node.getClassName() === 'Text') node.on('dblclick dbltap', () => editText(node));
    }

    selectableNodes().forEach(enableShape);

    function applyShapeDragState() {
        selectableNodes().forEach(n => n.draggable(activeTool === 'select'));
    }

    /* ════════════════════════════════════════════════
       ZOOM
    ════════════════════════════════════════════════ */
    function updateZoomLabel() {
        zoomLabel.textContent = `${Math.round(stage.scaleX() * 100)}%`;
    }

    function resetView() {
        stage.position({ x:0, y:0 });
        stage.scale({ x:1, y:1 });
        updateZoomLabel();
        stage.batchDraw();
    }

    stage.on('wheel', e => {
        e.evt.preventDefault();
        const old = stage.scaleX();
        const ptr = stage.getPointerPosition();
        const to  = { x:(ptr.x - stage.x())/old, y:(ptr.y - stage.y())/old };
        const dir = e.evt.deltaY > 0 ? -1 : 1;
        const next = Math.max(0.15, Math.min(5, old * (dir > 0 ? 1.08 : 1/1.08)));
        stage.scale({ x:next, y:next });
        stage.position({ x: ptr.x - to.x * next, y: ptr.y - to.y * next });
        updateZoomLabel();
        stage.batchDraw();
    });

    /* ════════════════════════════════════════════════
       ERASER
    ════════════════════════════════════════════════ */
    function eraseAtPoint(point) {
        const r = eraserSize / 2;
        // snapshot to avoid mutating the live array while iterating
        const nodes = selectableNodes().slice();
        let erased = false;
        for (const node of nodes) {
            if (node.hasName('selectionTransformer') || !node.getParent()) continue;
            const rect = node.getClientRect({ relativeTo: layer });
            if (point.x >= rect.x - r && point.x <= rect.x + rect.width  + r &&
                point.y >= rect.y - r && point.y <= rect.y + rect.height + r) {
                node.destroy();
                erased = true;
            }
        }
        if (erased) markDirty();
        transformer.nodes([]);
        layer.batchDraw();
    }

    /* ════════════════════════════════════════════════
       POINTER EVENTS
    ════════════════════════════════════════════════ */
    stage.on('contentMousedown contentTouchstart', e => {
        closeTextEditor();
        const point = snapPoint(pointerPosition());
        startPoint = point;
        applyShapeDragState();

        if (activeTool === 'eraser') {
            isErasing = true;
            eraseAtPoint(point);
            return;
        }

        if (activeTool === 'select') {
            if (isBackground(e.target)) {
                selectNode(null);
                isPanning = true;
                updateCursor('grabbing');
                stage.draggable(true);
                stage.startDrag();
            }
            return;
        }

        if (!isBackground(e.target)) return;

        isDrawing = true;
        const s = currentStyle();

        if (activeTool === 'freehand') {
            currentShape = new Konva.Line({
                points: [point.x, point.y],
                stroke: s.stroke, strokeWidth: s.strokeWidth,
                lineCap: 'round', lineJoin: 'round', tension: 0.35, draggable: false,
            });
        }
        if (activeTool === 'rect') {
            currentShape = new Konva.Rect({
                x: point.x, y: point.y, width:1, height:1,
                stroke: s.stroke, strokeWidth: s.strokeWidth,
                fill: s.fill, cornerRadius: 3, draggable: false,
            });
        }
        if (activeTool === 'circle') {
            currentShape = new Konva.Ellipse({
                x: point.x, y: point.y, radiusX:1, radiusY:1,
                stroke: s.stroke, strokeWidth: s.strokeWidth, fill: s.fill, draggable: false,
            });
        }
        if (activeTool === 'line') {
            currentShape = new Konva.Line({
                points: [point.x, point.y, point.x, point.y],
                stroke: s.stroke, strokeWidth: s.strokeWidth, lineCap: 'round', draggable: false,
            });
        }
        if (activeTool === 'arrow') {
            currentShape = new Konva.Arrow({
                points: [point.x, point.y, point.x, point.y],
                stroke: s.stroke, fill: s.stroke, strokeWidth: s.strokeWidth,
                pointerLength:14, pointerWidth:14, lineCap:'round', draggable:false,
            });
        }
        if (activeTool === 'text') {
            currentShape = new Konva.Text({
                x: point.x, y: point.y, text: 'Text', fill: s.stroke,
                fontSize: 24, fontFamily: "'Inter', system-ui, sans-serif", draggable: false,
            });
            layer.add(currentShape);
            enableShape(currentShape);
            selectNode(currentShape);
            editText(currentShape);
            markDirty();
            pushHistory();
            isDrawing = false;
            currentShape = null;
            return;
        }

        if (currentShape) { layer.add(currentShape); layer.draw(); }
    });

    stage.on('contentMousemove contentTouchmove', () => {
        if (activeTool === 'eraser' && isErasing) { eraseAtPoint(pointerPosition()); return; }
        if (!isDrawing || !currentShape) return;

        const point = activeTool === 'freehand' ? pointerPosition() : snapPoint(pointerPosition());

        if (activeTool === 'freehand') currentShape.points([...currentShape.points(), point.x, point.y]);
        if (activeTool === 'rect') currentShape.setAttrs({
            x: Math.min(startPoint.x, point.x), y: Math.min(startPoint.y, point.y),
            width: Math.abs(point.x - startPoint.x), height: Math.abs(point.y - startPoint.y),
        });
        if (activeTool === 'circle') currentShape.setAttrs({
            x: (startPoint.x + point.x)/2, y: (startPoint.y + point.y)/2,
            radiusX: Math.abs(point.x - startPoint.x)/2,
            radiusY: Math.abs(point.y - startPoint.y)/2,
        });
        if (activeTool === 'line' || activeTool === 'arrow')
            currentShape.points([startPoint.x, startPoint.y, point.x, point.y]);

        layer.batchDraw();
    });

    function finalizeDrawing() {
        if (isErasing) {
            isErasing = false;
            pushHistory();
            return;
        }
        if (isPanning) { isPanning = false; stage.draggable(false); updateCursor(); return; }
        if (!isDrawing || !currentShape) return;

        const shape = currentShape;
        currentShape = null;
        isDrawing = false;

        if (shapeTooSmall(shape)) { shape.destroy(); layer.draw(); return; }

        enableShape(shape);

        // Shape tools: select the new shape and switch back to Select
        // Freehand & text: stay on current tool so user can keep drawing
        const stayOnTool = activeTool === 'freehand';
        if (stayOnTool) {
            // deselect but keep the tool active
            transformer.nodes([]);
            applyShapeDragState();
            layer.draw();
        } else {
            selectNode(shape);
            setTool('select');
        }
        markDirty();
        pushHistory();
    }

    stage.on('contentMouseup contentTouchend', finalizeDrawing);
    window.addEventListener('mouseup',   finalizeDrawing);
    window.addEventListener('touchend',  finalizeDrawing);

    /* ════════════════════════════════════════════════
       KEYBOARD
    ════════════════════════════════════════════════ */
    document.addEventListener('keydown', e => {
        if (textEditor) return;
        const target = e.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') return;
        const key = e.key.toLowerCase();

        if ((e.ctrlKey || e.metaKey) && key === 'z') { e.preventDefault(); undo(); return; }
        if ((e.ctrlKey || e.metaKey) && (key === 'y' || (e.shiftKey && key === 'z'))) { e.preventDefault(); redo(); return; }
        if ((e.ctrlKey || e.metaKey) && key === 's') { e.preventDefault(); saveBoard(false); return; }
        if ((e.ctrlKey || e.metaKey) && key === 'c') { e.preventDefault(); copySelection(); return; }
        if ((e.ctrlKey || e.metaKey) && key === 'v') { e.preventDefault(); pasteSelection(); return; }
        if ((e.ctrlKey || e.metaKey) && key === 'd') { e.preventDefault(); duplicateSelection(); return; }

        const shortcuts = { v:'select', p:'freehand', r:'rect', c:'circle', l:'line', a:'arrow', t:'text' };
        if (!e.ctrlKey && !e.metaKey && shortcuts[key]) { setTool(shortcuts[key]); return; }
        if (!e.ctrlKey && !e.metaKey && key === 'e') { setTool(activeTool === 'eraser' ? 'select' : 'eraser'); return; }
        if (key === 'f') { fitToContent(); return; }
        if (key === 'escape') { setTool('select'); return; }
        if (key === 'home') { resetView(); return; }

        if (e.key !== 'Delete' && e.key !== 'Backspace') return;
        const selected = transformer.nodes();
        if (!selected.length) return;
        e.preventDefault();
        selected.forEach(n => n.destroy());
        transformer.nodes([]);
        markDirty();
        pushHistory();
        layer.draw();
        updateSelectionControls();
    });

    /* ════════════════════════════════════════════════
       COLOUR SWATCHES & STROKE BUTTONS
    ════════════════════════════════════════════════ */
    function syncSwatches(color) {
        document.querySelectorAll('.swatch').forEach(s =>
            s.classList.toggle('on', s.dataset.color.toLowerCase() === color.toLowerCase())
        );
    }

    document.querySelectorAll('.swatch').forEach(s => s.addEventListener('click', () => {
        colorPicker.value = s.dataset.color;
        syncSwatches(s.dataset.color);
        applyStyleToSelection();
    }));

    // Clicking the colour-wheel triggers the hidden native picker
    document.querySelector('.color-wheel').addEventListener('click', e => {
        // the input itself handles the picker; just prevent double-fire
    });
    colorPicker.addEventListener('input', e => {
        syncSwatches(e.target.value);
        applyStyleToSelection();
    });

    function syncStrokeBtns(w) {
        document.querySelectorAll('.stroke-btn').forEach(b =>
            b.classList.toggle('on', b.dataset.w === w)
        );
    }

    document.querySelectorAll('.stroke-btn').forEach(b => b.addEventListener('click', () => {
        strokeWidth.value = b.dataset.w;
        syncStrokeBtns(b.dataset.w);
        applyStyleToSelection();
    }));

    strokeWidth.addEventListener('change', applyStyleToSelection);

    function applyStyleToSelection() {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        const s = currentStyle();
        nodes.forEach(node => {
            if (node.stroke && node.getClassName() !== 'Text') {
                node.stroke(s.stroke);
                node.strokeWidth(s.strokeWidth);
            }
            if (node.getClassName() === 'Rect' || node.getClassName() === 'Ellipse') node.fill(s.fill);
            if (node.getClassName() === 'Text')  node.fill(s.stroke);
            if (node.getClassName() === 'Arrow') node.fill(s.stroke);
        });
        markDirty();
        pushHistory();
        layer.batchDraw();
    }

    /* ════════════════════════════════════════════════
       CLIPBOARD & SELECTION OPERATIONS
    ════════════════════════════════════════════════ */
    function duplicateSelection() {
        const clones = transformer.nodes().map(node => {
            const clone = node.clone({ x: node.x()+24, y: node.y()+24 });
            layer.add(clone); enableShape(clone);
            return clone;
        });
        if (!clones.length) return;
        transformer.nodes(clones); transformer.moveToTop();
        markDirty(); pushHistory(); updateSelectionControls(); layer.draw();
    }

    function copySelection() {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        clipboard = nodes.map(n => n.toJSON());
    }

    function pasteSelection() {
        if (!clipboard?.length) return;
        const clones = clipboard.map(json => {
            const node = Konva.Node.create(JSON.parse(json));
            node.position({ x: node.x()+28, y: node.y()+28 });
            layer.add(node); enableShape(node);
            return node;
        });
        clipboard = clones.map(n => n.toJSON());
        transformer.nodes(clones); transformer.moveToTop();
        markDirty(); pushHistory(); updateSelectionControls(); layer.draw();
    }

    function moveSelection(dir) {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        nodes.forEach(node => {
            if (dir === 'up')     node.moveUp();
            if (dir === 'down')   { node.moveDown(); background.moveToBottom(); gridGroup.moveToBottom(); background.moveToBottom(); }
            if (dir === 'top')    node.moveToTop();
            if (dir === 'bottom') { node.moveToBottom(); background.moveToBottom(); gridGroup.moveToBottom(); background.moveToBottom(); }
        });
        transformer.moveToTop();
        markDirty(); pushHistory(); layer.draw();
    }

    function fitToContent() {
        const nodes = selectableNodes();
        if (!nodes.length) { resetView(); return; }
        const box = nodes.reduce((b, node) => {
            const r = node.getClientRect({ relativeTo: layer });
            return { x: Math.min(b.x,r.x), y: Math.min(b.y,r.y), right: Math.max(b.right,r.x+r.width), bottom: Math.max(b.bottom,r.y+r.height) };
        }, { x:Infinity, y:Infinity, right:-Infinity, bottom:-Infinity });
        const pad=120, w=Math.max(1,box.right-box.x), h=Math.max(1,box.bottom-box.y);
        const scale = Math.max(0.15, Math.min(5, Math.min((stage.width()-pad)/w, (stage.height()-pad)/h)));
        stage.scale({ x:scale, y:scale });
        stage.position({ x: stage.width()/2 - (box.x+w/2)*scale, y: stage.height()/2 - (box.y+h/2)*scale });
        updateZoomLabel(); stage.batchDraw();
    }

    function exportPng() {
        const selected = transformer.nodes();
        transformer.nodes([]); layer.draw();
        const uri = stage.toDataURL({ pixelRatio:2 });
        transformer.nodes(selected); layer.draw();
        const a = document.createElement('a');
        a.download = `${(boardName.value.trim() || 'whiteboard').replace(/[^a-z0-9-_]+/gi,'-')}.png`;
        a.href = uri;
        document.body.appendChild(a); a.click(); a.remove();
    }

    /* ════════════════════════════════════════════════
       EVENT LISTENERS — CONTROLS
    ════════════════════════════════════════════════ */
    boardName.addEventListener('input', markDirty);
    document.getElementById('resetView').addEventListener('click', resetView);
    saveButton.addEventListener('click', () => saveBoard(false));

    // Keep stroke-btn visual state in sync whenever the hidden select changes
    strokeWidth.addEventListener('change', () => syncStrokeBtns(strokeWidth.value));
    undoButton.addEventListener('click', undo);
    redoButton.addEventListener('click', redo);
    duplicateButton.addEventListener('click', duplicateSelection);
    exportButton.addEventListener('click', exportPng);
    fitViewButton.addEventListener('click', fitToContent);
    bringForwardButton.addEventListener('click',  () => moveSelection('up'));
    sendBackwardButton.addEventListener('click',  () => moveSelection('down'));
    bringFrontButton.addEventListener('click',    () => moveSelection('top'));
    sendBackButton.addEventListener('click',      () => moveSelection('bottom'));

    fillToggle.addEventListener('click', () => {
        fillShapes = !fillShapes;
        fillToggle.innerHTML = fillShapes
            ? `<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/></svg> Fill On`
            : `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/></svg> Fill Off`;
        fillToggle.classList.toggle('on', fillShapes);
        applyStyleToSelection();
    });

    gridToggle.addEventListener('change', () => {
        gridVisible = gridToggle.checked;
        gridGroup.visible(gridVisible);
        gridLabel.classList.toggle('on', gridVisible);
        layer.batchDraw();
    });

    snapToggle.addEventListener('change', () => {
        snapEnabled = snapToggle.checked;
        snapLabel.classList.toggle('on', snapEnabled);
    });

    eraserSizeSelect.addEventListener('change', () => {
        eraserSize = Number(eraserSizeSelect.value);
        syncEraserBubble();
    });

    /* ════════════════════════════════════════════════
       HELPERS
    ════════════════════════════════════════════════ */
    function transparentFill(hex) {
        const v = hex.replace('#','');
        const n = parseInt(v, 16);
        return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},0.16)`;
    }

    function shapeTooSmall(shape) {
        const cn = shape.getClassName();
        if (cn === 'Line' || cn === 'Arrow') { const p=shape.points(); return Math.abs(p[0]-p[2])<3 && Math.abs(p[1]-p[3])<3; }
        if (cn === 'Rect')    return shape.width()<3 || shape.height()<3;
        if (cn === 'Ellipse') return shape.radiusX()<2 || shape.radiusY()<2;
        return false;
    }

    function stageJson() {
        transformer.nodes([]);
        const clone = stage.clone();
        clone.find('.selectionTransformer').forEach(n => n.destroy());
        clone.find('.gridGroup').forEach(n => n.destroy());
        clone.find('.gridNode').forEach(n => n.destroy());
        clone.find('.background').forEach(n => n.destroy());
        return clone.toJSON();
    }

    /* ════════════════════════════════════════════════
       SAVE
    ════════════════════════════════════════════════ */
    async function saveBoard(auto = false) {
        const name = boardName.value.trim();
        if (!name) { setStatus('Board name is required.', 'error'); return; }
        if (saving) return;
        saving = true;
        saveButton.disabled = true;
        setStatus(auto ? 'Auto-saving…' : 'Saving…', 'saving');
        const payload = { name, canvas_data: stageJson() };

        try {
            const res = await fetch(boardId ? `${apiBase}/${boardId}` : apiBase, {
                method: boardId ? 'PUT' : 'POST',
                headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrfToken },
                body: JSON.stringify(payload),
            });
            const data = res.status === 204 ? {} : await res.json().catch(()=>({}));
            if (!res.ok) throw new Error(data.message || Object.values(data.errors||{})[0]?.[0] || 'Save failed.');
            boardId = data.id || boardId;
            dirty = false;
            lastSavedAt = Date.now();
            setStatus(auto ? 'Auto-saved' : 'Saved just now', 'saved');
            if (boardId && !window.location.pathname.endsWith(`/boards/${boardId}`))
                window.history.replaceState({}, '', `/boards/${boardId}`);
        } catch (err) {
            console.error(err);
            setStatus(err.message || 'Save failed', 'error');
        } finally {
            saving = false;
            saveButton.disabled = false;
        }
    }

    /* ════════════════════════════════════════════════
       TEXT EDITOR
    ════════════════════════════════════════════════ */
    function editText(textNode) {
        closeTextEditor();
        transformer.nodes([]);
        textNode.hide();
        layer.draw();

        const pos  = textNode.absolutePosition();
        const cRect = stage.container().getBoundingClientRect();

        textEditor = document.createElement('textarea');
        document.body.appendChild(textEditor);
        textEditor.value = textNode.text();
        Object.assign(textEditor.style, {
            position: 'absolute',
            top:        `${cRect.top  + pos.y}px`,
            left:       `${cRect.left + pos.x}px`,
            width:      `${Math.max(textNode.width(), 180)}px`,
            minHeight:  '40px',
            fontSize:   `${textNode.fontSize() * stage.scaleX()}px`,
            fontFamily: textNode.fontFamily(),
            color:      textNode.fill(),
            border:     '2px solid #2563eb',
            borderRadius:'8px',
            padding:    '6px 10px',
            background: '#fff',
            zIndex:     '200',
            outline:    'none',
            resize:     'none',
            lineHeight: '1.5',
            boxShadow:  '0 4px 20px rgba(37,99,235,.22)',
        });
        textEditor.focus();
        textEditor.select();

        textEditor.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); closeTextEditor(true); }
            if (e.key === 'Escape') closeTextEditor(false);
        });
        textEditor.addEventListener('blur', () => closeTextEditor(true));
        textEditor._node = textNode;
    }

    function closeTextEditor(commit = true) {
        if (!textEditor) return;
        const node = textEditor._node;
        if (commit) { node.text(textEditor.value.trim() || 'Text'); markDirty(); pushHistory(); }
        node.show();
        textEditor.remove();
        textEditor = null;
        selectNode(node);
        layer.draw();
    }

    /* ════════════════════════════════════════════════
       RESIZE / INTERVALS
    ════════════════════════════════════════════════ */
    window.addEventListener('resize', () => {
        stage.width(window.innerWidth);
        stage.height(window.innerHeight);
        stage.batchDraw();
    });

    setInterval(() => { if (dirty) saveBoard(true); }, 60000);
    setInterval(updateSavedAge, 5000);

    /* ── Init ── */
    updateZoomLabel();
    setTool('select');
    pushHistory();
    updateSelectionControls();
</script>
</body>
</html>
