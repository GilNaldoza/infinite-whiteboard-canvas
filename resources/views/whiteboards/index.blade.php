<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Boards | Infinite Whiteboard</title>
    <meta name="description" content="Manage your infinite whiteboards — create, open, rename, and delete drawing boards.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f0f3f9;
            --surface: #fff;
            --surface-hover: #f7f9fc;
            --border: #e0e6f0;
            --border-hover: #c4cfdf;
            --text: #1a2540;
            --muted: #6b7a99;
            --accent: #3b73f5;
            --accent-light: #eef3fe;
            --accent-hover: #2563eb;
            --success: #16a34a;
            --success-bg: #e8f7ef;
            --danger: #dc2626;
            --danger-bg: #fff1f0;
            --danger-border: #fecaca;
            --radius: 14px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --shadow-card: 0 2px 8px rgba(20,40,80,0.07), 0 0 0 1px var(--border);
            --shadow-card-hover: 0 8px 28px rgba(20,40,80,0.12), 0 0 0 1px var(--border-hover);
            --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Background decoration ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 40% at 10% 0%, rgba(59,115,245,0.07) 0%, transparent 70%),
                radial-gradient(ellipse 50% 50% at 90% 100%, rgba(107,72,255,0.06) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .page {
            position: relative;
            z-index: 1;
            max-width: 1080px;
            margin: 0 auto;
            padding: 40px 28px 80px;
        }

        /* ── Header ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #3b73f5 0%, #6b48ff 100%);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(59,115,245,0.35);
        }

        .header-text h1 {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: var(--text);
        }

        .header-text p {
            font-size: 0.875rem;
            color: var(--muted);
            margin-top: 3px;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            height: 40px;
            padding: 0 18px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text);
            font: 500 13.5px 'Inter', sans-serif;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
            transition: background var(--transition), border-color var(--transition), box-shadow var(--transition), transform var(--transition);
        }
        .btn:hover { background: var(--surface-hover); border-color: var(--border-hover); box-shadow: 0 2px 8px rgba(20,40,80,0.08); }
        .btn:active { transform: scale(0.97); }
        .btn svg { flex-shrink: 0; }

        .btn-primary {
            background: linear-gradient(135deg, #3b73f5, #6b48ff);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 3px 12px rgba(59,115,245,0.35);
        }
        .btn-primary:hover { box-shadow: 0 5px 18px rgba(59,115,245,0.45); transform: translateY(-1px); }
        .btn-primary:active { transform: scale(0.97) translateY(0); }

        .btn-danger { color: var(--danger); border-color: var(--danger-border); background: transparent; }
        .btn-danger:hover { background: var(--danger-bg); border-color: var(--danger); }

        .btn-sm { height: 34px; padding: 0 12px; font-size: 12.5px; }

        /* ── Alerts ── */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            margin-bottom: 20px;
            animation: slide-down 0.3s ease both;
        }
        @keyframes slide-down {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #a7ddb8; }
        .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }

        /* ── Create Panel ── */
        .create-panel {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1.5px dashed rgba(59,115,245,0.3);
            padding: 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .create-panel:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,115,245,0.1);
        }

        .create-panel-icon {
            width: 38px; height: 38px;
            background: var(--accent-light);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--accent);
            flex-shrink: 0;
        }

        .create-panel label {
            font: 600 13px 'Inter', sans-serif;
            color: var(--text);
            flex-shrink: 0;
            margin-right: 4px;
        }

        .create-input {
            flex: 1;
            min-width: 200px;
            height: 40px;
            padding: 0 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            color: var(--text);
            font: 400 14px 'Inter', sans-serif;
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .create-input::placeholder { color: #a0aac0; }
        .create-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,115,245,0.12); background: #fff; }

        /* ── Boards count & filters ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .section-title {
            font: 600 13px 'Inter', sans-serif;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .count-badge {
            display: inline-flex; align-items: center; justify-content: center;
            height: 22px;
            padding: 0 8px;
            border-radius: 999px;
            background: var(--accent-light);
            color: var(--accent);
            font: 600 11.5px 'Inter', sans-serif;
        }

        /* ── Board Grid ── */
        .board-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        /* ── Board Card ── */
        .board-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow var(--transition), transform var(--transition);
            animation: card-in 0.35s cubic-bezier(0.34, 1.2, 0.64, 1) both;
        }
        .board-card:hover { box-shadow: var(--shadow-card-hover); transform: translateY(-2px); }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .board-card:nth-child(1) { animation-delay: 0.04s; }
        .board-card:nth-child(2) { animation-delay: 0.08s; }
        .board-card:nth-child(3) { animation-delay: 0.12s; }
        .board-card:nth-child(4) { animation-delay: 0.16s; }
        .board-card:nth-child(n+5) { animation-delay: 0.2s; }

        /* Card canvas preview */
        .board-preview {
            height: 130px;
            background: linear-gradient(145deg, #f0f3f9, #e8ecf5);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .board-preview::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(100,120,180,0.2) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .board-preview-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(59,115,245,0.6);
            backdrop-filter: blur(8px);
            position: relative;
        }

        .board-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .board-name-link {
            font: 600 15px 'Inter', sans-serif;
            color: var(--text);
            text-decoration: none;
            line-height: 1.3;
            transition: color var(--transition);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .board-name-link:hover { color: var(--accent); }

        .board-meta {
            font: 400 12px 'Inter', sans-serif;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .board-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
            border-top: 1px solid var(--border);
            background: rgba(240,243,249,0.5);
        }

        .board-actions .btn { flex: 1; justify-content: center; }

        /* Rename panel inside card */
        .rename-panel {
            display: none;
            padding: 12px 16px;
            border-top: 1px solid var(--border);
            gap: 8px;
            flex-direction: column;
        }
        .board-card.is-renaming .rename-panel { display: flex; animation: fade-in 0.2s ease; }
        .board-card.is-renaming .board-actions { display: none; }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }

        .rename-label {
            font: 600 12px 'Inter', sans-serif;
            color: var(--muted);
        }
        .rename-row { display: flex; gap: 6px; align-items: center; }
        .rename-input {
            flex: 1;
            height: 36px;
            padding: 0 10px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-xs);
            font: 400 13px 'Inter', sans-serif;
            color: var(--text);
            background: var(--bg);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }
        .rename-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,115,245,0.12); background: #fff; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 72px 32px;
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-card);
            animation: card-in 0.35s ease both;
        }
        .empty-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--accent-light), #ede8ff);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            color: var(--accent);
        }
        .empty-state h2 { font: 700 1.3rem 'Inter', sans-serif; margin-bottom: 8px; color: var(--text); }
        .empty-state p { color: var(--muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6; }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .page { padding: 24px 16px 60px; }
            .page-header { flex-direction: column; gap: 14px; }
            .board-grid { grid-template-columns: 1fr; }
            .create-panel { flex-direction: column; align-items: stretch; }
        }

        /* ── Skeleton loading ── */
        .delete-form { display: contents; }
    </style>
