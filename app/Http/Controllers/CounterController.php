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
            ->update(['status' => $status,'is_called' => 0]);

        return response()->json(['message' => 'Queue status updated successfully']);
    }
}
