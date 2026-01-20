<?php

namespace App\Http\Controllers;

use App\Models\FacultyLoad;
use App\Models\User;
use Illuminate\Http\Request;

class FacultyLoadController extends Controller
{
    /**
     * Display all faculty loads
     */
    public function index(Request $request)
    {
        $facultyLoads = FacultyLoad::with('faculty')->get();
        
        // Check if this is an API request
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $facultyLoads]);
        }
        
        return view('admin.faculty-loads.index', ['facultyLoads' => $facultyLoads]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $teachers = User::whereHas('role', function($q) { $q->where('name', 'teacher'); })->get();
        return view('admin.faculty-loads.create', ['teachers' => $teachers]);
    }

    /**
     * Store a new faculty load
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'faculty_id' => 'required|exists:users,id',
                'department' => 'required|string|max:100',
                'classes_assigned' => 'required|integer|min:1',
                'load_hours' => 'required|numeric|min:0.5|max:999.99',
                'status' => 'required|in:active,inactive',
                'notes' => 'nullable|string|max:500',
            ]);

            $facultyLoad = FacultyLoad::create($validated);
            $facultyLoad->load('faculty');

            // Return JSON response for API requests
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Faculty load added successfully', 'data' => $facultyLoad], 201);
            }

            return redirect()->route('admin.faculty-loads.index')
                ->with('success', 'Faculty load added successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Show edit form or get faculty load details (JSON API)
     */
    public function show(FacultyLoad $facultyLoad, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($facultyLoad->load('faculty'));
        }

        $teachers = User::whereHas('role', function($q) { $q->where('name', 'teacher'); })->get();
        return view('admin.faculty-loads.edit', ['facultyLoad' => $facultyLoad, 'teachers' => $teachers]);
    }

    /**
     * Show edit form
     */
    public function edit(FacultyLoad $facultyLoad)
    {
        $teachers = User::whereHas('role', function($q) { $q->where('name', 'teacher'); })->get();
        return view('admin.faculty-loads.edit', ['facultyLoad' => $facultyLoad, 'teachers' => $teachers]);
    }

    /**
     * Update a faculty load (JSON API version)
     */
    public function update(Request $request, FacultyLoad $facultyLoad)
    {
        // Check if this is an API request
        if ($request->wantsJson()) {
            $validated = $request->validate([
                'load_hours' => 'nullable|numeric|min:0.5|max:999.99',
                'status' => 'nullable|in:active,inactive,adjusted',
                'classes_assigned' => 'nullable|string|max:500',
            ]);

            $facultyLoad->update($validated);
            return response()->json(['success' => true, 'data' => $facultyLoad]);
        }

        // Web form version
        $validated = $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'department' => 'required|string|max:100',
            'classes_assigned' => 'required|integer|min:1',
            'load_hours' => 'required|numeric|min:0.5|max:999.99',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:500',
        ]);

        $facultyLoad->update($validated);

        return redirect()->route('admin.faculty-loads.index')
            ->with('success', 'Faculty load updated successfully!');
    }

    /**
     * Delete a faculty load (JSON API version)
     */
    public function destroy(Request $request, FacultyLoad $facultyLoad)
    {
        $facultyLoad->delete();

        // Return JSON for API requests
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Faculty load deleted successfully!']);
        }

        return redirect()->route('admin.faculty-loads.index')
            ->with('success', 'Faculty load deleted successfully!');
    }

    /**
     * Get all faculty loads as JSON (for API)
     */
    public function getFacultyLoadsApi()
    {
        return response()->json([
            'data' => FacultyLoad::with('faculty')->get()
        ]);
    }
}
