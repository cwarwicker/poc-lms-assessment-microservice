<?php

namespace App\Http\Controllers;

use App\Jobs\SendData;
use App\Models\Quiz;
use Illuminate\Http\Request;

/**
 * LTI Controller with basic LTI 1.1 support for PoC.
 */
class LTIController extends Controller
{

    public function index(Request $request) {

        $launch_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (!isset($request->oauth_consumer_key) || $request->oauth_consumer_key !== env('LTI_CONSUMER_KEY')) {
            http_response_code(401);
            echo "Invalid consumer key";
            exit;
        }

        if (!static::validate_oauth($request->all(), env('LTI_SHARED_SECRET'), $launch_url)) {
            http_response_code(403);
            echo "Invalid OAuth signature";
            exit;
        }

        // We are going to use the hard-coded example quiz.
        $quiz = Quiz::find(1);

        $service_url = $request->lis_outcome_service_url;
        $sourcedid = $request->lis_result_sourcedid;
        $service_url_replace = env('LTI_SERVICE_URL_REPLACE');
        if ($service_url_replace) {
            $parts = parse_url($service_url);
            $service_url = str_replace($parts['host'], $service_url_replace, $service_url);
        }

        return view('quiz', [
            'user_id' => $request->user_id,
            'quiz' => $quiz,
            'service_url' => $service_url,
            'sourcedid' => $sourcedid,
        ]);

    }

    public function submit(Request $request) {

        // Validation for PoC just make sure all questions answered.
        $validate = [];
        $quiz = Quiz::find($request->quiz_id);
        foreach ($quiz->questions as $question) {
            $validate['question.' . $question->id] = ['required'];
        }

        $request->validate($validate);

        // Queue the process to send the data to the grading service.
        SendData::dispatch([
            'quiz_id' => $quiz->id,
            'questions' => $quiz->questions,
            'service_url' => $request->service_url,
            'sourcedid' => $request->sourcedid,
            'answers' => $request->all(),
        ]);

        die('Answers submitted for grading');

    }

    public static function validate_oauth($params, $secret, $launch_url) {
        $base_string = static::build_base_string($launch_url, 'POST', $params);
        $signing_key = rawurlencode($secret) . '&';
        $calculated_signature = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
        return $calculated_signature === $params['oauth_signature'];
    }

    public static function build_base_string($url, $method, $params) {
        $r = [];
        ksort($params);
        foreach ($params as $key => $value) {
            if ($key != 'oauth_signature') {
                $r[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return $method . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $r));
    }

    public static function sendGrade(string $service_url, string $sourcedid, float $grade) {


        // Build the XML
        $xml = '<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>' . uniqid() . '</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <replaceResultRequest>
      <resultRecord>
        <sourcedGUID>
          <sourcedId>' . htmlspecialchars($sourcedid) . '</sourcedId>
        </sourcedGUID>
        <result>
          <resultScore>
            <language>en</language>
            <textString>' . $grade . '</textString>
          </resultScore>
        </result>
      </resultRecord>
    </replaceResultRequest>
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>';

        // Prepare the request
        $params = [
            'oauth_consumer_key' => env('LTI_CONSUMER_KEY'),
            'oauth_nonce' => uniqid(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
            'oauth_body_hash' => base64_encode(sha1($xml, true)),
        ];

        // Sign the request
        $base_string = static::build_base_string($service_url, 'POST', $params);
        $signature = base64_encode(hash_hmac('sha1', $base_string, rawurlencode(env('LTI_SHARED_SECRET')) . '&', true));
        $params['oauth_signature'] = $signature;

        // Create Authorization header
        $auth_header = 'OAuth ' . http_build_query($params, '', ', ');

        // Send the request
        $ch = curl_init($service_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $auth_header,
            'Content-Type: application/xml',
            'Content-Length: ' . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$http_code, $response];

    }

}
