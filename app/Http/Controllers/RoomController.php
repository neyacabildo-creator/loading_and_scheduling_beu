<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Display all rooms
     */
    public function index(Request $request)
    {
        $rooms = Room::all();

        // Derive sections from class_schedules
        $sections = ClassSchedule::select('grade_level', 'section_name')
            ->whereNotNull('section_name')
            ->where('section_name', '!=', '')
            ->distinct()
            ->orderBy('grade_level')
            ->orderBy('section_name')
            ->get()
            ->map(function ($s) {
                return [
                    'grade_section' => trim($s->grade_level . ' - ' . $s->section_name, ' -'),
                    'grade_level'   => $s->grade_level,
                    'section_name'  => $s->section_name,
                    'schedule_count'=> ClassSchedule::where('grade_level', $s->grade_level)
                                          ->where('section_name', $s->section_name)->count(),
                ];
            });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $rooms]);
        }

        return view('junior-high-admin.rooms-sections.index', compact('rooms', 'sections'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('junior-high-admin.rooms.create');
    }

    /**
     * Store a new room
     */
    public function store(Request $request)
    {
        $conn = (new Room)->getConnectionName();
        $validated = $request->validate([
            'room_number'     => ['required', 'string', 'max:50', Rule::unique("$conn.rooms", 'room_number')],
            'building'        => 'required|string|max:100',
            'capacity'        => 'required|integer|min:1|max:200',
            'has_laboratory'  => 'nullable|boolean',
            'has_projector'   => 'nullable|boolean',
            'has_ac'          => 'nullable|boolean',
            'status'          => 'required|in:available,in-use,maintenance',
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
    public function show($room, Request $request)
    {
        $room = Room::findOrFail($room);
        if ($request->wantsJson()) {
            return response()->json($room);
        }

        return view('junior-high-admin.rooms.edit', compact('room'));
    }

    /**
     * Show edit form
     */
    public function edit($room)
    {
        $room = Room::findOrFail($room);
        return view('junior-high-admin.rooms.edit', compact('room'));
    }

    /**
     * Update room
     */
    public function update(Request $request, $room)
    {
        $room = Room::findOrFail($room);
        $conn = (new Room)->getConnectionName();
        $validated = $request->validate([
            'room_number'     => ['required', 'string', 'max:50', Rule::unique("$conn.rooms", 'room_number')->ignore($room->id)],
            'building'        => 'required|string|max:100',
            'capacity'        => 'required|integer|min:1|max:200',
            'has_laboratory'  => 'nullable|boolean',
            'has_projector'   => 'nullable|boolean',
            'has_ac'          => 'nullable|boolean',
            'status'          => 'required|in:available,in-use,maintenance',
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
    public function destroy(Request $request, $room)
    {
        $room = Room::findOrFail($room);
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
