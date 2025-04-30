<?php

namespace App\Http\Controllers;

use App\Events\CounterEvent;
use App\Models\QueuesModel;
use App\Models\Clients;
use App\Events\ClientUpdated;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use DB;

class CounterController extends Controller
{
    public function current_clients(Request $request)
    {
        $counterIds = [1, 2, 3, 4];
        $allData = collect();

        foreach ($counterIds as $id) {
            $results = DB::table('vw_counter_queue')
                ->where('service_counter_id', $id)
                ->whereIn('status', ['serving', 'waiting']) // Include 'waiting' as fallback
                ->orderByRaw("
                CASE 
                    WHEN priority_level = 'PWD' THEN 0
                    WHEN priority_level = 'SENIOR' THEN 1
                    WHEN priority_level = 'PREGNANT WOMEN' THEN 2
                    ELSE 3
                END
            ")
                ->orderBy('queued_at', 'asc')
                ->limit(1)
                ->get();

            $allData = $allData->merge($results);
        }

        return response()->json(['data' => $allData]);
    }

    public function queue_list(Request $request)
    {
        $data = DB::table('queues')
            ->where('is_called', 1)
            ->where('status', 'serving')
            ->orderBy('called_at', 'desc')
            ->limit(1)
            ->get();
        return response()->json([
            'data' => $data,
        ]);
    }
    public function update_client_transaction(Request $request)
    {
        $validated = $request->validate([
            'queue_id' => 'required|integer',
            'status' => 'required|string',
        ]);

        $client = QueuesModel::find($validated['queue_id']);

        if ($client) {
            $client->status = $validated['status'];
            $client->is_called = 0;
            $client->updated_at = now();
            $client->save();

            event(new CounterEvent($client->counter_id, "---", $client->status));

            return response()->json(['message' => 'Queue status updated successfully']);
        }


        return response()->json(['message' => 'Client not found'], 404);
    }

    public function transaction(Request $request)
    {
        $counterId = $request->input('counter_id');

        $priorityLevelId = $counterId == 2 ? 1 : 4;

        $prefix = match ($counterId) {
            1 => 'D',
            2 => 'E',
            3 => 'N',
            4 => 'R'
        };

        // Get latest queue number with matching prefix
        $latestQueue = DB::table('queues')
            ->where('counter_id', $counterId)
            ->where('queue_number', 'like', "$prefix%")
            ->orderByDesc('id')
            ->first();

        if ($latestQueue && preg_match('/\d+/', $latestQueue->queue_number, $matches)) {
            $currentNumber = (int) $matches[0];
            $nextNumber = $currentNumber >= 10 ? '01' : str_pad($currentNumber + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '01';
        }

        $queueNumber = $prefix . $nextNumber;

        // Insert queue
        DB::table('queues')->insert([
            'priority_level_id' => $priorityLevelId,
            'client_id' => 1,
            'service_id' => 1,
            'counter_id' => $counterId,
            'queue_number' => $queueNumber,
            'status' => 'waiting',
            'is_called' => 0,
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Fire event
        $client = QueuesModel::where('counter_id', $counterId)
            ->select('queues.id', 'queues.queue_number', 'queues.counter_id', 'counter_name', 'queues.status', 'queues.queued_at', 'queues.called_at')
            ->leftJoin('service_counters as sc', 'queues.counter_id', '=', 'sc.id')
            ->orderBy('updated_at', 'desc')
            ->first();

        event(new ClientUpdated($client));

        return response()->json([
            'message' => 'Queue status updated successfully',
            'queue_number' => $queueNumber,
            'counter_id' => $counterId,
        ]);
    }
    
    public function recallClient(Request $request)
    {
        $queue = QueuesModel::where('id', $request->queue_id)->first();

        if (!$queue) {
            return response()->json(['error' => 'Queue not found'], 404);
        }

        if ($queue->status === 'serving' && $queue->is_called == 1) {
            $queue->called_at = now();
            $queue->save();

            return response()->json(['message' => 'Client re-called successfully.']);
        }

        return response()->json(['error' => 'Client is not in serving state or already handled.'], 400);
    }
}
