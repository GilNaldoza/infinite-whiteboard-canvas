<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $board?->name ?? 'New board' }} | Infinite Whiteboard</title>
    <script src="https://unpkg.com/konva@9/konva.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; background: #eceff3; color: #172033; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        #container { width: 100vw; height: 100vh; }
        .topbar {
            position: fixed;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 10;
            display: grid;
            grid-template-columns: minmax(180px, 320px) 1fr auto;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: rgba(255,255,255,.96);
            border: 1px solid #d9dee8;
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(20,31,51,.1);
        }
        .tool-rail {
            position: fixed;
            left: 12px;
            top: 76px;
            z-index: 10;
            display: grid;
            gap: 6px;
            padding: 8px;
            background: rgba(255,255,255,.96);
            border: 1px solid #d9dee8;
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(20,31,51,.1);
        }
        .topbar input, .topbar select, .topbar button, .topbar a, .tool-rail button {
            min-height: 36px;
            border: 1px solid #aab3c2;
            border-radius: 6px;
            background: #fff;
            color: #172033;
            font: inherit;
        }
        .topbar input[type="text"], .topbar select { padding: 7px 10px; }
        .topbar input[type="color"] { width: 44px; padding: 3px; }
        .topbar button, .topbar a, .tool-rail button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: background .16s ease, border-color .16s ease, color .16s ease, transform .16s ease;
        }
        .tool-rail button {
            width: 108px;
            justify-content: flex-start;
        }
        /* Eraser tool active state */
        .tool-rail button[data-tool="eraser"].active {
            background: #b42318;
            border-color: #b42318;
            color: #fff;
            box-shadow: inset 3px 0 0 rgba(255,255,255,.45);
        }
        /* Eraser cursor */
        .cursor-eraser { cursor: none !important; }
        #eraserCursor {
            position: fixed;
            pointer-events: none;
            z-index: 999;
            border-radius: 50%;
            border: 2px solid #b42318;
            background: rgba(180, 35, 24, 0.12);
            transform: translate(-50%, -50%);
            display: none;
            transition: width 0.1s ease, height 0.1s ease;
        }
        .topbar button:hover, .topbar a:hover, .tool-rail button:hover { background: #f3f5f8; }
        .tool-rail button.active {
            background: #1f6feb;
            color: #fff;
            border-color: #1f6feb;
            box-shadow: inset 3px 0 0 rgba(255,255,255,.45);
        }
        .topbar .save { background: #16703c; color: #fff; border-color: #16703c; }
        .topbar .save:hover { background: #126132; }
        .topbar .save:disabled { cursor: progress; opacity: .72; }
        .topbar .group { display: inline-flex; gap: 6px; align-items: center; justify-content: flex-end; }
        .board-field { width: 100%; min-width: 0; }
        .style-group { justify-self: center; }
        .status-group { justify-self: end; }
        #zoomLabel, #status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            border-radius: 999px;
            padding: 4px 10px;
            background: #f2f4f7;
            color: #475467;
            font-size: .9rem;

            white-space: nowrap;
        }
        #status[data-state="dirty"] { background: #fff7e6; color: #9a5b00; }
        #status[data-state="saving"] { background: #eaf2ff; color: #175cd3; }
        #status[data-state="saved"] { background: #e9f7ef; color: #16703c; }
        #status[data-state="error"] { background: #fff1f0; color: #b42318; }
        @media (max-width: 760px) {
            .topbar { grid-template-columns: 1fr; align-items: stretch; }
            .topbar .group { justify-content: flex-start; overflow-x: auto; }
            .tool-rail {
                top: auto;
                right: 12px;
                bottom: 12px;
                display: flex;
                overflow-x: auto;
            }
            .tool-rail button { width: auto; }
        }
    </style>
</head>
<body>
<div class="topbar">
    <input id="boardName" class="board-field" type="text" placeholder="Board name" value="{{ $board?->name ?? $defaultName ?? 'Untitled board' }}">
    <div class="group style-group">
        <input id="colorPicker" type="color" value="#1f6feb" aria-label="Color" title="Drawing color">
        <select id="strokeWidth" aria-label="Stroke width" title="Stroke width">
            <option value="2">Thin</option>
            <option value="4" selected>Medium</option>
            <option value="8">Thick</option>
        </select>
        <button type="button" id="resetView" title="Reset pan and zoom">Reset view</button>
        <select id="eraserSize" aria-label="Eraser size" title="Eraser size" style="display:none">
            <option value="16">Eraser S</option>
            <option value="32" selected>Eraser M</option>
            <option value="56">Eraser L</option>
        </select>
        <span id="zoomLabel">100%</span>
    </div>
    <div class="group status-group">
        <span id="status" data-state="saved">Ready</span>
        <button type="button" id="saveBoard" class="save">Save</button>
        <a href="{{ route('boards.index') }}">Boards</a>
    </div>
</div>
<div class="tool-rail" aria-label="Drawing tools">
    <button type="button" data-tool="select" class="active" title="Select and move shapes">Select</button>
    <button type="button" data-tool="freehand" title="Draw freehand strokes">Freehand</button>
    <button type="button" data-tool="rect" title="Draw rectangles">Rectangle</button>
    <button type="button" data-tool="circle" title="Draw circles and ellipses">Circle</button>
    <button type="button" data-tool="line" title="Draw straight lines">Line</button>
    <button type="button" data-tool="arrow" title="Draw arrows">Arrow</button>
    <button type="button" data-tool="text" title="Place editable text">Text</button>
    <button type="button" data-tool="eraser" title="Erase shapes or strokes (E)">Eraser</button>
</div>
<div id="eraserCursor"></div>
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
    const eraserSizeSelect = document.getElementById('eraserSize');
    const eraserCursor = document.getElementById('eraserCursor');

    let activeTool = 'select';
    let eraserSize = 32;   // diameter in canvas px
    let isErasing = false;
    let isDrawing = false;
    let isPanning = false;
    let startPoint = null;
    let currentShape = null;
    let dirty = false;
    let saving = false;
    let textEditor = null;

    function setStatus(message, state = 'saved') {
        statusLabel.textContent = message;
        statusLabel.dataset.state = state;
    }

    function makeStage() {
        return new Konva.Stage({
            container: 'container',
            width: window.innerWidth,
            height: window.innerHeight,
            x: 0,
            y: 0,
            scaleX: 1,
            scaleY: 1,
        });
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
        if (!layer.getStage()) {
            stage.add(layer);
        }
    }

    function ensureBackground() {
        let background = layer.findOne('.background');
        if (!background) {
            background = new Konva.Rect({
                name: 'background',
                x: -100000,
                y: -100000,
                width: 200000,
                height: 200000,
                fill: '#ffffff',
                listening: true,
            });
            layer.add(background);
            background.moveToBottom();
        }
        return background;
    }

    const background = ensureBackground();
    const transformer = new Konva.Transformer({
        name: 'selectionTransformer',
        rotateEnabled: true,
        ignoreStroke: true,
        boundBoxFunc: (oldBox, newBox) => {
            if (newBox.width < 8 || newBox.height < 8) {
                return oldBox;
            }
            return newBox;
        },
    });
    layer.add(transformer);
    layer.draw();

    function markDirty() {
        dirty = true;
        if (!saving) {
            setStatus('Unsaved', 'dirty');
        }
    }

    function setTool(tool) {
        activeTool = tool;
        transformer.nodes([]);
        toolButtons.forEach((button) => button.classList.toggle('active', button.dataset.tool === tool));
        // Show/hide eraser size selector
        eraserSizeSelect.style.display = tool === 'eraser' ? '' : 'none';
        updateCursor();
        layer.draw();
    }

    toolButtons.forEach((button) => {
        button.addEventListener('click', () => setTool(button.dataset.tool));
    });

    function pointerPosition() {
        const pointer = stage.getPointerPosition();
        if (!pointer) {
            return { x: 0, y: 0 };
        }
        return stage.getAbsoluteTransform().copy().invert().point(pointer);
    }

    function style() {
        return {
            stroke: colorPicker.value,
            fill: colorPicker.value,
            strokeWidth: Number(strokeWidth.value),
        };
    }

    function isBackground(target) {
        return target === stage || target === background || target.hasName?.('background');
    }

    function selectableNodes() {
        return layer.children.filter((node) => !node.hasName('background') && !node.hasName('selectionTransformer'));
    }

    function selectNode(node) {
        if (!node || isBackground(node)) {
            transformer.nodes([]);
        } else {
            transformer.nodes([node]);
        }
        layer.draw();
    }

    function updateCursor(cursor = null) {
        const container = stage.container();
        if (activeTool === 'eraser') {
            container.classList.add('cursor-eraser');
            eraserCursor.style.display = 'block';
            syncEraserCursorSize();
            return;
        }
        container.classList.remove('cursor-eraser');
        eraserCursor.style.display = 'none';
        if (cursor) {
            container.style.cursor = cursor;
            return;
        }
        if (activeTool === 'select') {
            container.style.cursor = 'grab';
            return;
        }
        container.style.cursor = activeTool === 'text' ? 'text' : 'crosshair';
    }

    function syncEraserCursorSize() {
        // Convert canvas-space eraser diameter to screen pixels
        const screenDiameter = eraserSize * stage.scaleX();
        eraserCursor.style.width = `${screenDiameter}px`;
        eraserCursor.style.height = `${screenDiameter}px`;
    }

    // Follow mouse for the eraser cursor bubble
    document.addEventListener('mousemove', (e) => {
        if (activeTool !== 'eraser') return;
        eraserCursor.style.left = `${e.clientX}px`;
        eraserCursor.style.top = `${e.clientY}px`;
        syncEraserCursorSize();
    });

    // Hide eraser bubble when leaving canvas area
    document.addEventListener('mouseleave', () => { eraserCursor.style.display = 'none'; });
    document.addEventListener('mouseenter', () => {
        if (activeTool === 'eraser') eraserCursor.style.display = 'block';
    });

    function enableShape(node) {
        node.draggable(activeTool === 'select');
        node.on('dragstart', () => {
            if (activeTool === 'select') {
                selectNode(node);
            }
        });
        node.on('dragend transformend', markDirty);
        node.on('click tap', (event) => {
            if (activeTool !== 'select') {
                return;
            }
            event.cancelBubble = true;
            selectNode(node);
        });
        if (node.getClassName() === 'Text') {
            node.on('dblclick dbltap', () => editText(node));
        }
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

    stage.on('wheel', (event) => {
        event.evt.preventDefault();
        const oldScale = stage.scaleX();
        const pointer = stage.getPointerPosition();
        const mousePointTo = {
            x: (pointer.x - stage.x()) / oldScale,
            y: (pointer.y - stage.y()) / oldScale,
        };
        const direction = event.evt.deltaY > 0 ? -1 : 1;
        const factor = 1.08;
        const nextScale = direction > 0 ? oldScale * factor : oldScale / factor;
        const newScale = Math.max(0.2, Math.min(3, nextScale));

        stage.scale({ x: newScale, y: newScale });
        stage.position({
            x: pointer.x - mousePointTo.x * newScale,
            y: pointer.y - mousePointTo.y * newScale,
        });
        updateZoomLabel();
        stage.batchDraw();
    });

    /* ── Eraser helpers ── */
    function eraseAtPoint(point) {
        const radius = eraserSize / 2;
        const nodes = selectableNodes();
        for (const node of nodes) {
            // Ignore the transformer
            if (node.hasName('selectionTransformer')) continue;
            const rect = node.getClientRect({ relativeTo: layer });
            // Simple AABB hit test expanded by eraser radius
            if (
                point.x >= rect.x - radius &&
                point.x <= rect.x + rect.width + radius &&
                point.y >= rect.y - radius &&
                point.y <= rect.y + rect.height + radius
            ) {
                node.destroy();
                markDirty();
            }
        }
        transformer.nodes([]);
        layer.batchDraw();
    }

    stage.on('contentMousedown contentTouchstart', (event) => {
        closeTextEditor();
        const point = pointerPosition();
        startPoint = point;
        applyShapeDragState();

        // ── Eraser tool ──
        if (activeTool === 'eraser') {
            isErasing = true;
            eraseAtPoint(point);
            return;
        }

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

        if (!isBackground(event.target)) {
            return;
        }

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
                x: point.x,
                y: point.y,
                width: 1,
                height: 1,
                stroke: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                fill: transparentFill(currentStyle.fill),
                draggable: false,
            });
        }

        if (activeTool === 'circle') {
            currentShape = new Konva.Ellipse({
                x: point.x,
                y: point.y,
                radiusX: 1,
                radiusY: 1,
                stroke: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                fill: transparentFill(currentStyle.fill),
                draggable: false,
            });
        }

        if (activeTool === 'line') {
            currentShape = new Konva.Line({
                points: [point.x, point.y, point.x, point.y],
                stroke: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                lineCap: 'round',
                draggable: false,
            });
        }

        if (activeTool === 'arrow') {
            currentShape = new Konva.Arrow({
                points: [point.x, point.y, point.x, point.y],
                stroke: currentStyle.stroke,
                fill: currentStyle.stroke,
                strokeWidth: currentStyle.strokeWidth,
                pointerLength: 14,
                pointerWidth: 14,
                lineCap: 'round',
                draggable: false,
            });
        }

        if (activeTool === 'text') {
            currentShape = new Konva.Text({
                x: point.x,
                y: point.y,
                text: 'Text',
                fill: currentStyle.stroke,
                fontSize: 24,
                fontFamily: 'system-ui, sans-serif',
                draggable: false,
            });
            layer.add(currentShape);
            enableShape(currentShape);
            selectNode(currentShape);
            editText(currentShape);
            markDirty();
            isDrawing = false;
            currentShape = null;
            return;
        }

        if (currentShape) {
            layer.add(currentShape);
            layer.draw();
        }
    });

    stage.on('contentMousemove contentTouchmove', () => {
        if (activeTool === 'eraser' && isErasing) {
            eraseAtPoint(pointerPosition());
            return;
        }
        if (!isDrawing || !currentShape) {
            return;
        }

        const point = pointerPosition();

        if (activeTool === 'freehand') {
            currentShape.points(currentShape.points().concat([point.x, point.y]));
        }

        if (activeTool === 'rect') {
            currentShape.setAttrs({
                x: Math.min(startPoint.x, point.x),
                y: Math.min(startPoint.y, point.y),
                width: Math.abs(point.x - startPoint.x),
                height: Math.abs(point.y - startPoint.y),
            });
        }

        if (activeTool === 'circle') {
            currentShape.setAttrs({
                x: (startPoint.x + point.x) / 2,
                y: (startPoint.y + point.y) / 2,
                radiusX: Math.abs(point.x - startPoint.x) / 2,
                radiusY: Math.abs(point.y - startPoint.y) / 2,
            });
        }

        if (activeTool === 'line' || activeTool === 'arrow') {
            currentShape.points([startPoint.x, startPoint.y, point.x, point.y]);
        }

        layer.batchDraw();
    });

    function finalizeDrawing() {
        if (isErasing) {
            isErasing = false;
            return;
        }

        if (isPanning) {
            isPanning = false;
            stage.draggable(false);
            updateCursor();
            return;
        }

        if (!isDrawing || !currentShape) {
            return;
        }

        const shape = currentShape;
        currentShape = null;
        isDrawing = false;

        if (shapeTooSmall(shape)) {
            shape.destroy();
            layer.draw();
            return;
        }

        enableShape(shape);
        selectNode(shape);
        setTool('select');
        markDirty();
    }

    stage.on('contentMouseup contentTouchend', finalizeDrawing);
    window.addEventListener('mouseup', finalizeDrawing);
    window.addEventListener('touchend', finalizeDrawing);

    document.addEventListener('keydown', (event) => {
        if (textEditor) return;
        const target = event.target;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') return;
        const key = event.key.toLowerCase();
        // Shortcut: E = eraser
        if (!event.ctrlKey && !event.metaKey && key === 'e') {
            setTool(activeTool === 'eraser' ? 'select' : 'eraser');
            return;
        }
        if (event.key !== 'Delete' && event.key !== 'Backspace') {
            return;
        }
        const selected = transformer.nodes();
        if (!selected.length) {
            return;
        }
        event.preventDefault();
        selected.forEach((node) => node.destroy());
        transformer.nodes([]);
        markDirty();
        layer.draw();
    });

    boardName.addEventListener('input', markDirty);
    document.getElementById('resetView').addEventListener('click', resetView);
    saveButton.addEventListener('click', () => saveBoard(false));
    eraserSizeSelect.addEventListener('change', () => {
        eraserSize = Number(eraserSizeSelect.value);
        syncEraserCursorSize();
    });

    function transparentFill(hex) {
        const value = hex.replace('#', '');
        const bigint = parseInt(value, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r}, ${g}, ${b}, 0.16)`;
    }

    function shapeTooSmall(shape) {
        if (shape.getClassName() === 'Line' || shape.getClassName() === 'Arrow') {
            const points = shape.points();
            return Math.abs(points[0] - points[2]) < 3 && Math.abs(points[1] - points[3]) < 3;
        }
        if (shape.getClassName() === 'Rect') {
            return shape.width() < 3 || shape.height() < 3;
        }
        if (shape.getClassName() === 'Ellipse') {
            return shape.radiusX() < 2 || shape.radiusY() < 2;
        }
        return false;
    }

    function stageJson() {
        transformer.nodes([]);
        const clone = stage.clone();
        clone.find('.selectionTransformer').forEach((node) => node.destroy());
        return clone.toJSON();
    }

    async function saveBoard(auto = false) {
        const name = boardName.value.trim();
        if (!name) {
            setStatus('Board name is required.', 'error');
            return;
        }

        if (saving) {
            return;
        }

        saving = true;
        saveButton.disabled = true;
        setStatus(auto ? 'Auto-saving...' : 'Saving...', 'saving');
        const payload = {
            name,
            canvas_data: stageJson(),
        };

        try {
            const response = await fetch(boardId ? `${apiBase}/${boardId}` : apiBase, {
                method: boardId ? 'PUT' : 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = response.status === 204 ? {} : await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || Object.values(data.errors || {})[0]?.[0] || 'Save failed.';
                throw new Error(message);
            }

            boardId = data.id || boardId;
            dirty = false;
            setStatus(auto ? 'Auto-saved' : 'Saved', 'saved');
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
        textEditor.style.position = 'absolute';
        textEditor.style.top = `${areaPosition.y}px`;
        textEditor.style.left = `${areaPosition.x}px`;
        textEditor.style.width = `${Math.max(textNode.width(), 160)}px`;
        textEditor.style.minHeight = '36px';
        textEditor.style.fontSize = `${textNode.fontSize() * stage.scaleX()}px`;
        textEditor.style.fontFamily = textNode.fontFamily();
        textEditor.style.color = textNode.fill();
        textEditor.style.border = '1px solid #1f6feb';
        textEditor.style.borderRadius = '6px';
        textEditor.style.padding = '4px 6px';
        textEditor.style.background = '#fff';
        textEditor.style.zIndex = '20';
        textEditor.focus();
        textEditor.select();

        textEditor.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                closeTextEditor(true);
            }
            if (event.key === 'Escape') {
                closeTextEditor(false);
            }
        });
        textEditor.addEventListener('blur', () => closeTextEditor(true));

        textEditor._node = textNode;
    }

    function closeTextEditor(commit = true) {
        if (!textEditor) {
            return;
        }
        const node = textEditor._node;
        if (commit) {
            node.text(textEditor.value.trim() || 'Text');
            markDirty();
        }
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

    setInterval(() => {
        if (dirty) {
            saveBoard(true);
        }
    }, 60000);

    updateZoomLabel();
    setTool('select');
</script>
</body>
</html>
