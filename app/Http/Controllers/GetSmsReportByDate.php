<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sms;

class GetSmsReportByDate extends Controller
{
    //

    /**
     * Retrieve SMS records based on a date filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

     public function getSmsByDate(Request $request)
{
    // Validate the request
    $request->validate([
        'filter_date' => 'required|date',
    ], [
        'filter_date.required' => 'Please provide a date to filter the records.',
        'filter_date.date' => 'The provided date is not in a valid format.',
    ]);

    // Get the validated date from the request
    $filterDate = $request->query('filter_date');

    // Query the SMS table for records matching the given date
    $smsRecords = Sms::whereDate('created_at', $filterDate)
        ->select('status', 'phone_number', 'firstname', 'lastname', 'err_code', 'status_code', 'api_message')
        ->get();

    // Return the results as JSON
    return response()->json([
        'success' => true,
        'data' => $smsRecords,
        'message' => $smsRecords->isEmpty() ? 'No records found for the selected date.' : 'Records retrieved successfully.',
    ]);
}

}
