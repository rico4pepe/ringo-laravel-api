<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Import\ExcelCsvImport;
class ExcelCsvImportController extends Controller
{
    //

    protected $importService;

    public function __construct(ExcelCsvImport $importService){
        $this->importService = $importService;
    }

    public function import(Request $request){

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx'
        ]);


        $isCustomMessage = $request->input('isCustomMessage') === 'true';

          // Store file temporarily
          $filePath = $request->file('file')->store('temp');

             // Import file and get summary
          $summary = $this->importService->importFile(storage_path("app/{$filePath}"), $isCustomMessage);


           // Delete the temporary file
           Storage::delete($filePath);

           return response()->json([
            'message' => 'File import completed.',
            'summary' => $summary
        ]);
    }

}
