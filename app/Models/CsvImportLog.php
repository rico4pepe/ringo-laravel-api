<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_request_data',
        'api_response_data',
        'status',
    ];


     // Define possible status options
     const STATUS_PENDING = 0;
     const STATUS_SUCCESS = 1;
     const STATUS_FAILED = 2;
     const STATUS_PROCESSING = 3;


       // Mutator to store request data as JSON
    public function setApiRequestDataAttribute($value)
    {
        $this->attributes['api_request_data'] = json_encode($value);
    }

    // Mutator to store response data as JSON
    public function setApiResponseDataAttribute($value)
    {
        $this->attributes['api_response_data'] = json_encode($value);
    }

      // Accessor to decode JSON response
      public function getApiRequestDataAttribute($value)
      {
          return json_decode($value, true);
      }

        // Accessor to decode JSON request
    public function getApiResponseDataAttribute($value)
    {
        return json_decode($value, true);
    }

}
