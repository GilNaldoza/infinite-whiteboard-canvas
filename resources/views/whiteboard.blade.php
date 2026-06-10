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
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #eceff3;
            --panel-bg: rgba(255, 255, 255, 0.88);
            --panel-border: rgba(180, 195, 220, 0.55);
            --panel-shadow: 0 8px 32px rgba(20, 40, 80, 0.13), 0 1.5px 4px rgba(20,40,80,0.06);
            --text: #1a2540;
            --muted: #6b7a99;
            --accent: #3b73f5;
            --accent-hover: #2563eb;
            --danger: #e53e3e;
            --success: #16a34a;
            --radius: 12px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --transition: 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html, body {
            height: 100%;
            overflow: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            -webkit-font-smoothing: antialiased;
        }

        #container { width: 100vw; height: 100vh; }

        /* ── Canvas background grid pattern ── */
        #canvas-bg {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                radial-gradient(circle, rgba(155,170,200,0.35) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* ── Glassmorphism panel base ── */
        .glass {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius);
            box-shadow: var(--panel-shadow);
            backdrop-filter: blur(16px) saturate(1.6);
            -webkit-backdrop-filter: blur(16px) saturate(1.6);
        }

        /* ── Top Bar ── */
        .topbar {
            position: fixed;
            top: 12px; left: 12px; right: 12px;
            z-index: 100;
            display: grid;
            grid-template-columns: minmax(180px, 260px) 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
        }

        .board-name-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .board-icon {
            flex-shrink: 0;
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #3b73f5, #6b48ff);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
        }

        #boardName {
            width: 100%; min-width: 0;
            padding: 6px 10px;
            border: 1.5px solid transparent;
            border-radius: var(--radius-xs);
            background: transparent;
            color: var(--text);
            font: 500 13px 'Inter', sans-serif;
            outline: none;
            transition: border-color var(--transition), background var(--transition);
        }
        #boardName:hover { border-color: rgba(100,130,200,0.28); background: rgba(255,255,255,0.5); }
        #boardName:focus { border-color: var(--accent); background: #fff; }

        /* Center style group */
        .style-group {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        /* Color swatches */
        .color-swatch-wrap {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .color-swatch {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            position: relative;
        }
        .color-swatch:hover { transform: scale(1.18); box-shadow: 0 2px 8px rgba(0,0,0,0.25); }
        .color-swatch.active { border-color: #fff; box-shadow: 0 0 0 2.5px var(--accent); transform: scale(1.1); }
        #colorPicker { display: none; }
        .color-picker-btn {
            width: 26px; height: 26px;
            border-radius: 50%;
            border: 2px solid var(--panel-border);
            background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red);
            cursor: pointer;
            transition: transform var(--transition);
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .color-picker-btn:hover { transform: scale(1.15); }
        .color-picker-btn input[type="color"] {
            position: absolute; inset: 0;
            opacity: 0; cursor: pointer;
            width: 100%; height: 100%;
        }

        /* Stroke width */
        .stroke-btns { display: flex; gap: 3px; }
        .stroke-btn {
            display: flex; align-items: center; justify-content: center;
            width: 32px; height: 32px;
            border: 1.5px solid var(--panel-border);
            border-radius: var(--radius-xs);
            background: transparent;
            cursor: pointer;
            transition: background var(--transition), border-color var(--transition);
        }
        .stroke-btn:hover { background: rgba(59,115,245,0.08); border-color: rgba(59,115,245,0.35); }
        .stroke-btn.active { background: rgba(59,115,245,0.12); border-color: var(--accent); }
        .stroke-btn svg { pointer-events: none; }

        /* Divider */
        .vdivider {
            width: 1px; height: 22px;
            background: rgba(100,130,200,0.22);
            flex-shrink: 0;
        }

        /* Generic icon button */
        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 5px;
            height: 32px;
            padding: 0 10px;
            border: 1.5px solid var(--panel-border);
            border-radius: var(--radius-xs);
            background: transparent;
            color: var(--text);
            font: 500 12px 'Inter', sans-serif;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background var(--transition), border-color var(--transition), color var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .icon-btn:hover { background: rgba(59,115,245,0.07); border-color: rgba(59,115,245,0.3); }
        .icon-btn:active { transform: scale(0.96); }
        .icon-btn.active { background: rgba(59,115,245,0.13); border-color: var(--accent); color: var(--accent); }
        .icon-btn svg { flex-shrink: 0; }

        /* Status group */
        .status-group { display: flex; align-items: center; gap: 6px; }

        .zoom-badge {
            display: inline-flex; align-items: center;
            height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(100,120,180,0.1);
            color: var(--muted);
            font: 500 12px 'Inter', sans-serif;
            white-space: nowrap;
            border: 1px solid transparent;
            transition: all var(--transition);
            cursor: default;
        }
        .zoom-badge:hover { background: rgba(59,115,245,0.1); color: var(--accent); }

        #status {
            display: inline-flex; align-items: center; gap: 5px;
            height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font: 500 12px 'Inter', sans-serif;
            white-space: nowrap;
            border: 1px solid transparent;
            transition: background 0.3s ease, color 0.3s ease;
        }
        #status::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
            transition: background 0.3s ease;
        }
        #status[data-state="dirty"] { background: #fff8e6; color: #9a5b00; border-color: #ffe0a0; }
        #status[data-state="dirty"]::before { background: #f6a800; }
        #status[data-state="saving"] { background: #eaf2ff; color: #1757cb; border-color: #b3ccff; }
        #status[data-state="saving"]::before { background: #3b73f5; animation: pulse 1s ease-in-out infinite; }
        #status[data-state="saved"] { background: #e8f7ef; color: #16703c; border-color: #a0dbb5; }
        #status[data-state="saved"]::before { background: #22c55e; }
        #status[data-state="error"] { background: #fff1f0; color: #b42318; border-color: #f3b8b1; }
        #status[data-state="error"]::before { background: var(--danger); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .btn-save {
            height: 32px;
            padding: 0 14px;
            border-radius: var(--radius-xs);
            border: none;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font: 600 12px 'Inter', sans-serif;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(22,163,74,0.35);
            transition: opacity var(--transition), transform var(--transition), box-shadow var(--transition);
        }
        .btn-save:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(22,163,74,0.4); }
        .btn-save:active { transform: scale(0.96) translateY(0); }
        .btn-save:disabled { cursor: progress; opacity: 0.65; transform: none; }

        /* ── Tool Rail (left) ── */
        .tool-rail {
            position: fixed;
            left: 12px; top: 74px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px;
        }

        .tool-btn {
            display: flex; align-items: center; justify-content: center;
            flex-direction: column;
            gap: 2px;
            width: 48px; height: 48px;
            border: 1.5px solid transparent;
            border-radius: 10px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            font: 500 10px 'Inter', sans-serif;
            transition: background var(--transition), border-color var(--transition), color var(--transition), transform var(--transition);
            position: relative;
        }
        .tool-btn:hover { background: rgba(59,115,245,0.09); border-color: rgba(59,115,245,0.25); color: var(--accent); transform: translateX(1px); }
        .tool-btn.active {
            background: linear-gradient(135deg, rgba(59,115,245,0.15), rgba(107,72,255,0.12));
            border-color: rgba(59,115,245,0.4);
            color: var(--accent);
            box-shadow: inset 0 0 0 1px rgba(59,115,245,0.12);
        }
        .tool-btn svg { flex-shrink: 0; }
        .tool-label { font-size: 9.5px; line-height: 1; opacity: 0.9; }

        /* Tooltip */
        .tool-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: rgba(20,30,60,0.92);
            color: #fff;
            font: 500 11px 'Inter', sans-serif;
            padding: 5px 9px;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
            z-index: 200;
        }
        .tool-btn:hover::after { opacity: 1; }

        /* ── Utility Panel (right) ── */
        .utility-panel {
            position: fixed;
            right: 12px; top: 74px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 10px;
        }

        .util-section { display: flex; flex-direction: column; gap: 4px; }
        .util-label {
            font: 600 9.5px 'Inter', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--muted);
            padding: 0 2px;
        }
        .util-row { display: flex; gap: 4px; }

        .util-btn {
            display: flex; align-items: center; justify-content: center;
            flex: 1;
            height: 34px;
            border: 1.5px solid var(--panel-border);
            border-radius: var(--radius-xs);
            background: transparent;
            color: var(--text);
            font: 500 11px 'Inter', sans-serif;
            cursor: pointer;
            transition: background var(--transition), border-color var(--transition), color var(--transition), transform var(--transition);
            gap: 4px;
            white-space: nowrap;
        }
        .util-btn:hover { background: rgba(59,115,245,0.08); border-color: rgba(59,115,245,0.3); }
        .util-btn:active { transform: scale(0.95); }
        .util-btn:disabled { opacity: 0.38; cursor: not-allowed; transform: none; }
        .util-btn.active { background: rgba(59,115,245,0.12); border-color: var(--accent); color: var(--accent); }
        .util-btn svg { flex-shrink: 0; }

        .util-toggle { display: none; }
        .util-toggle-label {
            display: flex; align-items: center; justify-content: center;
            gap: 5px;
            flex: 1;
            height: 34px;
            border: 1.5px solid var(--panel-border);
            border-radius: var(--radius-xs);
            background: transparent;
            color: var(--text);
            font: 500 11px 'Inter', sans-serif;
            cursor: pointer;
            transition: background var(--transition), border-color var(--transition), color var(--transition);
            user-select: none;
        }
        .util-toggle:checked + .util-toggle-label,
        .util-toggle-label.active {
            background: rgba(59,115,245,0.12);
            border-color: var(--accent);
            color: var(--accent);
        }
        .util-toggle-label:hover { background: rgba(59,115,245,0.08); border-color: rgba(59,115,245,0.3); }

        /* ── Responsive ── */
        @media (max-width: 760px) {
            .topbar { grid-template-columns: 1fr; gap: 6px; }
            .style-group { justify-content: flex-start; overflow-x: auto; }
            .status-group { justify-content: flex-start; }
            .tool-rail {
                left: 12px; right: 12px;
                top: auto; bottom: 12px;
                flex-direction: row;
                overflow-x: auto;
            }
            .tool-btn { width: 44px; height: 44px; }
            .utility-panel {
                left: 12px; right: 12px;
                top: auto; bottom: 80px;
                flex-direction: row;
                flex-wrap: wrap;
                overflow-x: auto;
            }
            .tool-btn::after { display: none; }
        }

        /* ── Slide-in animation for panels ── */
        .glass {
            animation: panel-in 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        .tool-rail { animation-delay: 0.06s; }
        .utility-panel { animation-delay: 0.1s; }

        @keyframes panel-in {
            from { opacity: 0; transform: translateY(-8px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Selection box ── */
        #selectionRect {
            position: absolute;
            border: 1.5px dashed rgba(59,115,245,0.7);
            background: rgba(59,115,245,0.06);
            pointer-events: none;
            display: none;
            border-radius: 3px;
        }

        /* ── Context toast ── */
        #toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(20,30,60,0.92);
            color: #fff;
            font: 500 12px 'Inter', sans-serif;
            padding: 8px 16px;
            border-radius: 999px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 500;
            white-space: nowrap;
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
<div id="canvas-bg"></div>
<div id="toast"></div>

<!-- ── Top Bar ── -->
<div class="topbar glass">
    <div class="board-name-wrap">
        <div class="board-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8M12 8v8"/>
            </svg>
        </div>
        <input id="boardName" class="board-field" type="text" placeholder="Board name" value="{{ $board?->name ?? $defaultName ?? 'Untitled board' }}" autocomplete="off" spellcheck="false">
    </div>

    <div class="style-group">
        <!-- Color swatches -->
        <div class="color-swatch-wrap" id="colorSwatches">
            <div class="color-swatch active" style="background:#3b73f5" data-color="#3b73f5" title="Blue"></div>
            <div class="color-swatch" style="background:#e53e3e" data-color="#e53e3e" title="Red"></div>
            <div class="color-swatch" style="background:#f97316" data-color="#f97316" title="Orange"></div>
            <div class="color-swatch" style="background:#eab308" data-color="#eab308" title="Yellow"></div>
            <div class="color-swatch" style="background:#22c55e" data-color="#22c55e" title="Green"></div>
            <div class="color-swatch" style="background:#a855f7" data-color="#a855f7" title="Purple"></div>
            <div class="color-swatch" style="background:#1a2540" data-color="#1a2540" title="Black"></div>
        </div>
        <!-- Custom color picker -->
        <div class="color-picker-btn" title="Custom color">
            <input type="color" id="colorPicker" value="#3b73f5" aria-label="Custom color">
        </div>

        <div class="vdivider"></div>

        <!-- Stroke width -->
        <div class="stroke-btns" id="strokeBtns">
            <button class="stroke-btn active" data-width="2" title="Thin (2px)" aria-label="Thin stroke">
                <svg width="20" height="14" viewBox="0 0 20 14"><line x1="2" y1="7" x2="18" y2="7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </button>
            <button class="stroke-btn" data-width="4" title="Medium (4px)" aria-label="Medium stroke">
                <svg width="20" height="14" viewBox="0 0 20 14"><line x1="2" y1="7" x2="18" y2="7" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </button>
            <button class="stroke-btn" data-width="8" title="Thick (8px)" aria-label="Thick stroke">
                <svg width="20" height="14" viewBox="0 0 20 14"><line x1="2" y1="7" x2="18" y2="7" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>
            </button>
        </div>
        <!-- Hidden select for compatibility -->
        <select id="strokeWidth" style="display:none">
            <option value="2" selected>Thin</option>
            <option value="4">Medium</option>
            <option value="8">Thick</option>
        </select>

        <div class="vdivider"></div>

        <button class="icon-btn" id="fillToggle" title="Toggle shape fill">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
            Fill
        </button>
        <button class="icon-btn" id="resetView" title="Reset view (Home)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                <path d="M3 3v5h5"/>
            </svg>
            Reset
        </button>
        <button class="icon-btn" id="fitView" title="Fit to content (F)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            </svg>
            Fit
        </button>
        <span id="zoomLabel" class="zoom-badge">100%</span>
    </div>

    <div class="status-group">
        <span id="status" data-state="saved">Ready</span>
        <button class="btn-save" id="saveBoard">Save</button>
        <a href="{{ route('boards.index') }}" class="icon-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Boards
        </a>
    </div>
</div>

<!-- ── Tool Rail ── -->
<div class="tool-rail glass" aria-label="Drawing tools">
    <button class="tool-btn active" data-tool="select" data-tooltip="Select (V)" title="Select and move">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 3l14 9-7 1-4 7z"/>
        </svg>
        <span class="tool-label">Select</span>
    </button>
    <button class="tool-btn" data-tool="freehand" data-tooltip="Freehand (P)" title="Draw freehand">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 19c-2.3 0-6.4-.2-8-2 -.4-.5-.2-1.2.3-1.5 1.2-.7 2.4.5 3.2 1.3.8.7 2.2 1.7 4.5 1.2 2.7-.6 4-3 4-5.5 0-2.2-1.7-3.5-4-3.5-1.5 0-2.5.7-3.5 1.5"/>
            <path d="M9.5 9.5c-1.2.8-2.5 2.2-2.5 4"/>
        </svg>
        <span class="tool-label">Pen</span>
    </button>
    <button class="tool-btn" data-tool="rect" data-tooltip="Rectangle (R)" title="Draw rectangle">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
        </svg>
        <span class="tool-label">Rect</span>
    </button>
    <button class="tool-btn" data-tool="circle" data-tooltip="Ellipse (C)" title="Draw circle/ellipse">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="12" rx="10" ry="7"/>
        </svg>
        <span class="tool-label">Circle</span>
    </button>
    <button class="tool-btn" data-tool="line" data-tooltip="Line (L)" title="Draw line">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="19" x2="19" y2="5"/>
        </svg>
        <span class="tool-label">Line</span>
    </button>
    <button class="tool-btn" data-tool="arrow" data-tooltip="Arrow (A)" title="Draw arrow">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="19" x2="19" y2="5"/><polyline points="9 5 19 5 19 15"/>
        </svg>
        <span class="tool-label">Arrow</span>
    </button>
    <button class="tool-btn" data-tool="text" data-tooltip="Text (T)" title="Place text">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>
        </svg>
        <span class="tool-label">Text</span>
    </button>
</div>

<!-- ── Utility Panel ── -->
<div class="utility-panel glass" aria-label="Canvas utilities">
    <div class="util-section">
        <div class="util-label">History</div>
        <div class="util-row">
            <button class="util-btn" id="undoButton" title="Undo (Ctrl+Z)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>
                </svg>
                Undo
            </button>
            <button class="util-btn" id="redoButton" title="Redo (Ctrl+Y)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/>
                </svg>
                Redo
            </button>
        </div>
    </div>

    <div class="util-section">
        <div class="util-label">Selection</div>
        <div class="util-row">
            <button class="util-btn" id="duplicateButton" title="Duplicate (Ctrl+D)" disabled>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="8" y="8" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Dupe
            </button>
            <button class="util-btn" id="exportButton" title="Export as PNG">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                PNG
            </button>
        </div>
    </div>

    <div class="util-section">
        <div class="util-label">Layer Order</div>
        <div class="util-row">
            <button class="util-btn" id="bringForwardButton" title="Bring forward" disabled>↑ Fwd</button>
            <button class="util-btn" id="sendBackwardButton" title="Send backward" disabled>↓ Back</button>
        </div>
        <div class="util-row">
            <button class="util-btn" id="bringFrontButton" title="Bring to front" disabled>⤒ Front</button>
            <button class="util-btn" id="sendBackButton" title="Send to back" disabled>⤓ Back</button>
        </div>
    </div>

    <div class="util-section">
        <div class="util-label">Canvas</div>
        <div class="util-row">
            <input type="checkbox" class="util-toggle" id="gridToggle">
            <label for="gridToggle" class="util-toggle-label" id="gridLabel" title="Show grid">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="1"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>
                </svg>
                Grid
            </label>
            <input type="checkbox" class="util-toggle" id="snapToggle">
            <label for="snapToggle" class="util-toggle-label" id="snapLabel" title="Snap to grid">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
                </svg>
                Snap
            </label>
        </div>
    </div>
</div>

<div id="container"></div>

<script>
    const savedData = @json($board?->canvas_data ?? null);
    let boardId = @json($board?->id ?? null);
    const apiBase = '/api/boards';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const boardName = document.getElementById('boardName');
    const statusLabel = document.getElementById('status');
    const zoomLabel = document.getElementById('zoomLabel');
    const colorPicker = document.getElementById('colorPicker');
    const strokeWidth = document.getElementById('strokeWidth');
    const toolButtons = document.querySelectorAll('[data-tool]');
    const saveButton = document.getElementById('saveBoard');
    const undoButton = document.getElementById('undoButton');
    const redoButton = document.getElementById('redoButton');
    const duplicateButton = document.getElementById('duplicateButton');
    const exportButton = document.getElementById('exportButton');
    const bringForwardButton = document.getElementById('bringForwardButton');
    const sendBackwardButton = document.getElementById('sendBackwardButton');
    const bringFrontButton = document.getElementById('bringFrontButton');
    const sendBackButton = document.getElementById('sendBackButton');
    const fillToggle = document.getElementById('fillToggle');
    const fitViewButton = document.getElementById('fitView');
    const gridToggle = document.getElementById('gridToggle');
    const snapToggle = document.getElementById('snapToggle');
    const gridLabel = document.getElementById('gridLabel');
    const snapLabel = document.getElementById('snapLabel');

    let activeTool = 'select';
    let isDrawing = false;
    let isPanning = false;
    let startPoint = null;
    let currentShape = null;
    let dirty = false;
    let saving = false;
    let fillShapes = false;
    let gridVisible = false;
    let snapEnabled = false;
    let lastSavedAt = null;
    let suppressHistory = false;
    let textEditor = null;
    let clipboard = null;
    const history = [];
    const redoHistory = [];
    const maxHistory = 60;
    const gridSize = 40;

    /* ── Toast ── */
    let toastTimer = null;
    function showToast(msg, duration = 2000) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), duration);
    }

    /* ── Status ── */
    function setStatus(message, state = 'saved') {
        statusLabel.textContent = message;
        statusLabel.dataset.state = state;
    }

    function updateSavedAge() {
        if (dirty || saving || !lastSavedAt || statusLabel.dataset.state !== 'saved') return;
        const seconds = Math.max(0, Math.floor((Date.now() - lastSavedAt) / 1000));
        if (seconds < 5) { setStatus('Saved just now', 'saved'); return; }
        if (seconds < 60) { setStatus(`Saved ${seconds}s ago`, 'saved'); return; }
        setStatus(`Saved ${Math.floor(seconds / 60)}m ago`, 'saved');
    }

    /* ── Stage ── */
    function makeStage() {
        return new Konva.Stage({ container: 'container', width: window.innerWidth, height: window.innerHeight, x: 0, y: 0, scaleX: 1, scaleY: 1 });
    }

    let stage = makeStage();
    if (savedData) {
        try {
            stage.destroy();
            stage = Konva.Node.create(JSON.parse(savedData), 'container');
            stage.width(window.innerWidth);
            stage.height(window.innerHeight);
        } catch (error) {
            console.error(error);
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

    function ensureBackground() {
        let background = layer.findOne('.background');
        if (!background) {
            background = new Konva.Rect({ name: 'background', x: -100000, y: -100000, width: 200000, height: 200000, fill: '#ffffff', listening: true });
            layer.add(background);
            background.moveToBottom();
        }
        return background;
    }

    const background = ensureBackground();
    const gridGroup = ensureGrid();
    const transformer = new Konva.Transformer({
        name: 'selectionTransformer',
        rotateEnabled: true,
        ignoreStroke: true,
        borderStroke: '#3b73f5',
        borderStrokeWidth: 1.5,
        anchorStroke: '#3b73f5',
        anchorFill: '#fff',
        anchorSize: 9,
        anchorCornerRadius: 3,
        boundBoxFunc: (oldBox, newBox) => (newBox.width < 8 || newBox.height < 8) ? oldBox : newBox,
    });
    layer.add(transformer);
    layer.draw();

    function ensureGrid() {
        let group = layer.findOne('.gridGroup');
        if (!group) {
            group = new Konva.Group({ name: 'gridGroup', listening: false, visible: false });
            for (let value = -4000; value <= 4000; value += gridSize) {
                group.add(new Konva.Line({ name: 'gridNode', points: [value, -4000, value, 4000], stroke: '#dde3ee', strokeWidth: value === 0 ? 1.5 : 0.75, listening: false }));
                group.add(new Konva.Line({ name: 'gridNode', points: [-4000, value, 4000, value], stroke: '#dde3ee', strokeWidth: value === 0 ? 1.5 : 0.75, listening: false }));
            }
            layer.add(group);
            group.moveToBottom();
            background.moveToBottom();
        }
        return group;
    }

    function markDirty() {
        dirty = true;
        if (!saving) setStatus('Unsaved', 'dirty');
    }

    function drawableSnapshot() {
        return JSON.stringify(selectableNodes().map((node) => node.toJSON()));
    }

    function restoreDrawableSnapshot(snapshot) {
        suppressHistory = true;
        transformer.nodes([]);
        selectableNodes().forEach((node) => node.destroy());
        JSON.parse(snapshot).forEach((nodeJson) => {
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
        const snapshot = drawableSnapshot();
        if (history[history.length - 1] === snapshot) return;
        history.push(snapshot);
        if (history.length > maxHistory) history.shift();
        redoHistory.length = 0;
        updateHistoryControls();
    }

    function undo() {
        if (history.length <= 1) return;
        redoHistory.push(history.pop());
        restoreDrawableSnapshot(history[history.length - 1]);
        updateHistoryControls();
        showToast('Undo');
    }

    function redo() {
        if (!redoHistory.length) return;
        const snapshot = redoHistory.pop();
        history.push(snapshot);
        restoreDrawableSnapshot(snapshot);
        updateHistoryControls();
        showToast('Redo');
    }

    function updateHistoryControls() {
        undoButton.disabled = history.length <= 1;
        redoButton.disabled = redoHistory.length === 0;
    }

    function setTool(tool) {
        activeTool = tool;
        transformer.nodes([]);
        toolButtons.forEach((button) => button.classList.toggle('active', button.dataset.tool === tool));
        updateCursor();
        layer.draw();
    }

    toolButtons.forEach((button) => button.addEventListener('click', () => setTool(button.dataset.tool)));

    function pointerPosition() {
        const pointer = stage.getPointerPosition();
        if (!pointer) return { x: 0, y: 0 };
        return stage.getAbsoluteTransform().copy().invert().point(pointer);
    }

    function snapValue(value) { return snapEnabled ? Math.round(value / gridSize) * gridSize : value; }
    function snapPoint(point) { return { x: snapValue(point.x), y: snapValue(point.y) }; }

    function style() {
        return {
            stroke: colorPicker.value,
            fill: fillShapes ? colorPicker.value : transparentFill(colorPicker.value),
            strokeWidth: Number(strokeWidth.value),
        };
    }

    function isBackground(target) {
        return target === stage || target === background || target.hasName?.('background');
    }

    function selectableNodes() {
        return layer.children.filter((node) => (
            !node.hasName('background') &&
            !node.hasName('selectionTransformer') &&
            !node.hasName('gridGroup') &&
            !node.hasName('gridNode')
        ));
    }

    function selectNode(node, append = false) {
        if (!node || isBackground(node)) {
            transformer.nodes([]);
        } else if (append) {
            const nodes = transformer.nodes();
            const exists = nodes.includes(node);
            transformer.nodes(exists ? nodes.filter((item) => item !== node) : nodes.concat(node));
        } else {
            transformer.nodes([node]);
        }
        syncStyleControlsFromSelection();
        updateSelectionControls();
        layer.draw();
    }

    function updateSelectionControls() {
        const hasSelection = transformer.nodes().length > 0;
        [duplicateButton, bringForwardButton, sendBackwardButton, bringFrontButton, sendBackButton].forEach((button) => {
            button.disabled = !hasSelection;
        });
    }

    function syncStyleControlsFromSelection() {
        const selected = transformer.nodes()[0];
        if (!selected) return;
        const stroke = selected.stroke?.() || selected.fill?.();
        if (stroke && /^#[0-9a-f]{6}$/i.test(stroke)) {
            colorPicker.value = stroke;
            syncSwatches(stroke);
        }
        if (selected.strokeWidth?.()) {
            strokeWidth.value = String(selected.strokeWidth());
            syncStrokeButtons(String(selected.strokeWidth()));
        }
    }

    function updateCursor(cursor = null) {
        if (cursor) { stage.container().style.cursor = cursor; return; }
        if (activeTool === 'select') { stage.container().style.cursor = 'grab'; return; }
        stage.container().style.cursor = activeTool === 'text' ? 'text' : 'crosshair';
    }

    function enableShape(node) {
        node.draggable(activeTool === 'select');
        node.on('dragstart', () => { if (activeTool === 'select') selectNode(node); });
        node.on('dragmove', () => {
            if (!snapEnabled || node.getClassName() === 'Line' || node.getClassName() === 'Arrow') return;
            node.position(snapPoint(node.position()));
        });
        node.on('dragend transformend', () => { markDirty(); pushHistory(); });
        node.on('click tap', (event) => {
            if (activeTool !== 'select') return;
            event.cancelBubble = true;
            selectNode(node, event.evt?.shiftKey);
        });
        if (node.getClassName() === 'Text') node.on('dblclick dbltap', () => editText(node));
    }

    selectableNodes().forEach(enableShape);

    function updateZoomLabel() {
        zoomLabel.textContent = `${Math.round(stage.scaleX() * 100)}%`;
    }

    function resetView() {
        stage.position({ x: 0, y: 0 });
        stage.scale({ x: 1, y: 1 });
        updateZoomLabel();
        stage.batchDraw();
    }

    function applyShapeDragState() {
        selectableNodes().forEach((node) => node.draggable(activeTool === 'select'));
    }

    /* ── Smooth zoom with easing ── */
    let zoomTarget = 1;
    let zoomAnimating = false;

    stage.on('wheel', (event) => {
        event.evt.preventDefault();
        const oldScale = stage.scaleX();
        const pointer = stage.getPointerPosition();
        const mousePointTo = {
            x: (pointer.x - stage.x()) / oldScale,
            y: (pointer.y - stage.y()) / oldScale,
        };
        const direction = event.evt.deltaY > 0 ? -1 : 1;
        const factor = 1.07;
        const nextScale = direction > 0 ? oldScale * factor : oldScale / factor;
        const newScale = Math.max(0.1, Math.min(5, nextScale));

        stage.scale({ x: newScale, y: newScale });
        stage.position({
            x: pointer.x - mousePointTo.x * newScale,
            y: pointer.y - mousePointTo.y * newScale,
        });
        updateZoomLabel();
        stage.batchDraw();
    });

    stage.on('mousedown touchstart', (event) => {
        closeTextEditor();
        const point = snapPoint(pointerPosition());
        startPoint = point;
        applyShapeDragState();

        if (activeTool === 'select') {
            if (isBackground(event.target)) {
                selectNode(null);
                isPanning = true;
                updateCursor('grabbing');
                stage.draggable(true);
                stage.startDrag();
            }
            return;
        }

        if (!isBackground(event.target)) return;

        isDrawing = true;
        const currentStyle = style();

        if (activeTool === 'freehand') {
            currentShape = new Konva.Line({
                points: [point.x, point.y],
                stroke: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                lineCap: 'round',
                lineJoin: 'round',
                tension: 0.35,
                draggable: false,
            });
        }
        if (activeTool === 'rect') {
            currentShape = new Konva.Rect({
                x: point.x, y: point.y, width: 1, height: 1,
                stroke: currentStyle.stroke, strokeWidth: currentStyle.strokeWidth,
                fill: currentStyle.fill, cornerRadius: 3, draggable: false,
            });
        }
        if (activeTool === 'circle') {
            currentShape = new Konva.Ellipse({
                x: point.x, y: point.y, radiusX: 1, radiusY: 1,
                stroke: currentStyle.stroke, strokeWidth: currentStyle.strokeWidth,
                fill: currentStyle.fill, draggable: false,
            });
        }
        if (activeTool === 'line') {
            currentShape = new Konva.Line({
                points: [point.x, point.y, point.x, point.y],
                stroke: currentStyle.stroke, strokeWidth: currentStyle.strokeWidth,
                lineCap: 'round', draggable: false,
            });
        }
        if (activeTool === 'arrow') {
            currentShape = new Konva.Arrow({
                points: [point.x, point.y, point.x, point.y],
                stroke: currentStyle.stroke, fill: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                pointerLength: 14, pointerWidth: 14, lineCap: 'round', draggable: false,
            });
        }
        if (activeTool === 'text') {
            currentShape = new Konva.Text({
                x: point.x, y: point.y, text: 'Text',
                fill: currentStyle.stroke, fontSize: 24,
                fontFamily: "'Inter', system-ui, sans-serif", draggable: false,
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

    stage.on('mousemove touchmove', () => {
        if (!isDrawing || !currentShape) return;
        const point = activeTool === 'freehand' ? pointerPosition() : snapPoint(pointerPosition());

        if (activeTool === 'freehand') currentShape.points(currentShape.points().concat([point.x, point.y]));
        if (activeTool === 'rect') {
            currentShape.setAttrs({
                x: Math.min(startPoint.x, point.x), y: Math.min(startPoint.y, point.y),
                width: Math.abs(point.x - startPoint.x), height: Math.abs(point.y - startPoint.y),
            });
        }
        if (activeTool === 'circle') {
            currentShape.setAttrs({
                x: (startPoint.x + point.x) / 2, y: (startPoint.y + point.y) / 2,
                radiusX: Math.abs(point.x - startPoint.x) / 2,
                radiusY: Math.abs(point.y - startPoint.y) / 2,
            });
        }
        if (activeTool === 'line' || activeTool === 'arrow') {
            currentShape.points([startPoint.x, startPoint.y, point.x, point.y]);
        }
        layer.batchDraw();
    });

    stage.on('mouseup touchend', () => {
        if (isPanning) {
            isPanning = false;
            stage.draggable(false);
            updateCursor();
            return;
        }
        if (!isDrawing || !currentShape) return;
        const shape = currentShape;
        currentShape = null;
        isDrawing = false;
        if (shapeTooSmall(shape)) { shape.destroy(); layer.draw(); return; }
        enableShape(shape);
        selectNode(shape);
        setTool('select');
        markDirty();
        pushHistory();
    });

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const isTyping = textEditor || target === boardName || target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT';
        if (isTyping) return;
        const key = event.key.toLowerCase();

        if ((event.ctrlKey || event.metaKey) && key === 'z') { event.preventDefault(); undo(); return; }
        if ((event.ctrlKey || event.metaKey) && (key === 'y' || (event.shiftKey && key === 'z'))) { event.preventDefault(); redo(); return; }
        if ((event.ctrlKey || event.metaKey) && key === 's') { event.preventDefault(); saveBoard(false); return; }
        if ((event.ctrlKey || event.metaKey) && key === 'c') { event.preventDefault(); copySelection(); return; }
        if ((event.ctrlKey || event.metaKey) && key === 'v') { event.preventDefault(); pasteSelection(); return; }
        if ((event.ctrlKey || event.metaKey) && key === 'd') { event.preventDefault(); duplicateSelection(); return; }

        const shortcuts = { v: 'select', p: 'freehand', r: 'rect', c: 'circle', l: 'line', a: 'arrow', t: 'text' };
        if (!event.ctrlKey && !event.metaKey && shortcuts[key]) { setTool(shortcuts[key]); return; }
        if (key === 'f') { fitToContent(); return; }
        if (key === 'escape') { setTool('select'); return; }
        if (key === 'home') { resetView(); return; }

        if (event.key !== 'Delete' && event.key !== 'Backspace') return;
        const selected = transformer.nodes();
        if (!selected.length) return;
        event.preventDefault();
        selected.forEach((node) => node.destroy());
        transformer.nodes([]);
        markDirty();
        pushHistory();
        layer.draw();
        updateSelectionControls();
    });

    boardName.addEventListener('input', markDirty);
    document.getElementById('resetView').addEventListener('click', resetView);
    saveButton.addEventListener('click', () => saveBoard(false));
    undoButton.addEventListener('click', undo);
    redoButton.addEventListener('click', redo);
    duplicateButton.addEventListener('click', duplicateSelection);
    exportButton.addEventListener('click', exportPng);
    fitViewButton.addEventListener('click', fitToContent);
    bringForwardButton.addEventListener('click', () => moveSelection('up'));
    sendBackwardButton.addEventListener('click', () => moveSelection('down'));
    bringFrontButton.addEventListener('click', () => moveSelection('top'));
    sendBackButton.addEventListener('click', () => moveSelection('bottom'));

    fillToggle.addEventListener('click', () => {
        fillShapes = !fillShapes;
        fillToggle.textContent = '';
        fillToggle.innerHTML = fillShapes
            ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> Fill On`
            : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> Fill Off`;
        fillToggle.classList.toggle('active', fillShapes);
        applyStyleToSelection();
    });

    gridToggle.addEventListener('change', () => {
        gridVisible = gridToggle.checked;
        gridGroup.visible(gridVisible);
        gridLabel.classList.toggle('active', gridVisible);
        layer.batchDraw();
    });

    snapToggle.addEventListener('change', () => {
        snapEnabled = snapToggle.checked;
        snapLabel.classList.toggle('active', snapEnabled);
    });

    colorPicker.addEventListener('input', (e) => {
        syncSwatches(e.target.value);
        applyStyleToSelection();
    });

    strokeWidth.addEventListener('change', applyStyleToSelection);

    /* ── Color swatches ── */
    function syncSwatches(color) {
        const normalized = color.toLowerCase();
        document.querySelectorAll('.color-swatch').forEach((swatch) => {
            swatch.classList.toggle('active', swatch.dataset.color.toLowerCase() === normalized);
        });
    }

    document.querySelectorAll('.color-swatch').forEach((swatch) => {
        swatch.addEventListener('click', () => {
            colorPicker.value = swatch.dataset.color;
            syncSwatches(swatch.dataset.color);
            applyStyleToSelection();
        });
    });

    /* ── Stroke width buttons ── */
    function syncStrokeButtons(width) {
        document.querySelectorAll('.stroke-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.width === width);
        });
    }

    document.querySelectorAll('.stroke-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            strokeWidth.value = btn.dataset.width;
            syncStrokeButtons(btn.dataset.width);
            applyStyleToSelection();
        });
    });

    function applyStyleToSelection() {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        const currentStyle = style();
        nodes.forEach((node) => {
            if (node.stroke && node.getClassName() !== 'Text') {
                node.stroke(currentStyle.stroke);
                node.strokeWidth(currentStyle.strokeWidth);
            }
            if (node.getClassName() === 'Rect' || node.getClassName() === 'Ellipse') node.fill(currentStyle.fill);
            if (node.getClassName() === 'Text') node.fill(currentStyle.stroke);
            if (node.getClassName() === 'Arrow') node.fill(currentStyle.stroke);
        });
        markDirty();
        pushHistory();
        layer.batchDraw();
    }

    function duplicateSelection() {
        const clones = transformer.nodes().map((node) => {
            const clone = node.clone({ x: node.x() + 24, y: node.y() + 24 });
            layer.add(clone);
            enableShape(clone);
            return clone;
        });
        if (!clones.length) return;
        transformer.nodes(clones);
        transformer.moveToTop();
        markDirty();
        pushHistory();
        updateSelectionControls();
        layer.draw();
        showToast('Duplicated');
    }

    function copySelection() {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        clipboard = nodes.map((node) => node.toJSON());
        showToast(`Copied ${nodes.length} shape${nodes.length > 1 ? 's' : ''}`);
    }

    function pasteSelection() {
        if (!clipboard?.length) return;
        const clones = clipboard.map((nodeJson) => {
            const node = Konva.Node.create(JSON.parse(nodeJson));
            node.position({ x: node.x() + 28, y: node.y() + 28 });
            layer.add(node);
            enableShape(node);
            return node;
        });
        clipboard = clones.map((node) => node.toJSON());
        transformer.nodes(clones);
        transformer.moveToTop();
        markDirty();
        pushHistory();
        updateSelectionControls();
        layer.draw();
        showToast('Pasted');
    }

    function moveSelection(direction) {
        const nodes = transformer.nodes();
        if (!nodes.length) return;
        nodes.forEach((node) => {
            if (direction === 'up') node.moveUp();
            if (direction === 'down') { node.moveDown(); background.moveToBottom(); gridGroup.moveToBottom(); background.moveToBottom(); }
            if (direction === 'top') node.moveToTop();
            if (direction === 'bottom') { node.moveToBottom(); background.moveToBottom(); gridGroup.moveToBottom(); background.moveToBottom(); }
        });
        transformer.moveToTop();
        markDirty();
        pushHistory();
        layer.draw();
    }

    function fitToContent() {
        const nodes = selectableNodes();
        if (!nodes.length) { resetView(); return; }
        const box = nodes.reduce((bounds, node) => {
            const rect = node.getClientRect({ relativeTo: layer });
            return {
                x: Math.min(bounds.x, rect.x),
                y: Math.min(bounds.y, rect.y),
                right: Math.max(bounds.right, rect.x + rect.width),
                bottom: Math.max(bounds.bottom, rect.y + rect.height),
            };
        }, { x: Infinity, y: Infinity, right: -Infinity, bottom: -Infinity });

        const padding = 120;
        const width = Math.max(1, box.right - box.x);
        const height = Math.max(1, box.bottom - box.y);
        const scale = Math.max(0.1, Math.min(5, Math.min((stage.width() - padding) / width, (stage.height() - padding) / height)));

        stage.scale({ x: scale, y: scale });
        stage.position({
            x: stage.width() / 2 - (box.x + width / 2) * scale,
            y: stage.height() / 2 - (box.y + height / 2) * scale,
        });
        updateZoomLabel();
        stage.batchDraw();
    }

    function exportPng() {
        const selected = transformer.nodes();
        transformer.nodes([]);
        layer.draw();
        const uri = stage.toDataURL({ pixelRatio: 2 });
        transformer.nodes(selected);
        layer.draw();
        const link = document.createElement('a');
        link.download = `${(boardName.value.trim() || 'whiteboard').replace(/[^a-z0-9-_]+/gi, '-')}.png`;
        link.href = uri;
        document.body.appendChild(link);
        link.click();
        link.remove();
        showToast('Exported PNG');
    }

    function transparentFill(hex) {
        const value = hex.replace('#', '');
        const bigint = parseInt(value, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, 0.15)`;
    }

    function shapeTooSmall(shape) {
        if (shape.getClassName() === 'Line' || shape.getClassName() === 'Arrow') {
            const points = shape.points();
            return Math.abs(points[0] - points[2]) < 3 && Math.abs(points[1] - points[3]) < 3;
        }
        if (shape.getClassName() === 'Rect') return shape.width() < 3 || shape.height() < 3;
        if (shape.getClassName() === 'Ellipse') return shape.radiusX() < 2 || shape.radiusY() < 2;
        return false;
    }

    function stageJson() {
        transformer.nodes([]);
        const clone = stage.clone();
        clone.find('.selectionTransformer').forEach((node) => node.destroy());
        clone.find('.gridGroup').forEach((node) => node.destroy());
        clone.find('.gridNode').forEach((node) => node.destroy());
        clone.find('.background').forEach((node) => node.destroy());
        return clone.toJSON();
    }

    async function saveBoard(auto = false) {
        const name = boardName.value.trim();
        if (!name) { setStatus('Board name is required.', 'error'); return; }
        if (saving) return;
        saving = true;
        saveButton.disabled = true;
        setStatus(auto ? 'Auto-saving…' : 'Saving…', 'saving');
        const payload = { name, canvas_data: stageJson() };

        try {
            const response = await fetch(boardId ? `${apiBase}/${boardId}` : apiBase, {
                method: boardId ? 'PUT' : 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload),
            });
            const data = response.status === 204 ? {} : await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || Object.values(data.errors || {})[0]?.[0] || 'Save failed.';
                throw new Error(message);
            }
            boardId = data.id || boardId;
            dirty = false;
            lastSavedAt = Date.now();
            setStatus(auto ? 'Auto-saved' : 'Saved just now', 'saved');
            if (!auto) showToast('Board saved ✓');
            if (boardId && !window.location.pathname.endsWith(`/boards/${boardId}`)) {
                window.history.replaceState({}, '', `/boards/${boardId}`);
            }
        } catch (error) {
            console.error(error);
            setStatus(error.message || 'Save failed', 'error');
        } finally {
            saving = false;
            saveButton.disabled = false;
        }
    }

    function editText(textNode) {
        closeTextEditor();
        transformer.nodes([]);
        textNode.hide();
        layer.draw();

        const textPosition = textNode.absolutePosition();
        const containerRect = stage.container().getBoundingClientRect();
        const areaPosition = {
            x: containerRect.left + textPosition.x,
            y: containerRect.top + textPosition.y,
        };

        textEditor = document.createElement('textarea');
        document.body.appendChild(textEditor);
        textEditor.value = textNode.text();
        Object.assign(textEditor.style, {
            position: 'absolute',
            top: `${areaPosition.y}px`,
            left: `${areaPosition.x}px`,
            width: `${Math.max(textNode.width(), 180)}px`,
            minHeight: '40px',
            fontSize: `${textNode.fontSize() * stage.scaleX()}px`,
            fontFamily: textNode.fontFamily(),
            color: textNode.fill(),
            border: '2px solid #3b73f5',
            borderRadius: '8px',
            padding: '6px 10px',
            background: '#fff',
            zIndex: '200',
            outline: 'none',
            resize: 'none',
            boxShadow: '0 4px 20px rgba(59,115,245,0.25)',
            lineHeight: '1.5',
        });
        textEditor.focus();
        textEditor.select();

        textEditor.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); closeTextEditor(true); }
            if (event.key === 'Escape') closeTextEditor(false);
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

    window.addEventListener('resize', () => {
        stage.width(window.innerWidth);
        stage.height(window.innerHeight);
        stage.batchDraw();
    });

    setInterval(() => { if (dirty) saveBoard(true); }, 60000);
    setInterval(updateSavedAge, 5000);

    updateZoomLabel();
    setTool('select');
    pushHistory();
    updateSelectionControls();
</script>
</body>
</html>
