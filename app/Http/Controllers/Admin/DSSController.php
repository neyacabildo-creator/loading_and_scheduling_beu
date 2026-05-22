<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DSSEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * DSSController — Decision Support System API for admin panels.
 *
 * Endpoints are consumed by the DSS recommendations blade via fetch().
 * School DB connection is already scoped by the calling route's middleware
 * (school.db:mysql_jh or school.db:mysql_gs).
 */
class DSSController extends Controller
{
    /**
     * Run the DSS engine and return the full analysis as JSON.
     * POST /api/admin/dss/analyze
     */
    public function analyze(Request $request): JsonResponse
    {
        try {
            $engine = new DSSEngine();
            $result = $engine->analyze();

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('DSS analysis failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'DSS analysis encountered an error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return only the workload summary portion.
     * GET /api/admin/dss/workload
     */
    public function workload(Request $request): JsonResponse
    {
        try {
            $engine = new DSSEngine();
            $result = $engine->analyze();

            return response()->json([
                'success' => true,
                'data'    => $result['workload_summary'],
            ]);
        } catch (\Exception $e) {
            Log::error('DSS workload summary failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Return only the room allocation portion.
     * GET /api/admin/dss/rooms
     */
    public function rooms(Request $request): JsonResponse
    {
        try {
            $engine = new DSSEngine();
            $result = $engine->analyze();

            return response()->json([
                'success' => true,
                'data'    => $result['room_allocation'],
            ]);
        } catch (\Exception $e) {
            Log::error('DSS room allocation failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Return high-priority notifications only.
     * GET /api/admin/dss/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        try {
            $engine = new DSSEngine();
            $result = $engine->analyze();

            return response()->json([
                'success' => true,
                'data'    => $result['notifications'],
                'count'   => count($result['notifications']),
            ]);
        } catch (\Exception $e) {
            Log::error('DSS notifications failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
