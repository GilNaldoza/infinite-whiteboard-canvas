<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BoardController extends Controller
{
    public function index()
    {
        return view('whiteboards.index', [
            'boards' => Board::orderBy('updated_at', 'desc')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('whiteboard', [
            'board' => null,
            'defaultName' => $request->query('name', 'Untitled board'),
        ]);
    }

    public function show(Board $board)
    {
        return view('whiteboard', [
            'board' => $board,
            'defaultName' => $board->name,
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:boards,name'],
            'canvas_data' => ['nullable', 'string'],
        ]);

        $board = Board::create([
            'name' => $payload['name'],
            'canvas_data' => $payload['canvas_data'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json($board, 201);
        }

        return redirect()->route('boards.show', $board);
    }

    public function update(Request $request, Board $board)
    {
        $payload = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('boards', 'name')->ignore($board->id),
            ],
            'canvas_data' => ['nullable', 'string'],
        ]);

        $board->update([
            'name' => $payload['name'],
            'canvas_data' => $payload['canvas_data'] ?? $board->canvas_data,
        ]);

        if ($request->expectsJson()) {
            return response()->json($board);
        }

        return back()->with('status', 'Board renamed.');
    }

    public function destroy(Request $request, Board $board)
    {
        $board->delete();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('boards.index')->with('status', 'Board deleted.');
    }

    public function apiIndex()
    {
        return Board::orderBy('updated_at', 'desc')->get();
    }

    public function apiStore(Request $request)
    {
        return $this->store($request);
    }

    public function apiShow(Board $board)
    {
        return $board;
    }

    public function apiUpdate(Request $request, Board $board)
    {
        return $this->update($request, $board);
    }

    public function apiDestroy(Request $request, Board $board)
    {
        return $this->destroy($request, $board);
    }
}
