<?php

namespace App\Http\Controllers;

use App\Models\QueuesModel;
use App\Models\Clients;
use Illuminate\Http\Request;
use Inertia\Inertia;
use DB;

class ClientController extends Controller
{
    public function get_client(Request $request)
    {
        $queues = QueuesModel::with(['client', 'client.priorityLevel', 'serviceCounter'])
            ->join('clients as c', 'queues.client_id', '=', 'c.id')
            ->join('services as s', 'queues.service_id', '=', 's.id')
            ->join('service_counters as sc', 'queues.counter_id', '=', 'sc.id')
            ->join('priority_levels as p', 'queues.priority_level_id', '=', 'p.id')
            ->select(
                'queues.id as queue_id',
                'queues.queue_number',
                'p.level_name as priority_level',
                'c.full_name',
                'c.id as client_id',
                's.name as service_name',
                'sc.counter_name',
                'queues.status',
                'queues.queued_at'
            )
            
            ->where('queues.status', 'waiting')
            ->limit(10)
            ->orderBy('queues.queued_at', 'asc')
            ->get();

        return response()->json($queues);
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|integer',
            'status' => 'required|string',  // e.g., 'Completed'
        ]);

        $client = QueuesModel::find($validated['queue_id']); // Assuming Client is your model

        if ($client) {
            $client->status = $validated['status'];
            $client->save();

            return response()->json(['message' => 'Status updated successfully'], 200);
        }

        return response()->json(['message' => 'Client not found'], 404);
    }
    public function set_client_priority(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer',
            'priority_level_id' => 'required|integer',  // e.g., 'Completed'
        ]);

        $client = QueuesModel::find($validated['client_id']); // Assuming Client is your model

        if ($client) {
            $client->priority_level_id = $validated['priority_level_id'];
            $client->save();

            return response()->json(['message' => 'Priority Level updated successfully'], 200);
        }

        return response()->json(['message' => 'Client not found'], 404);
    }
}
