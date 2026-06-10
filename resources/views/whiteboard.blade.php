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
        html, body { margin: 0; height: 100%; overflow: hidden; background: #eef1f5; color: #172033; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        #container { width: 100vw; height: 100vh; }
        .toolbar {
            position: fixed;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 10px;
            background: rgba(255,255,255,.96);
            border: 1px solid #d9dee8;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(20,31,51,.12);
        }
        .toolbar input[type="text"] { width: min(260px, 100%); }
        .toolbar input, .toolbar select, .toolbar button, .toolbar a {
            min-height: 36px;
            border: 1px solid #aab3c2;
            border-radius: 6px;
            background: #fff;
            color: #172033;
            font: inherit;
        }
        .toolbar input[type="text"], .toolbar select { padding: 7px 10px; }
        .toolbar input[type="color"] { width: 44px; padding: 3px; }
        .toolbar button, .toolbar a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .toolbar button:hover, .toolbar a:hover { background: #f3f5f8; }
        .toolbar button.active { background: #1f6feb; color: #fff; border-color: #1f6feb; }
        .toolbar .save { background: #16703c; color: #fff; border-color: #16703c; }
        .toolbar .group { display: inline-flex; gap: 6px; align-items: center; }
        #zoomLabel { min-width: 52px; text-align: center; color: #475467; }
        #status { min-width: 120px; color: #475467; }
        @media (max-width: 760px) {
            .toolbar { align-items: stretch; }
            .toolbar input[type="text"] { width: 100%; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <input id="boardName" type="text" placeholder="Board name" value="{{ $board?->name ?? $defaultName ?? 'Untitled board' }}">
    <div class="group" aria-label="Drawing tools">
        <button type="button" data-tool="select" class="active">Select</button>
        <button type="button" data-tool="freehand">Freehand</button>
        <button type="button" data-tool="rect">Rectangle</button>
        <button type="button" data-tool="circle">Circle</button>
        <button type="button" data-tool="line">Line</button>
        <button type="button" data-tool="arrow">Arrow</button>
        <button type="button" data-tool="text">Text</button>
    </div>
    <input id="colorPicker" type="color" value="#1f6feb" aria-label="Color">
    <select id="strokeWidth" aria-label="Stroke width">
        <option value="2">Thin</option>
        <option value="4" selected>Medium</option>
        <option value="8">Thick</option>
    </select>
    <button type="button" id="resetView">Reset view</button>
    <span id="zoomLabel">100%</span>
    <button type="button" id="saveBoard" class="save">Save</button>
    <a href="{{ route('boards.index') }}">Boards</a>
    <span id="status"></span>
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

    let activeTool = 'select';
    let isDrawing = false;
    let isPanning = false;
    let startPoint = null;
    let currentShape = null;
    let dirty = false;
    let textEditor = null;

    function setStatus(message, ok = true) {
        statusLabel.textContent = message;
        statusLabel.style.color = ok ? '#16703c' : '#b42318';
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
            setStatus('Could not load board data.', false);
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
    }

    function setTool(tool) {
        activeTool = tool;
        transformer.nodes([]);
        toolButtons.forEach((button) => button.classList.toggle('active', button.dataset.tool === tool));
        stage.container().style.cursor = tool === 'select' ? 'default' : 'crosshair';
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

    stage.on('mousedown touchstart', (event) => {
        closeTextEditor();
        const point = pointerPosition();
        startPoint = point;
        applyShapeDragState();

        if (activeTool === 'select') {
            if (isBackground(event.target)) {
                selectNode(null);
                isPanning = true;
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

    stage.on('mousemove touchmove', () => {
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

    stage.on('mouseup touchend', () => {
        if (isPanning) {
            isPanning = false;
            stage.draggable(false);
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
    });

    document.addEventListener('keydown', (event) => {
        if (textEditor) {
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
    document.getElementById('saveBoard').addEventListener('click', () => saveBoard(false));

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
            setStatus('Board name is required.', false);
            return;
        }

        setStatus(auto ? 'Auto-saving...' : 'Saving...');
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
            setStatus(auto ? 'Auto-saved.' : 'Saved.');
            if (boardId && !window.location.pathname.endsWith(`/boards/${boardId}`)) {
                window.history.replaceState({}, '', `/boards/${boardId}`);
            }
        } catch (error) {
            console.error(error);
            setStatus(error.message || 'Save failed.', false);
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
