<?php

namespace App\Http\Controllers;

use App\Models\Whiteboard;
use Illuminate\Http\Request;

class WhiteboardController extends Controller
{
    public function index()
    {
        return view('whiteboards.index', [
            'whiteboards' => Whiteboard::orderBy('updated_at', 'desc')->get(),
        ]);
    }

    public function create()
    {
        return view('whiteboard', [
            'whiteboard' => null,
        ]);
    }

    public function show(Whiteboard $whiteboard)
    {
        return view('whiteboard', [
            'whiteboard' => $whiteboard,
        ]);
    }

    public function destroy(Whiteboard $whiteboard)
    {
        $whiteboard->delete();

        return redirect()->route('whiteboards.index');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'id' => ['nullable', 'integer', 'exists:whiteboards,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'canvas_data' => ['nullable', 'string'],
        ]);

        if (! empty($payload['id'])) {
            $whiteboard = Whiteboard::find($payload['id']);
            if ($whiteboard) {
                $whiteboard->update([
                    'title' => $payload['title'] ?? 'Untitled Whiteboard',
                    'canvas_data' => $payload['canvas_data'] ?? '',
                ]);
            } else {
                $whiteboard = Whiteboard::create([
                    'title' => $payload['title'] ?? 'Untitled Whiteboard',
                    'canvas_data' => $payload['canvas_data'] ?? '',
                ]);
            }
        } else {
            $whiteboard = Whiteboard::create([
                'title' => $payload['title'] ?? 'Untitled Whiteboard',
                'canvas_data' => $payload['canvas_data'] ?? '',
            ]);
        }

        return response()->json([
            'id' => $whiteboard->id,
            'title' => $whiteboard->title,
        ]);
    }
}
