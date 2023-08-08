<?php
namespace App\Console\Commands;

use App\UserNotificaitonSetup;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoneB1B2Finder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:b1b2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $meta             = ['b1b2' => 0];
        $notificaitonList = UserNotificaitonSetup::get();

        $version = config('app.w_version');
        $phoneNumberID = config('app.w_phone_number_id');
        $token = config('app.w_token');


        $headers  = [
            'Authorization' => 'Bearer ' . $token,
        ];

        foreach ($notificaitonList as $key => $notification) {
            $link = 'https://travel.state.gov/content/travel/resources/database/database.getVisaWaitTimes.html?cid=' . $notification->zoneData->zone_code . '&aid=VisaWaitTimesHomePage';
            $ch   = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $data         =  str_replace(' ', '', trim($response));

            $responseList = explode('Days|', $data);
            foreach ($responseList as $itemKey => $value) {
                $responseList[$itemKey] = str_replace('Days', '', $value);
            }


            $b1b2After = $responseList[$meta['b1b2']];
            $dateTime = new DateTime(date('Y-m-d'));
            $dateTime->modify("+$b1b2After days");
            $earliestDate = $dateTime->format('M d Y');




            $params = [
                'messaging_product' => "whatsapp",
                'to' => $notification->mobile,
                'type' => 'template',
                'template' => [
                    'name' => $notification->zoneData->template_id,
                    'language' => [
                        'code' => 'en_US'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $b1b2After,

                                ],
                                [
                                    'type' => 'text',
                                    'text' => $earliestDate,

                                ]
                            ]
                        ]
                    ]
                ]

            ];
            $requestUrl = "https://graph.facebook.com/$version/$phoneNumberID/messages";
            $response = Http::withHeaders($headers)->post($requestUrl, $params);
            Log::channel('stderr')->error("Notified User $notification->mobile for date of  $earliestDate ");

        }
    }
}
