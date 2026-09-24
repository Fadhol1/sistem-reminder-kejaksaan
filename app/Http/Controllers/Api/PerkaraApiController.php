<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perkara;
use App\Services\PerkaraWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerkaraApiController extends Controller
{
    /**
     * Get detail of a specific case
     */
    public function show($id)
    {
        $perkara = Perkara::with(['timelines.user', 'activeReminders'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $perkara
        ]);
    }

    /**
     * Get available actions dynamically based on the current state.
     */
    public function getAvailableActions($id)
    {
        $perkara = Perkara::findOrFail($id);
        
        $actions = PerkaraWorkflowService::getAvailableActions($perkara);

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_stage' => $perkara->tahap_saat_ini,
                'current_status' => $perkara->status_saat_ini,
                'available_actions' => $actions
            ]
        ]);
    }

    /**
     * Process an action triggered by the client.
     */
    public function processAction(Request $request, $id)
    {
        $perkara = Perkara::findOrFail($id);
        
        $validated = $request->validate([
            'action_id' => 'required|string',
            'tanggal' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        $availableActions = PerkaraWorkflowService::getAvailableActions($perkara);
        $actionExists = collect($availableActions)->contains('id', $validated['action_id']);
        
        if (!$actionExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aksi tidak valid untuk tahap dan status perkara saat ini.'
            ], 422);
        }

        try {
            // Default to current user or systematic user if logic demands
            // For now, API relies on token/Auth
            $userId = Auth::id() ?? 1; 

            PerkaraWorkflowService::processAction(
                $perkara, 
                $validated['action_id'], 
                $validated, 
                $userId
            );

            // Refetch to get updated state
            $perkara->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Action successfully processed',
                'data' => [
                    'new_stage' => $perkara->tahap_saat_ini,
                    'new_status' => $perkara->status_saat_ini,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }
}
