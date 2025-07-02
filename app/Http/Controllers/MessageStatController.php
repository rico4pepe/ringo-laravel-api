<?php

namespace App\Http\Controllers;
use App\Utils\ErrorDescriptions;
use App\Models\MessageStat;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MessageStatController extends Controller
{
    //
public function getMessageStats(Request $request)
{
    $today = now()->startOfDay();

    $stats = MessageStat::whereDate('created_at', $today)
        ->latest()
        ->get();

    $summary = [
        'total_messages' => $stats->sum('total_messages'),
        'delivered' => $stats->sum('delivered'),
        'undelivered' => $stats->sum('undelivered'),
        'pending' => $stats->sum('pending'),
    ];

    $transformed = $stats->map(function ($item) {
        $decodedErrors = json_decode($item->errors, true) ?? [];

        $descriptiveErrors = [];
        foreach ($decodedErrors as $code => $count) {
            $description = ErrorDescriptions::lookup($code);
            $descriptiveErrors[] = [
                'code' => $code,
                'description' => $description,
                'count' => $count,
            ];
        }

        return [
            'network' => $item->network,
            'batch_id' => $item->batch_id,
            'total_messages' => $item->total_messages,
            'delivered' => $item->delivered,
            'undelivered' => $item->undelivered,
            'pending' => $item->pending,
            'errors' => $descriptiveErrors,
            'start_id' => $item->start_id,
            'end_id' => $item->end_id,
            'created_at' => $item->created_at,
        ];
    });

    return response()->json([
        'data' => $transformed,
        'summary' => $summary,
    ]);
}



 public function exportCsv()
    {
        $filename = 'message_stats_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, [
                'Network', 'Batch ID', 'Total Messages', 'Delivered', 'Undelivered',
                'Pending', 'Start ID', 'End ID', 'Created At', 'Top Error Codes (JSON)'
            ]);

            MessageStat::chunk(100, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->network,
                        $row->batch_id,
                        $row->total_messages,
                        $row->delivered,
                        $row->undelivered,
                        $row->pending,
                        $row->start_id,
                        $row->end_id,
                        $row->created_at,
                        $row->errors,
                    ]);
                }
            });

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}



