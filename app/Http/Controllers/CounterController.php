<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use DB;

class CounterController extends Controller
{
    public function current_clients(Request $request)
    {
        $counterIds = [1, 2, 3, 4];
        $allData = collect(); // Initialize an empty collection

        foreach ($counterIds as $id) {
            $results = DB::table('vw_counter_queue')
                ->where('service_counter_id', $id)
                ->where('status', 'serving')

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

            $allData = $allData->merge($results); // Merge each result into the collection
        }

        return response()->json([
            'data' => $allData,
        ]);
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
        $queueId = $request->input('queue_id');
        $status = $request->input('status');

        // Update the queue status
        DB::table('queues')
            ->where('id', $queueId)
            ->update(['status' => $status, 'is_called' => 0]);

        return response()->json(['message' => 'Queue status updated successfully']);
    }
    public function transaction(Request $request)
    {
        $counterId = $request->input('counter_id');

        $priorityLevelId = 4; // default
        if ($counterId == 2) {
            $priorityLevelId = 1;
        }

        // Prefix based on counter ID
        $prefix = match ($counterId) {
            1 => 'L',
            2 => 'P',
            3 => 'O',
            default => 'Q',
        };

        // Get latest queue number for this counter and prefix
        $latestQueue = DB::table('queues')
            ->where('counter_id', $counterId)
            ->where('queue_number', 'like', "$prefix%")
            ->orderByDesc('id')
            ->first();

        // Extract and increment number
        if ($latestQueue && preg_match('/\d+/', $latestQueue->queue_number, $matches)) {
            $nextNumber = str_pad(((int) $matches[0]) + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        $queueNumber = $prefix . $nextNumber;

        // Insert into queues table
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

        return response()->json([
            'message' => 'Queue status updated successfully',
            'queue_number' => $queueNumber
        ]);
    }
}
