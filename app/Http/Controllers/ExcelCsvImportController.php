<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Import\ExcelCsvImport;
use App\Models\ScheduledCampaign;
class ExcelCsvImportController extends Controller
{
    //

    protected $importService;

    public function __construct(ExcelCsvImport $importService){
        $this->importService = $importService;
    }

    public function import(Request $request){

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
             'ordinaryMessage' => 'required_if:isCustomMessage,false|string',
             'campaign_title' => 'required_if:isScheduled,true|string',
             'schedule_date' => 'required_if:isScheduled,true|date',
             'schedule_time' => 'required_if:isScheduled,true|date_format:H:i'
        ]);


        $isCustomMessage = $request->input('isCustomMessage') === 'true';
        $ordinaryMessage = $request->input('ordinaryMessage');

          // Store file temporarily
          $filePath = $request->file('file')->store('temp');

          if ($request->has('schedule_date') && $request->has('schedule_time')) {
            // Save scheduled campaign details if schedule is set
            ScheduledCampaign::create([
                'campaign_title' => $request->input('campaign_title'),
                'file_path' => $filePath,
                'schedule_date' => $request->input('schedule_date'),
                'schedule_time' => $request->input('schedule_time'),
                'is_custom_message' => $isCustomMessage,
            ]);

            return response()->json([
                'message' => 'Campaign scheduled successfully.'
            ]);
        }else {
            // If no schedule, process and send immediately
            $summary = $this->importService->importFile(storage_path("app/{$filePath}"), $isCustomMessage);

            // Delete the temporary file
            Storage::delete($filePath);

            return response()->json([
                'message' => 'File import completed.',
                'summary' => $summary
            ]);
        }
    }

}
