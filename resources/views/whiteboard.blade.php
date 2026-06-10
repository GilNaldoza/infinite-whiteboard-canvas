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
        /* â”€â”€ Reset & base â”€â”€ */
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

        /* â”€â”€ Glassmorphism card â”€â”€ */
        .glass {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: var(--r);
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(18px) saturate(1.6);
            -webkit-backdrop-filter: blur(18px) saturate(1.6);
        }

        /* â”€â”€ Panel entrance animation â”€â”€ */
        .glass { animation: panelIn .3s cubic-bezier(.34,1.4,.64,1) both; }
        .tool-rail  { animation-delay: .06s; }
        .util-panel { animation-delay: .1s; }
        @keyframes panelIn {
            from { opacity:0; transform: translateY(-6px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           TOP BAR
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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
        /* NOTE: colorPicker is the input inside .color-wheel â€“ do NOT set display:none */

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

        /* Eraser size select â€” shown only when eraser tool is active */
        #eraserSize {
            height: 30px; padding: 0 10px;
            border: 1.5px solid var(--danger);
            border-radius: var(--r-xs);
            background: var(--danger-dim);
            color: var(--danger);
            font: 500 12px 'Inter', sans-serif;
            cursor: pointer; outline: none;
            display: none;
        }
        #eraserSize option { background: #fff; color: var(--text); }

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

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           TOOL RAIL (left sidebar)
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           UTILITY PANEL (right sidebar)
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           ERASER CURSOR BUBBLE
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

        /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
           RESPONSIVE
        â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
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

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• TOP BAR â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• TOOL RAIL â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â• UTILITY PANEL â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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
            <button class="util-btn" id="bringForwardButton" title="Bring forward" disabled>â†‘ Fwd</button>
            <button class="util-btn" id="sendBackwardButton" title="Send backward" disabled>â†“ Back</button>
        </div>
        <div class="util-row">
            <button class="util-btn" id="bringFrontButton" title="Bring to front" disabled>â¤’ Front</button>
            <button class="util-btn" id="sendBackButton" title="Send to back" disabled>â¤“ Back</button>
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
'use strict';
/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   CONSTANTS & DOM
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
const savedData    = @json($board?->canvas_data ?? null);
let   boardId      = @json($board?->id ?? null);
const API          = '/api/boards';
const CSRF         = document.querySelector('meta[name="csrf-token"]').content;

const elBoardName   = document.getElementById('boardName');
const elStatus      = document.getElementById('status');
const elZoom        = document.getElementById('zoomLabel');
const elColorPicker = document.getElementById('colorPicker');
const elStrokeWidth = document.getElementById('strokeWidth');   // hidden <select>
const elEraserSize  = document.getElementById('eraserSize');
const elEraserCursor= document.getElementById('eraserCursor');
const elSaveBtn     = document.getElementById('saveBoard');
const elUndoBtn     = document.getElementById('undoButton');
const elRedoBtn     = document.getElementById('redoButton');
const elDupeBtn     = document.getElementById('duplicateButton');
const elExportBtn   = document.getElementById('exportButton');
const elFwdBtn      = document.getElementById('bringForwardButton');
const elBkBtn       = document.getElementById('sendBackwardButton');
const elFrontBtn    = document.getElementById('bringFrontButton');
const elBackBtn     = document.getElementById('sendBackButton');
const elFillToggle  = document.getElementById('fillToggle');
const elFitBtn      = document.getElementById('fitView');
const elGridToggle  = document.getElementById('gridToggle');
const elSnapToggle  = document.getElementById('snapToggle');
const elGridLabel   = document.getElementById('gridLabel');
const elSnapLabel   = document.getElementById('snapLabel');
const elResetView   = document.getElementById('resetView');
const toolBtns      = document.querySelectorAll('[data-tool]');

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   STATE
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
let activeTool   = 'select';
let eraserSize   = 32;
let isDrawing    = false;
let isPanning    = false;
let isErasing    = false;
let startPt      = null;
let curShape     = null;
let dirty        = false;
let saving       = false;
let fillShapes   = false;
let gridVisible  = false;
let snapEnabled  = false;
let lastSaveTime = null;
let noHistory    = false;   // suppress during undo/redo restores
let textEl       = null;    // active textarea overlay
let clipboard    = null;
const history    = [];
const future     = [];
const HISTORY_MAX= 60;
const GRID_SIZE  = 40;

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   STATUS HELPERS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function setStatus(msg, state = 'saved') {
    elStatus.textContent   = msg;
    elStatus.dataset.state = state;
}
function tickAge() {
    if (dirty || saving || !lastSaveTime || elStatus.dataset.state !== 'saved') return;
    const s = Math.floor((Date.now() - lastSaveTime) / 1000);
    if (s < 5)  return setStatus('Saved just now', 'saved');
    if (s < 60) return setStatus(`Saved ${s}s ago`, 'saved');
    setStatus(`Saved ${Math.floor(s/60)}m ago`, 'saved');
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   STAGE SETUP
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function makeStage() {
    return new Konva.Stage({ container:'container', width:innerWidth, height:innerHeight });
}

let stage = makeStage();
if (savedData) {
    try {
        stage.destroy();
        stage = Konva.Node.create(JSON.parse(savedData), 'container');
        stage.width(innerWidth);
        stage.height(innerHeight);
    } catch(e) {
        console.error('Load error:', e);
        stage = makeStage();
        setStatus('Could not load board data.', 'error');
    }
}

// Find or create the draw layer
let layer = stage.findOne('.drawLayer');
if (!layer) {
    layer = stage.findOne('Layer') || new Konva.Layer({ name:'drawLayer' });
    layer.name('drawLayer');
    if (!layer.getStage()) stage.add(layer);
}

// â”€â”€ White background rect (fills infinite canvas)
let bgRect = layer.findOne('.background');
if (!bgRect) {
    bgRect = new Konva.Rect({
        name:'background',
        x:-100000, y:-100000,
        width:200000, height:200000,
        fill:'#ffffff', listening:true,
    });
    layer.add(bgRect);
    bgRect.moveToBottom();
}

// â”€â”€ Grid group
let gridGroup = layer.findOne('.gridGroup');
if (!gridGroup) {
    gridGroup = new Konva.Group({ name:'gridGroup', listening:false, visible:false });
    for (let v = -4000; v <= 4000; v += GRID_SIZE) {
        gridGroup.add(new Konva.Line({ name:'gridNode', points:[v,-4000,v,4000],   stroke:'#dde4ef', strokeWidth: v===0 ? 1.5 : 0.6, listening:false }));
        gridGroup.add(new Konva.Line({ name:'gridNode', points:[-4000,v,4000,v],   stroke:'#dde4ef', strokeWidth: v===0 ? 1.5 : 0.6, listening:false }));
    }
    layer.add(gridGroup);
    gridGroup.moveToBottom();
    bgRect.moveToBottom();
}

// â”€â”€ Transformer
const tr = new Konva.Transformer({
    name: 'selectionTransformer',
    rotateEnabled: true, ignoreStroke: true,
    borderStroke:'#2563eb', borderStrokeWidth:1.5,
    anchorStroke:'#2563eb', anchorFill:'#fff', anchorSize:9, anchorCornerRadius:3,
    boundBoxFunc:(o,n) => (n.width<8||n.height<8) ? o : n,
});
layer.add(tr);
layer.draw();

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   HISTORY
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function snapshot() {
    return JSON.stringify(drawableNodes().map(n => n.toJSON()));
}

function pushHistory() {
    if (noHistory) return;
    const snap = snapshot();
    if (history.length && history[history.length-1] === snap) return;
    history.push(snap);
    if (history.length > HISTORY_MAX) history.shift();
    future.length = 0;
    refreshHistoryBtns();
}

function applySnapshot(snap) {
    noHistory = true;
    tr.nodes([]);
    drawableNodes().forEach(n => n.destroy());
    JSON.parse(snap).forEach(json => {
        const node = Konva.Node.create(JSON.parse(json));
        layer.add(node);
        wireShape(node);
    });
    bgRect.moveToBottom();
    gridGroup.moveToBottom();
    bgRect.moveToBottom();
    tr.moveToTop();
    layer.draw();
    noHistory = false;
    markDirty();
    refreshSelBtns();
}

function undo() {
    if (history.length <= 1) return;
    future.push(history.pop());
    applySnapshot(history[history.length-1]);
    refreshHistoryBtns();
}
function redo() {
    if (!future.length) return;
    const snap = future.pop();
    history.push(snap);
    applySnapshot(snap);
    refreshHistoryBtns();
}
function refreshHistoryBtns() {
    elUndoBtn.disabled = history.length <= 1;
    elRedoBtn.disabled = future.length === 0;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   DIRTY / MARK
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function markDirty() {
    dirty = true;
    if (!saving) setStatus('Unsaved', 'dirty');
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   DRAWABLE NODES
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function drawableNodes() {
    return layer.children.filter(n =>
        !n.hasName('background') &&
        !n.hasName('selectionTransformer') &&
        !n.hasName('gridGroup') &&
        !n.hasName('gridNode')
    );
}

function isOnBackground(target) {
    return !target ||
           target === stage ||
           target === bgRect ||
           target.hasName?.('background') ||
           target.hasName?.('gridGroup') ||
           target.hasName?.('gridNode');
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SELECTION
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function selectNode(node, append = false) {
    if (!node || isOnBackground(node)) {
        tr.nodes([]);
    } else if (append) {
        const cur = tr.nodes();
        tr.nodes(cur.includes(node) ? cur.filter(x=>x!==node) : [...cur, node]);
    } else {
        tr.nodes([node]);
    }
    syncStyleFromSel();
    refreshSelBtns();
    layer.draw();
}

function refreshSelBtns() {
    const has = tr.nodes().length > 0;
    [elDupeBtn, elFwdBtn, elBkBtn, elFrontBtn, elBackBtn].forEach(b => { b.disabled = !has; });
}

function syncStyleFromSel() {
    const sel = tr.nodes()[0];
    if (!sel) return;
    // Sync color
    const color = (sel.getClassName()==='Text') ? sel.fill() : (sel.stroke?.() || sel.fill?.());
    if (color && /^#[0-9a-f]{6}$/i.test(color)) {
        elColorPicker.value = color;
        highlightSwatch(color);
    }
    // Sync stroke width
    const sw = sel.strokeWidth?.();
    if (sw) {
        elStrokeWidth.value = String(sw);
        highlightStrokeBtn(String(sw));
    }
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   CURSOR
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function setCursor(cursor) {
    stage.container().style.cursor = cursor;
}

function refreshCursor(override = null) {
    const c = stage.container();
    if (activeTool === 'eraser') {
        c.classList.add('cursor-eraser');
        elEraserCursor.style.display = 'block';
        syncEraserBubble();
        return;
    }
    c.classList.remove('cursor-eraser');
    elEraserCursor.style.display = 'none';
    if (override)               { c.style.cursor = override; return; }
    if (activeTool === 'select'){ c.style.cursor = 'grab'; return; }
    c.style.cursor = activeTool === 'text' ? 'text' : 'crosshair';
}

function syncEraserBubble() {
    const d = eraserSize * stage.scaleX();
    elEraserCursor.style.width  = d + 'px';
    elEraserCursor.style.height = d + 'px';
}

document.addEventListener('mousemove', e => {
    if (activeTool !== 'eraser') return;
    elEraserCursor.style.left = e.clientX + 'px';
    elEraserCursor.style.top  = e.clientY + 'px';
    syncEraserBubble();
});
document.addEventListener('mouseleave', () => { elEraserCursor.style.display = 'none'; });
document.addEventListener('mouseenter', () => {
    if (activeTool === 'eraser') elEraserCursor.style.display = 'block';
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   TOOL ACTIVATION
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function setTool(t) {
    activeTool = t;
    tr.nodes([]);
    toolBtns.forEach(b => b.classList.toggle('active', b.dataset.tool === t));
    elEraserSize.style.display = (t === 'eraser') ? '' : 'none';
    // Show stroke/color controls only for drawing tools
    refreshCursor();
    layer.draw();
}

toolBtns.forEach(b => b.addEventListener('click', () => setTool(b.dataset.tool)));

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   CANVAS COORDINATE HELPER
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function stagePoint() {
    const p = stage.getPointerPosition();
    if (!p) return { x:0, y:0 };
    return stage.getAbsoluteTransform().copy().invert().point(p);
}

function maybeSnap(p) {
    if (!snapEnabled) return p;
    const s = GRID_SIZE;
    return { x: Math.round(p.x/s)*s, y: Math.round(p.y/s)*s };
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   STYLE
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function getStyle() {
    const stroke = elColorPicker.value;
    const sw     = Number(elStrokeWidth.value);
    const fill   = fillShapes ? stroke : hexAlpha(stroke, 0.14);
    return { stroke, fill, strokeWidth: sw };
}

function hexAlpha(hex, a) {
    const n = parseInt(hex.replace('#',''), 16);
    return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   WIRE SHAPE (events on each drawn node)
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function wireShape(node) {
    // Idempotent guard
    if (node._wired) { node.draggable(activeTool === 'select'); return; }
    node._wired = true;

    node.draggable(activeTool === 'select');

    node.on('mousedown touchstart', e => {
        if (activeTool === 'eraser') {
            e.cancelBubble = true;
            node.destroy();
            tr.nodes([]);
            markDirty();
            pushHistory();
            layer.draw();
        }
    });

    node.on('dragstart', () => {
        if (activeTool === 'select') selectNode(node);
    });
    node.on('dragmove', () => {
        if (snapEnabled) {
            const cls = node.getClassName();
            if (cls !== 'Line' && cls !== 'Arrow') {
                node.position(maybeSnap(node.position()));
            }
        }
    });
    node.on('dragend transformend', () => { markDirty(); pushHistory(); });
    node.on('click tap', e => {
        if (activeTool !== 'select') return;
        e.cancelBubble = true;
        selectNode(node, e.evt?.shiftKey);
    });
    if (node.getClassName() === 'Text') {
        node.on('dblclick dbltap', () => openTextEditor(node));
    }
}

// Wire shapes loaded from saved data
drawableNodes().forEach(wireShape);

function setAllDraggable() {
    const sel = activeTool === 'select';
    drawableNodes().forEach(n => n.draggable(sel));
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   ZOOM
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function refreshZoomLabel() {
    elZoom.textContent = Math.round(stage.scaleX() * 100) + '%';
}

function resetView() {
    stage.position({x:0,y:0});
    stage.scale({x:1,y:1});
    refreshZoomLabel();
    stage.batchDraw();
}

stage.on('wheel', e => {
    e.evt.preventDefault();
    const old = stage.scaleX();
    const ptr = stage.getPointerPosition();
    const to  = { x:(ptr.x - stage.x())/old, y:(ptr.y - stage.y())/old };
    const dir = e.evt.deltaY > 0 ? -1 : 1;
    const next = Math.max(0.1, Math.min(8, old * (dir > 0 ? 1.08 : 1/1.08)));
    stage.scale({x:next,y:next});
    stage.position({ x: ptr.x - to.x*next, y: ptr.y - to.y*next });
    refreshZoomLabel();
    stage.batchDraw();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   ERASER
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function eraseAt(pt) {
    const r = eraserSize / 2;
    const nodes = drawableNodes().slice(); // snapshot to avoid mutation
    let erased = false;
    for (const node of nodes) {
        if (!node.getParent()) continue;
        const rect = node.getClientRect({ relativeTo: layer });
        if (pt.x >= rect.x - r && pt.x <= rect.x + rect.width  + r &&
            pt.y >= rect.y - r && pt.y <= rect.y + rect.height + r) {
            node.destroy();
            erased = true;
        }
    }
    if (erased) {
        tr.nodes([]);
        markDirty();
        layer.batchDraw();
    }
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   POINTER / DRAWING EVENTS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
stage.on('mousedown touchstart', e => {
    closeTextEditor();
    const pt = maybeSnap(stagePoint());
    startPt = pt;
    setAllDraggable();

    // â”€â”€ Eraser
    if (activeTool === 'eraser') {
        isErasing = true;
        eraseAt(pt);
        return;
    }

    // â”€â”€ Select / Pan
    if (activeTool === 'select') {
        if (isOnBackground(e.target)) {
            selectNode(null);
            isPanning = true;
            setCursor('grabbing');
            stage.draggable(true);
            stage.startDrag();
        }
        return;
    }

    // â”€â”€ Drawing tools â€” only start if clicking on background
    if (!isOnBackground(e.target)) return;

    isDrawing = true;
    const s = getStyle();

    if (activeTool === 'freehand') {
        curShape = new Konva.Line({
            points: [pt.x, pt.y, pt.x, pt.y],
            stroke: s.stroke, strokeWidth: s.strokeWidth,
            lineCap:'round', lineJoin:'round', tension:0.4,
            draggable:false,
        });
    } else if (activeTool === 'rect') {
        curShape = new Konva.Rect({
            x:pt.x, y:pt.y, width:1, height:1,
            stroke:s.stroke, strokeWidth:s.strokeWidth,
            fill:s.fill, cornerRadius:3, draggable:false,
        });
    } else if (activeTool === 'circle') {
        curShape = new Konva.Ellipse({
            x:pt.x, y:pt.y, radiusX:1, radiusY:1,
            stroke:s.stroke, strokeWidth:s.strokeWidth,
            fill:s.fill, draggable:false,
        });
    } else if (activeTool === 'line') {
        curShape = new Konva.Line({
            points:[pt.x,pt.y,pt.x,pt.y],
            stroke:s.stroke, strokeWidth:s.strokeWidth,
            lineCap:'round', draggable:false,
        });
    } else if (activeTool === 'arrow') {
        curShape = new Konva.Arrow({
            points:[pt.x,pt.y,pt.x,pt.y],
            stroke:s.stroke, fill:s.stroke, strokeWidth:s.strokeWidth,
            pointerLength:12, pointerWidth:12, lineCap:'round', draggable:false,
        });
    } else if (activeTool === 'text') {
        // Text: place immediately and open editor
        const node = new Konva.Text({
            x:pt.x, y:pt.y, text:'Text',
            fill:s.stroke, fontSize:22,
            fontFamily:"'Inter',system-ui,sans-serif", draggable:false,
        });
        layer.add(node);
        wireShape(node);
        selectNode(node);
        openTextEditor(node);
        markDirty();
        pushHistory();
        isDrawing = false;
        return;
    }

    if (curShape) {
        layer.add(curShape);
        layer.draw();
    }
});

stage.on('mousemove touchmove', () => {
    if (isErasing) {
        eraseAt(stagePoint());
        return;
    }
    if (!isDrawing || !curShape) return;

    const pt = activeTool === 'freehand' ? stagePoint() : maybeSnap(stagePoint());

    if (activeTool === 'freehand') {
        curShape.points([...curShape.points(), pt.x, pt.y]);
    } else if (activeTool === 'rect') {
        curShape.setAttrs({
            x: Math.min(startPt.x, pt.x),
            y: Math.min(startPt.y, pt.y),
            width:  Math.abs(pt.x - startPt.x),
            height: Math.abs(pt.y - startPt.y),
        });
    } else if (activeTool === 'circle') {
        curShape.setAttrs({
            x: (startPt.x + pt.x) / 2,
            y: (startPt.y + pt.y) / 2,
            radiusX: Math.abs(pt.x - startPt.x) / 2,
            radiusY: Math.abs(pt.y - startPt.y) / 2,
        });
    } else if (activeTool === 'line' || activeTool === 'arrow') {
        curShape.points([startPt.x, startPt.y, pt.x, pt.y]);
    }

    layer.batchDraw();
});

function finalize() {
    if (isErasing) {
        isErasing = false;
        pushHistory();
        return;
    }
    if (isPanning) {
        isPanning = false;
        stage.draggable(false);
        refreshCursor();
        return;
    }
    if (!isDrawing || !curShape) return;

    const shape = curShape;
    curShape    = null;
    isDrawing   = false;

    // Discard shapes that are too small to be intentional
    if (tooSmall(shape)) { shape.destroy(); layer.draw(); return; }

    wireShape(shape);

    if (activeTool === 'freehand') {
        // Stay on freehand so user can keep drawing
        tr.nodes([]);
        setAllDraggable();
        layer.draw();
    } else {
        selectNode(shape);
        setTool('select');
    }
    markDirty();
    pushHistory();
}

// Use window events only — they fire for every release regardless of where the pointer lifts
window.addEventListener('mouseup',  finalize);
window.addEventListener('touchend', finalize);

function tooSmall(shape) {
    const cn = shape.getClassName();
    if (cn === 'Line') {
        const p = shape.points();
        // Freehand lines accumulate many points; only discard if the mouse never moved
        // (points stays at the initial [x,y,x,y] = 4 values, both identical)
        if (p.length > 4) return false;   // has real movement — always keep
        return Math.abs(p[0]-p[2]) < 4 && Math.abs(p[1]-p[3]) < 4;
    }
    if (cn === 'Arrow') {
        const p = shape.points();
        return Math.abs(p[0]-p[2]) < 4 && Math.abs(p[1]-p[3]) < 4;
    }
    if (cn === 'Rect')    return shape.width()   < 4 || shape.height()   < 4;
    if (cn === 'Ellipse') return shape.radiusX() < 2 || shape.radiusY() < 2;
    return false;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   KEYBOARD
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
document.addEventListener('keydown', e => {
    if (textEl) return;   // ignore when typing in textarea
    const tg = e.target;
    if (tg.tagName === 'INPUT' || tg.tagName === 'TEXTAREA' || tg.tagName === 'SELECT') return;
    const k = e.key.toLowerCase();

    if ((e.ctrlKey||e.metaKey) && k==='z')                          { e.preventDefault(); undo(); return; }
    if ((e.ctrlKey||e.metaKey) && (k==='y'||(e.shiftKey&&k==='z'))){ e.preventDefault(); redo(); return; }
    if ((e.ctrlKey||e.metaKey) && k==='s')                          { e.preventDefault(); saveBoard(false); return; }
    if ((e.ctrlKey||e.metaKey) && k==='c')                          { e.preventDefault(); copySelected(); return; }
    if ((e.ctrlKey||e.metaKey) && k==='v')                          { e.preventDefault(); pasteClipboard(); return; }
    if ((e.ctrlKey||e.metaKey) && k==='d')                          { e.preventDefault(); duplicateSelected(); return; }

    const shortcuts = { v:'select', p:'freehand', r:'rect', c:'circle', l:'line', a:'arrow', t:'text' };
    if (!e.ctrlKey && !e.metaKey && shortcuts[k]) { setTool(shortcuts[k]); return; }
    if (!e.ctrlKey && !e.metaKey && k==='e') { setTool(activeTool==='eraser' ? 'select' : 'eraser'); return; }
    if (k==='f')      { fitToContent(); return; }
    if (k==='escape') { setTool('select'); return; }
    if (k==='home')   { resetView(); return; }

    if (e.key !== 'Delete' && e.key !== 'Backspace') return;
    const sel = tr.nodes();
    if (!sel.length) return;
    e.preventDefault();
    sel.forEach(n => n.destroy());
    tr.nodes([]);
    markDirty(); pushHistory(); layer.draw(); refreshSelBtns();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   COLOUR SWATCHES
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function highlightSwatch(color) {
    document.querySelectorAll('.swatch').forEach(s =>
        s.classList.toggle('on', s.dataset.color.toLowerCase() === color.toLowerCase())
    );
}

document.querySelectorAll('.swatch').forEach(s => s.addEventListener('click', () => {
    elColorPicker.value = s.dataset.color;
    highlightSwatch(s.dataset.color);
    applyStyle();
}));

// The native color input is overlaid on the .color-wheel div at opacity:0
elColorPicker.addEventListener('input', e => {
    highlightSwatch(e.target.value);
    applyStyle();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   STROKE WIDTH BUTTONS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function highlightStrokeBtn(w) {
    document.querySelectorAll('.stroke-btn').forEach(b =>
        b.classList.toggle('on', b.dataset.w === w)
    );
}

document.querySelectorAll('.stroke-btn').forEach(b => b.addEventListener('click', () => {
    elStrokeWidth.value = b.dataset.w;
    highlightStrokeBtn(b.dataset.w);
    applyStyle();
}));

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   APPLY STYLE TO SELECTION
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function applyStyle() {
    const nodes = tr.nodes();
    if (!nodes.length) return;
    const s = getStyle();
    nodes.forEach(node => {
        const cn = node.getClassName();
        if (cn === 'Text') {
            node.fill(s.stroke);
        } else {
            if (node.stroke) { node.stroke(s.stroke); node.strokeWidth(s.strokeWidth); }
            if (cn === 'Rect' || cn === 'Ellipse') node.fill(s.fill);
            if (cn === 'Arrow') node.fill(s.stroke);
        }
    });
    markDirty(); pushHistory(); layer.batchDraw();
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SELECTION OPERATIONS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function duplicateSelected() {
    const clones = tr.nodes().map(node => {
        const clone = node.clone({ x:node.x()+24, y:node.y()+24 });
        layer.add(clone); wireShape(clone);
        return clone;
    });
    if (!clones.length) return;
    tr.nodes(clones); tr.moveToTop();
    markDirty(); pushHistory(); refreshSelBtns(); layer.draw();
}

function copySelected() {
    const nodes = tr.nodes();
    if (!nodes.length) return;
    clipboard = nodes.map(n => n.toJSON());
}

function pasteClipboard() {
    if (!clipboard?.length) return;
    const pasted = clipboard.map(json => {
        const node = Konva.Node.create(JSON.parse(json));
        node.position({ x:node.x()+28, y:node.y()+28 });
        layer.add(node); wireShape(node);
        return node;
    });
    clipboard = pasted.map(n => n.toJSON());
    tr.nodes(pasted); tr.moveToTop();
    markDirty(); pushHistory(); refreshSelBtns(); layer.draw();
}

function moveLayer(dir) {
    tr.nodes().forEach(node => {
        if (dir==='up')     { node.moveUp(); }
        if (dir==='down')   { node.moveDown(); bgRect.moveToBottom(); gridGroup.moveToBottom(); bgRect.moveToBottom(); }
        if (dir==='top')    { node.moveToTop(); }
        if (dir==='bottom') { node.moveToBottom(); bgRect.moveToBottom(); gridGroup.moveToBottom(); bgRect.moveToBottom(); }
    });
    tr.moveToTop();
    markDirty(); pushHistory(); layer.draw();
}

function fitToContent() {
    const nodes = drawableNodes();
    if (!nodes.length) { resetView(); return; }
    const box = nodes.reduce((b,node) => {
        const r = node.getClientRect({ relativeTo: layer });
        return { x:Math.min(b.x,r.x), y:Math.min(b.y,r.y), right:Math.max(b.right,r.x+r.width), bottom:Math.max(b.bottom,r.y+r.height) };
    }, {x:Infinity,y:Infinity,right:-Infinity,bottom:-Infinity});
    const pad=100, w=Math.max(1,box.right-box.x), h=Math.max(1,box.bottom-box.y);
    const scale = Math.max(0.1, Math.min(8, Math.min((stage.width()-pad)/w, (stage.height()-pad)/h)));
    stage.scale({x:scale,y:scale});
    stage.position({ x:stage.width()/2-(box.x+w/2)*scale, y:stage.height()/2-(box.y+h/2)*scale });
    refreshZoomLabel(); stage.batchDraw();
}

function exportPng() {
    const sel = tr.nodes();
    tr.nodes([]); layer.draw();
    const uri = stage.toDataURL({ pixelRatio:2 });
    tr.nodes(sel); layer.draw();
    const a = Object.assign(document.createElement('a'), {
        download: (elBoardName.value.trim()||'whiteboard').replace(/[^a-z0-9_-]+/gi,'-') + '.png',
        href: uri,
    });
    document.body.appendChild(a); a.click(); a.remove();
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   FILL TOGGLE
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
elFillToggle.addEventListener('click', () => {
    fillShapes = !fillShapes;
    elFillToggle.innerHTML = fillShapes
        ? `<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg> Fill On`
        : `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg> Fill Off`;
    elFillToggle.classList.toggle('on', fillShapes);
    applyStyle();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   GRID & SNAP
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
elGridToggle.addEventListener('change', () => {
    gridVisible = elGridToggle.checked;
    gridGroup.visible(gridVisible);
    elGridLabel.classList.toggle('on', gridVisible);
    layer.batchDraw();
});
elSnapToggle.addEventListener('change', () => {
    snapEnabled = elSnapToggle.checked;
    elSnapLabel.classList.toggle('on', snapEnabled);
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   CONTROL EVENT WIRING
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
elBoardName.addEventListener('input',  markDirty);
elResetView.addEventListener('click',  resetView);
elSaveBtn.addEventListener('click',    () => saveBoard(false));
elUndoBtn.addEventListener('click',    undo);
elRedoBtn.addEventListener('click',    redo);
elDupeBtn.addEventListener('click',    duplicateSelected);
elExportBtn.addEventListener('click',  exportPng);
elFitBtn.addEventListener('click',     fitToContent);
elFwdBtn.addEventListener('click',     () => moveLayer('up'));
elBkBtn.addEventListener('click',      () => moveLayer('down'));
elFrontBtn.addEventListener('click',   () => moveLayer('top'));
elBackBtn.addEventListener('click',    () => moveLayer('bottom'));
elEraserSize.addEventListener('change', () => {
    eraserSize = Number(elEraserSize.value);
    syncEraserBubble();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   SAVE
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function stageJson() {
    tr.nodes([]);
    const clone = stage.clone();
    clone.find('.selectionTransformer').forEach(n => n.destroy());
    clone.find('.gridGroup').forEach(n => n.destroy());
    clone.find('.background').forEach(n => n.destroy());
    return clone.toJSON();
}

async function saveBoard(auto = false) {
    const name = elBoardName.value.trim();
    if (!name) { setStatus('Board name is required.', 'error'); return; }
    if (saving) return;
    saving = true;
    elSaveBtn.disabled = true;
    setStatus(auto ? 'Auto-savingâ€¦' : 'Savingâ€¦', 'saving');
    const payload = { name, canvas_data: stageJson() };
    try {
        const res  = await fetch(boardId ? `${API}/${boardId}` : API, {
            method:  boardId ? 'PUT' : 'POST',
            headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF },
            body:    JSON.stringify(payload),
        });
        const data = res.status === 204 ? {} : await res.json().catch(()=>({}));
        if (!res.ok) throw new Error(data.message || Object.values(data.errors||{})[0]?.[0] || 'Save failed.');
        boardId = data.id || boardId;
        dirty = false;
        lastSaveTime = Date.now();
        setStatus(auto ? 'Auto-saved' : 'Saved just now', 'saved');
        if (boardId && !location.pathname.endsWith(`/boards/${boardId}`))
            history.replaceState({}, '', `/boards/${boardId}`);
    } catch(err) {
        console.error(err);
        setStatus(err.message || 'Save failed', 'error');
    } finally {
        saving = false;
        elSaveBtn.disabled = false;
    }
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   TEXT EDITOR OVERLAY
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function openTextEditor(textNode) {
    closeTextEditor();
    tr.nodes([]);
    textNode.hide();
    layer.draw();

    const pos   = textNode.absolutePosition();
    const cRect = stage.container().getBoundingClientRect();
    const scale = stage.scaleX();

    textEl = document.createElement('textarea');
    document.body.appendChild(textEl);
    textEl.value = textNode.text();
    Object.assign(textEl.style, {
        position:   'fixed',
        top:        (cRect.top  + pos.y) + 'px',
        left:       (cRect.left + pos.x) + 'px',
        width:      Math.max(textNode.width() * scale, 200) + 'px',
        minHeight:  '40px',
        fontSize:   (textNode.fontSize() * scale) + 'px',
        fontFamily: textNode.fontFamily(),
        color:      textNode.fill(),
        border:     '2px solid #2563eb',
        borderRadius: '8px',
        padding:    '6px 10px',
        background: '#fff',
        zIndex:     '9999',
        outline:    'none',
        resize:     'none',
        lineHeight: '1.5',
        boxShadow:  '0 4px 20px rgba(37,99,235,.22)',
    });
    textEl.focus();
    textEl.select();

    textEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); closeTextEditor(true); }
        if (e.key === 'Escape') closeTextEditor(false);
    });
    textEl.addEventListener('blur', () => closeTextEditor(true));
    textEl._node = textNode;
}

function closeTextEditor(commit = true) {
    if (!textEl) return;
    const node = textEl._node;
    if (commit) {
        node.text(textEl.value.trim() || 'Text');
        markDirty(); pushHistory();
    }
    node.show();
    textEl.remove();
    textEl = null;
    selectNode(node);
    layer.draw();
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   RESIZE
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
window.addEventListener('resize', () => {
    stage.width(innerWidth);
    stage.height(innerHeight);
    stage.batchDraw();
});

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   AUTO-SAVE & TIMERS
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
setInterval(() => { if (dirty) saveBoard(true); }, 60_000);
setInterval(tickAge, 5_000);

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
   INIT
â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
refreshZoomLabel();
setTool('select');
pushHistory();
refreshSelBtns();
refreshHistoryBtns();
</script>
</body>
</html>
