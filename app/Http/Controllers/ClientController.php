<?php

namespace App\Http\Controllers;
use App\Events\CounterEvent;
use App\Models\QueuesModel;
use App\Models\QueueLogs;
use App\Models\Clients;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

use DB;

class ClientController extends Controller
{
    public function index()
    {
        return Inertia::render('client/index', [
            'task' => QueuesModel::all(),
        ]);
    }

    public function get_client(Request $request)
    {
        $counterId = $request->query('counter_id');

        $queues = QueuesModel::with(['client', 'client.priorityLevel', 'serviceCounter'])
            ->join('clients as c', 'queues.client_id', '=', 'c.id')
            ->join('services as s', 'queues.service_id', '=', 's.id')
            ->join('service_counters as sc', 'queues.counter_id', '=', 'sc.id')
            ->join('priority_levels as p', 'queues.priority_level_id', '=', 'p.id')
            ->join('users as u', 'queues.counter_id', '=', 'u.service_counter_id')
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
            ->where('u.service_counter_id', $counterId)
            ->whereIn('queues.status', ['waiting', 'serving'])
            ->orderByRaw("
            CASE 
                WHEN p.level_name = 'PWD' THEN 0
                WHEN p.level_name = 'SENIOR' THEN 1
                WHEN p.level_name = 'PREGNANT WOMEN' THEN 2
                ELSE 3
            END
        ")
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
            $client->is_called = 1;
            $client->called_at = Carbon::now();
            $client->save();
            event(new CounterEvent($client->counter_id, $client->queue_number,$client->status));
            //CounterEvent(4,RO3)
            return response()->json(['message' => 'Status updated successfully'], 200);
        }
        

        // return response()->json(['queue_number' => $queueNumber]);
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
    public function save_queue_logs(Request $request)
    {
        $request->validate([
            'queue_id' => 'required|integer|exists:queues,id', // Make sure queue_id exists in `queues` table
            'served_by' => 'required|string',
        ]);

        $log = QueueLogs::create([
            'queue_id' => $request->queue_id,
            'served_by' => $request->served_by,
            'served_at' => now(), // Add current timestamp
            'remarks' => $request->remarks ?? null, // Optional
        ]);

        return response()->json([
            'message' => 'Queue log created successfully.',
            'data' => $log
        ]);
    }
    public function get_counter(Request $request)
    {
        $counters = DB::table('service_counters')
            ->select('service_counters.id as counter_id', 'service_counters.counter_name')
            ->get();

        return response()->json($counters);
    }
    public function transfer_client(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|integer',
            'selectedCounter' => 'required|integer',
        ]);

        $client = QueuesModel::find($validated['queue_id']); // Assuming Client is your model

        if ($client) {
            $client->counter_id = $validated['selectedCounter'];
            $client->save();

            return response()->json(['message' => 'Transfer completed successfully'], 200);
        }

        return response()->json(['message' => 'Client not found'], 404);
    }
    public function recall(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|integer',
            'selectedClient' => 'required|integer',
        ]);

        $client = QueuesModel::find($validated['queue_id']); // Assuming Client is your model

        if ($client) {
            $client->status = 'recall';
            $client->save();


            return response()->json(['message' => 'Recall completed successfully'], 200);
        }

        return response()->json(['message' => 'Client not found'], 404);
    }
}
