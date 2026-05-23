<?php

namespace App\Http\Controllers;

use App\Models\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionRequestController extends Controller
{
    /** Show the admin's own requests + form to create new ones */
    public function index()
    {
        $myRequests = PermissionRequest::where('requester_id', Auth::id())
            ->with('reviewer')
            ->orderByDesc('created_at')
            ->paginate(20);

        $actionTypes = PermissionRequest::ACTION_TYPES;

        return view('admin.permission-requests', compact('myRequests', 'actionTypes'));
    }

    /** Submit a new request to the Principal */
    public function store(Request $request)
    {
        $data = $request->validate([
            'action_type'   => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(PermissionRequest::ACTION_TYPES))],
            'details'       => 'required|string|max:2000',
            'related_model' => 'nullable|string|max:100',
            'related_id'    => 'nullable|integer|min:1',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        PermissionRequest::create([
            'requester_id'  => $user->id,
            'action_type'   => $data['action_type'],
            'details'       => $data['details'],
            'school_level'  => $user->school_level,
            'related_model' => $data['related_model'] ?? null,
            'related_id'    => $data['related_id'] ?? null,
            'status'        => 'pending',
        ]);

        return back()->with('success', 'Your request has been sent to the Principal.');
    }

    /** Cancel an own pending request */
    public function cancel(PermissionRequest $permissionRequest)
    {
        if ($permissionRequest->requester_id !== Auth::id()) {
            abort(403);
        }

        if (!$permissionRequest->isPending()) {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        $permissionRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Request cancelled.');
    }
}