</head>
<body>
<main class="page">
    <!-- Header -->
    <header class="page-header">
        <div class="header-brand">
            <div class="brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <div class="header-text">
                <h1>Infinite Whiteboard</h1>
                <p>Your creative workspace</p>
            </div>
        </div>
        <a href="{{ route('boards.create') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Blank canvas
        </a>
    </header>

    @if(session('status'))
        <div class="alert alert-success" role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error" role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Create new board -->
    <div class="create-panel">
        <div class="create-panel-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
        </div>
        <form method="POST" action="{{ route('boards.store') }}" style="display:contents">
            @csrf
            <label for="new-board-name">New board</label>
            <input
                id="new-board-name"
                class="create-input"
                name="name"
                type="text"
                placeholder="Give your board a name…"
                value="{{ old('name') }}"
                required
                autocomplete="off"
                maxlength="100"
            >
            <input name="canvas_data" type="hidden" value="">
            <button type="submit" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Create
            </button>
        </form>
    </div>

    @if($boards->isEmpty())
        <!-- Empty state -->
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                </svg>
            </div>
            <h2>No boards yet</h2>
            <p>Create your first whiteboard above and start drawing.<br>Your work auto-saves every minute.</p>
            <a href="{{ route('boards.create') }}" class="btn btn-primary" style="margin: 0 auto; display: inline-flex;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Start drawing
            </a>
        </div>
    @else
        <!-- Section header -->
        <div class="section-header">
            <span class="section-title">Your boards</span>
            <span class="count-badge">{{ $boards->count() }}</span>
        </div>

        <!-- Board grid -->
        <section class="board-grid" aria-label="Saved boards">
            @foreach($boards as $board)
                <article class="board-card" id="card-{{ $board->id }}">
                    <!-- Preview area -->
                    <a href="{{ route('boards.show', $board) }}" class="board-preview" aria-hidden="true" tabindex="-1">
                        <div class="board-preview-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Body -->
                    <div class="board-body">
                        <a href="{{ route('boards.show', $board) }}" class="board-name-link">{{ $board->name }}</a>
                        <p class="board-meta">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Updated {{ $board->updated_at->diffForHumans() }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="board-actions summary-actions">
                        <a href="{{ route('boards.show', $board) }}" class="btn btn-sm btn-primary">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                            Open
                        </a>
                        <button type="button" class="btn btn-sm" data-rename-toggle>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                            </svg>
                            Rename
                        </button>
                        <form method="POST" action="{{ route('boards.destroy', $board) }}" class="delete-form" onsubmit="return confirm('Delete "{{ addslashes($board->name) }}"? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <!-- Rename panel -->
                    <div class="rename-panel">
                        <span class="rename-label">Rename board</span>
                        <form method="POST" action="{{ route('boards.update', $board) }}" class="rename-row">
                            @csrf
                            @method('PUT')
                            <input
                                class="rename-input"
                                name="name"
                                type="text"
                                value="{{ $board->name }}"
                                aria-label="Rename {{ $board->name }}"
                                required
                                maxlength="100"
                            >
                            <input name="canvas_data" type="hidden" value="{{ $board->canvas_data }}">
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            <button type="button" class="btn btn-sm" data-rename-cancel>Cancel</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</main>

<script>
    document.querySelectorAll('[data-rename-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.board-card');
            card.classList.add('is-renaming');
            const input = card.querySelector('.rename-input');
            if (input) {
                input.focus();
                input.select();
            }
        });
    });

    document.querySelectorAll('[data-rename-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.board-card').classList.remove('is-renaming');
        });
    });

    // Press Escape to cancel rename
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.board-card.is-renaming').forEach((card) => {
                card.classList.remove('is-renaming');
            });
        }
    });
</script>
</body>
</html>
