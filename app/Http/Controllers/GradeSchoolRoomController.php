<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class GradeSchoolRoomController extends Controller
{
    /**
     * Display all Grade School rooms
     */
    public function index(Request $request)
    {
        $rooms = Room::all();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $rooms]);
        }

        return view('grade-school-admin.rooms-sections.index', compact('rooms'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('grade-school-admin.rooms.create');
    }

    /**
     * Store a new room
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'capacity' => 'required|integer|min:1|max:200',
            'status'   => 'required|in:available,in-use,maintenance',
        ]);

        $room = Room::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room added successfully',
                'data'    => $room,
            ], 201);
        }

        return redirect()->route('grade-school-admin.rooms.index')
            ->with('success', 'Room added successfully!');
    }

    /**
     * Show single room
     */
    public function show($id, Request $request)
    {
        $room = Room::findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($room);
        }

        return view('grade-school-admin.rooms.edit', compact('room'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $room = Room::findOrFail($id);
        return view('grade-school-admin.rooms.edit', compact('room'));
    }

    /**
     * Update room
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $validated = $request->validate([
            'capacity' => 'required|integer|min:1|max:200',
            'status'   => 'required|in:available,in-use,maintenance',
        ]);

        $room->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data'    => $room,
            ]);
        }

        return redirect()->route('grade-school-admin.rooms.index')
            ->with('success', 'Room updated successfully!');
    }

    /**
     * Delete room
     */
    public function destroy(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully!',
            ]);
        }

        return redirect()->route('grade-school-admin.rooms.index')
            ->with('success', 'Room deleted successfully!');
    }
}
