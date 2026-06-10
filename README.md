# Infinite Canvas Whiteboard

A Laravel and Konva.js whiteboard app for creating, editing, saving, renaming, and deleting diagram-style boards.

## Stack

- Laravel
- Blade templates with inline JavaScript
- Konva.js loaded by CDN
- SQLite by default

## Features

- Full-screen infinite canvas
- Pan by dragging the background
- Mouse wheel zoom from 20% to 300%
- Zoom percentage and reset view controls
- Tools: Select, Freehand, Rectangle, Circle, Line, Arrow, Text
- Color picker and stroke width selector
- Shape selection, dragging, resizing, and delete/backspace removal
- Editable text labels by double-clicking text
- Save and reload canvas state as Konva JSON
- 60-second autosave when the board has changed
- Saved boards list with create, open, rename, and delete
- Duplicate board names are rejected

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

The default database is SQLite:

```env
DB_CONNECTION=sqlite
```

Create the SQLite database file if it does not exist:

```bash
type nul > database\database.sqlite
```

Run migrations:

```bash
php artisan migrate
```

Start the app:

```bash
php artisan serve
```

Open the local URL shown by Artisan and visit `/boards`.

## API Routes

- `GET /api/boards`
- `POST /api/boards`
- `GET /api/boards/{id}`
- `PUT /api/boards/{id}`
- `DELETE /api/boards/{id}`

Board JSON is stored in the `boards.canvas_data` column using Konva's `stage.toJSON()` output.
