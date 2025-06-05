<?php

namespace App\Http\Controllers;

use App\Events\CounterEvent;
use App\Events\QueueListUpdated;
use App\Events\ClientTransferred;
use App\Models\QueuesModel;
use App\Models\QueueLogs;
use App\Models\Clients;
use App\Events\ClientUpdated;
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
                'queues.priority_level_id',
                'p.level_name as priority_level',
                'c.full_name',
                'c.id as client_id',
                's.name as service_name',
                'sc.counter_name',
                'queues.status',
                'queues.queued_at'
            )
            ->where('u.service_counter_id', $counterId)
            ->whereIn('queues.status', ['ongoing','waiting', 'serving'])
            ->orderByRaw("
            CASE 
                WHEN p.level_name = 'PWD' THEN 0
                WHEN p.level_name = 'SENIOR' THEN 1
                WHEN p.level_name = 'PREGNANT WOMEN' THEN 2
                ELSE 3
            END
        ")
            ->orderByRaw('CASE WHEN p.id = 1 THEN 0 ELSE 1 END')
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
            event(new CounterEvent($client->counter_id, $client->queue_number, $client->status));


            $cur_counter = QueuesModel::where('counter_id', $client->counter_id)
                ->select('queues.id', 'queues.priority_level_id', 'queues.queue_number', 'queues.counter_id', 'counter_name', 'queues.status', 'queues.queued_at', 'queues.called_at')
                ->leftJoin('service_counters as sc', 'queues.counter_id', '=', 'sc.id')
                ->orderBy('updated_at', 'desc')
                ->first();
            event(new QueueListUpdated($cur_counter['counter_name'], $cur_counter['queue_number'], $cur_counter['called_at']));

            //CounterEvent(4,RO3)
            return response()->json(['message' => 'Status updated successfully'], 200);
        }


        // return response()->json(['queue_number' => $queueNumber]);
        return response()->json(['message' => 'Client not found'], 404);
    }
    public function set_client_priority(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'priority_level_id' => 'required|integer',  // e.g., 'Completed'
        ]);

        $client = QueuesModel::find($validated['id']); // Assuming Client is your model

        if ($client) {
            $client->priority_level_id = $validated['priority_level_id'];
            $client->save();
            event(new ClientUpdated($client));

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
            ->limit(3)
            ->get();

        return response()->json($counters);
    }
   public function transfer_client(Request $request)
{
    $validated = $request->validate([
        'queue_id' => 'required|integer',
        'selectedCounter' => 'required|integer',
    ]);

    $client = QueuesModel::with('service_counter')->find($validated['queue_id']);

    if ($client) {
        $oldCounterId = $client->counter_id;
        $oldCounterName = optional($client->service_counter)->counter_name ?? 'Unknown';

        // Clear the old counter's queue number
        event(new CounterEvent($oldCounterId, null,$client->status));

        // Change to new counter
        $client->counter_id = $validated['selectedCounter'];
        $client->save();

        $newCounter = \App\Models\ServiceCounter::find($validated['selectedCounter']);
        $newCounterName = optional($newCounter)->counter_name ?? 'Unknown';

        // Fire new counter event with new queue number
        event(new CounterEvent($client->counter_id, $client->queue_number, $client->status));

        // Optional: if you're using a custom event for logging transfer
        event(new ClientTransferred(
            $client->queue_number,
            $oldCounterId,
            $oldCounterName,
            $client->counter_id,
            $newCounterName
        ));

        return response()->json([
            'message' => 'Transfer completed successfully',
            'queue_number' => $client->queue_number,
            'old_counter_name' => $oldCounterName,
            'new_counter_name' => $newCounterName
        ]);
    }

    return response()->json(['message' => 'Client not found'], 404);
}
    // public function transfer_client(Request $request)
    // {
    //     $validated = $request->validate([
    //         'queue_id' => 'required|integer',
    //         'selectedCounter' => 'required|integer',
    //     ]);

    //     $client = QueuesModel::find($validated['queue_id']); // Assuming Client is your model

    //     if ($client) {
    //         $oldCounterId = $client->counter_id; // Save old counter

    //         $client->counter_id = $validated['selectedCounter'];
    //         $client->save();

    //         event(new ClientUpdated($client, $oldCounterId));

    //         return response()->json(['message' => 'Transfer completed successfully'], 200);
    //     }


    //     return response()->json(['message' => 'Client not found'], 404);
    // }

}
