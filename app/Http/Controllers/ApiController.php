<?php

namespace App\Http\Controllers;

use App\Facades\Formatter;
use App\Http\Controllers\Controller;
use App\Jobs\SendNotifyInApp;
use App\Mail\DemoMail;
use App\Models\Salesforce\Account;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Illuminate\Http\Client\Pool;
use Twilio\Rest\Client;
// use GuzzleHttp\Client;
// use GuzzleHttp\Exception\RequestException;

class ApiController extends Controller
{

    public function sendResponse($data = [], $success = true, $message = '', $code = 200, $total_record = 0){
        return response([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'total_record' => $total_record
        ], $code);
    }

    public function sendOTPResponse($phone, $sms, $bid, $OTP_FROM = null, $OTP_U = null, $OTP_PWD = null){
        $endpoint = env('OTP_URL');

        $data = [
            'from' => $OTP_FROM ?? env('OTP_FROM'),
            'u' => $OTP_U ?? env('OTP_U'),
            'pwd' => $OTP_PWD ?? env('OTP_PWD'),
            'phone' => $phone,
            'sms' => $sms,
            'bid' => $bid,
            'type' => env('OTP_TYPE'),
            'json' => 1
        ];

        $response = Http::post($endpoint, $data);

        return $response->json();
    }

    public function sendMailResponse($email, $subject, $content){

        try{
            $mailData = [
                'subject' => $subject,
                'body' => $content,
            ];

            Mail::to($email)->send(new DemoMail($mailData));

            return [
                'success' => true,
                'message' => 'Send mail success'
            ];

        }catch(Exception $e){
            Log::error('@sendMailResponse - '.$e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function uploadFileCloudflare(Request $request){
        $attachments = $request->attachments ?? [];
        $folder_type = $request->folder_type ?? null;
        $folder = $request->folder ?? null;

        $folder_cloudflare = config('folder_cloudflare.folder');

        $list_file = [];
        if($folder_type && array_key_exists($folder_type, $folder_cloudflare) && count($attachments) > 0){
            $directory = $folder_cloudflare[$folder_type] .'/'. $folder;

            foreach($attachments as $key => $attachment){
                $file = $request->hasFile('attachments.'.$key);
                if($file){
                    $list_file[] = [
                        'download_link' => Formatter::save_file_cloudflare($attachment, $directory),
                        'original_name' => $attachment->getClientOriginalName(),
                    ];
                }
            }
        }

        return response([
            'success' => true,
            'message' => '',
            'data' => $list_file,
            'total_record' => 0
        ], 200);
    }

    public function getFolderFileCloudflare(Request $request){
        $folder_type = $request->folder_type ?? null;
        $object_sfid = $request->object_sfid ? ($request->object_sfid.'/') : null;
        $sub_path = $request->sub_path ? ($request->sub_path.'/') : null;

        $folder_cloudflare = config('folder_cloudflare.folder');

        $slideshow = [];
        $listContents = [];
        if($folder_type && array_key_exists($folder_type, $folder_cloudflare))
        {
            $directory = $folder_cloudflare[$folder_type] .'/'. $object_sfid . $sub_path;
            $is_public = json_decode(Http::get(Storage::disk('r2')->url($directory))->body())->public??true;
            if(!$is_public){
                return response([
                    'success' => true,
                    'message' => '',
                    'data' => [
                        "endpoint" =>  env('CLOUDFLARE_R2_URL'),
                        'listContents' => [],
                        'slideshow' => [],
                    ],
                    'total_record' => 0
                ], 200);
            }

            $directories = Storage::disk('r2')
                    ->listContents($directory, false)
                    ->toArray();

            $responses = Http::pool(function (Pool $pool) use ($directories) {
                $responseHandlers = [];
                foreach($directories as $item){
                    if($item['type'] == 'dir'){
                        $responseHandlers[] = $pool->as($item['path'])->get(Storage::disk('r2')->url($item['path'].'/'))->then(function ($response) {});
                    }

                }
                return $responseHandlers;
            });

            foreach($directories as $item){
                if(!($item['type'] == 'dir' && (json_decode($responses[$item['path']]->body())->public??true) == false)){
                    if($item['path'] == $directory.'config.json'){
                        $response = Storage::disk('r2')->read($item['path']);

                        $slideshow = json_decode($response, true)['order'];
                    }else{
                        $listContents[] = $item;
                    }
                }

            }
        }

        return response([
            'success' => true,
            'message' => '',
            'data' => [
                "endpoint" =>  env('CLOUDFLARE_R2_URL'),
                'listContents' => $listContents,
                'slideshow' => $slideshow,
            ],
            'total_record' => 0
        ], 200);
    }

    public function getFieldPicklist(Request $request){
        $object = $request->object ?? null;

        $list_picklist = [];
        if($object){
            $list_picklist = config('field_picklist.'. $object);
        }else{
            $list_picklist = config('field_picklist');
        }

        return response([
            'success' => true,
            'message' => '',
            'data' => $list_picklist,
            'total_record' => 0
        ], 200);
    }

    public function sendInApp(Request $request){
        $contact_sfid = $request->contact_sfid;
        $action = $request->action ?? null;

        $data = [
            'topic' => 'subscribeToUser'. $contact_sfid,
            'action' => $action,
            'object' => 'Contact',
            'sfid' => $contact_sfid,
        ];

        dispatch(new SendNotifyInApp($data));

        return $this->sendResponse();
    }

    public function getVersionApp(Request $request){
        $version = $request->version;
        $device = $request->device;

        $isUpdate = false;

        $config_app = \DB::table('config_app')->where('device', $device)->first(['version']);
        if($config_app->version > $version){
            $isUpdate = true;
        }

        $data = [
            'isUpdate' => $isUpdate,
        ];

        return $this->sendResponse($data);
    }

    public function updateVersionApp(Request $request)
    {
        $version = $request->version;
        $device = $request->device;

        $config_app = \DB::table('config_app')
            ->where('device', $device)
            ->update([
                'version' => $version
            ]);

        return $this->sendResponse($config_app);
    }
}
