<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Boards</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f6f7f9; color: #172033; }
        .page { max-width: 1040px; margin: 0 auto; padding: 28px; }
        .header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        h1 { margin: 0 0 6px; font-size: 2rem; line-height: 1.1; }
        .muted { color: #667085; margin: 0; }
        .panel, .board-card { background: #fff; border: 1px solid #d9dee8; border-radius: 8px; box-shadow: 0 8px 22px rgba(20, 31, 51, .06); }
        .panel { padding: 16px; margin-bottom: 18px; }
        .create-form, .rename-form { display: flex; gap: 10px; align-items: center; }
        input { width: 100%; min-width: 0; padding: 10px 12px; border: 1px solid #b8c0cc; border-radius: 6px; font: inherit; }
        button, .button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 9px 14px; border: 1px solid #9aa4b2; border-radius: 6px; background: #fff; color: #172033; font: inherit; text-decoration: none; cursor: pointer; white-space: nowrap; }
        button:hover, .button:hover { background: #f3f5f8; }
        .primary { background: #1f6feb; color: #fff; border-color: #1f6feb; }
        .primary:hover { background: #1a5fc9; }
        .danger { color: #b42318; border-color: #f3b8b1; }
        .danger:hover { background: #fff1f0; }
        .status { margin: 0 0 16px; padding: 10px 12px; border-radius: 6px; background: #e9f7ef; color: #16703c; }
        .errors { margin: 0 0 16px; padding: 10px 12px; border-radius: 6px; background: #fff1f0; color: #b42318; }
        .board-list { display: grid; gap: 12px; }
        .board-card { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; padding: 16px; }
        .board-title { color: #175cd3; font-weight: 700; text-decoration: none; }
        .board-title:hover { text-decoration: underline; }
        .actions { display: flex; align-items: center; gap: 8px; }
        .delete-form { margin: 0; }
        @media (max-width: 720px) {
            .header, .board-card, .create-form, .rename-form, .actions { display: grid; grid-template-columns: 1fr; }
            .actions { width: 100%; }
            button, .button { width: 100%; }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="header">
        <div>
            <h1>Saved boards</h1>
            <p class="muted">{{ $boards->count() }} saved board{{ $boards->count() === 1 ? '' : 's' }}.</p>
        </div>
        <a href="{{ route('boards.create') }}" class="button">Blank canvas</a>
    </header>

    @if(session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    @if($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <form method="POST" action="{{ route('boards.store') }}" class="create-form">
            @csrf
            <input name="name" type="text" placeholder="New board name" value="{{ old('name') }}" required>
            <input name="canvas_data" type="hidden" value="">
            <button type="submit" class="primary">Create board</button>
        </form>
    </section>

    @if($boards->isEmpty())
        <article class="board-card">
            <div>
                <strong>No boards yet</strong>
                <p class="muted">Create a board to start drawing.</p>
            </div>
        </article>
    @else
        <section class="board-list">
            @foreach($boards as $board)
                <article class="board-card">
                    <div>
                        <a href="{{ route('boards.show', $board) }}" class="board-title">{{ $board->name }}</a>
                        <p class="muted">Updated {{ $board->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="actions">
                        <form method="POST" action="{{ route('boards.update', $board) }}" class="rename-form">
                            @csrf
                            @method('PUT')
                            <input name="name" type="text" value="{{ $board->name }}" aria-label="Rename {{ $board->name }}" required>
                            <input name="canvas_data" type="hidden" value="{{ $board->canvas_data }}">
                            <button type="submit">Rename</button>
                        </form>
                        <form method="POST" action="{{ route('boards.destroy', $board) }}" class="delete-form" onsubmit="return confirm('Delete this board?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">Delete</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</main>
</body>
</html>
