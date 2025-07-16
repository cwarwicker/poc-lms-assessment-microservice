<?php

namespace App\Jobs;

use App\Http\Controllers\LTIController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendData implements ShouldQueue
{
    use Queueable;

    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Sending answer data:", $this->data);
        $gradingResponse = Http::post(env('LTI_GRADING_SERVICE'), $this->data);
        if ($gradingResponse->ok()) {
            $body = json_decode($gradingResponse->body());
            Log::info("Sending grade to lms:", [$body->response->score]);
            [$lmsResponseCode, $lmsResponse] = LTIController::sendGrade($body->response->service_url, $body->response->sourcedid, $body->response->score);
            if ($lmsResponseCode == 200) {
                Log::info('Finished');
            } else {
                Log::error("Error sending data to lms:", [$lmsResponse]);
            }
        } else {
            Log::error("Error sending data to grading service");
        }
    }
}
