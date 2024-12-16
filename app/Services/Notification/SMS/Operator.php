<?php

namespace App\Services\Notification\SMS;

use Illuminate\Support\Collection;
use Kavenegar;

class Operator
{
    protected string $phoneNumber;
    protected string $apiKey;

    public function __construct()
    {
        $this->phoneNumber = "90006727";
        $this->apiKey = '744252654D79434F316865727978777131542F4A2B465A4D4F74766849754576544E65665574712B3479773D';
    }

    public function send(string|array|Collection $receptor, string $message)
    {
        try {
            $service = new Kavenegar\KavenegarApi($this->apiKey);
            $result = false;

            if ($receptor instanceof Collection) {
                $receptor = $receptor->filter(function ($user) {
                    if ($user->role == 'accountant') {
                        return isset($user->info['position']) && $user->info['position'] != 'jnr' && $user->status == 'active';
                    }
                    return $user->status == 'active';
                })->pluck('phone')->all();
            }


            if ((is_array($receptor) && !empty($receptor)) || (is_string($receptor) && !empty(trim($receptor)))) {
                $result = $service->Send($this->phoneNumber, $receptor, $message);
            }

            if ($result) return true;

        } catch (\Kavenegar\Exceptions\ApiException $e) {
            // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
            echo $e->errorMessage();
        } catch (\Kavenegar\Exceptions\HttpException $e) {
            // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
            echo $e->errorMessage();
        }
    }
}
