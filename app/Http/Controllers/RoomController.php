<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display all rooms
     */
    public function index(Request $request)
    {
        $rooms = Room::all();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $rooms]);
        }

        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.rooms.create');
    }

    /**
     * Store a new room
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_number'     => 'required|string|max:50|unique:rooms,room_number',
            'building'        => 'required|string|max:100',
            'capacity'        => 'required|integer|min:1|max:200',
            'has_laboratory'  => 'nullable|boolean',
            'has_projector'   => 'nullable|boolean',
            'has_ac'          => 'nullable|boolean',
            'status'          => 'required|in:available,unavailable,maintenance',
        ]);

        // Handle checkboxes
        $validated['has_laboratory'] = $request->boolean('has_laboratory');
        $validated['has_projector']  = $request->boolean('has_projector');
        $validated['has_ac']         = $request->boolean('has_ac');

        $room = Room::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room added successfully',
                'data' => $room
            ], 201);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room added successfully!');
    }

    /**
     * Show single room (JSON or Edit page)
     */
    public function show(Room $room, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($room);
        }

        return view('admin.rooms.edit', compact('room'));
    }

    /**
     * Show edit form
     */
    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    /**
     * Update room
     */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_number'     => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'building'        => 'required|string|max:100',
            'capacity'        => 'required|integer|min:1|max:200',
            'has_laboratory'  => 'nullable|boolean',
            'has_projector'   => 'nullable|boolean',
            'has_ac'          => 'nullable|boolean',
            'status'          => 'required|in:available,unavailable,maintenance',
        ]);

        // Handle checkboxes
        $validated['has_laboratory'] = $request->boolean('has_laboratory');
        $validated['has_projector']  = $request->boolean('has_projector');
        $validated['has_ac']         = $request->boolean('has_ac');

        $room->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => $room
            ]);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room updated successfully!');
    }

    /**
     * Delete room
     */
    public function destroy(Request $request, Room $room)
    {
        $room->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully!'
            ]);
        }

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room deleted successfully!');
    }

    /**
     * API: Get all rooms
     */
    public function getRoomsApi()
    {
        return response()->json([
            'data' => Room::all()
        ]);
    }
}
