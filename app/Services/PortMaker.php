<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PortMaker
{
    protected static array $iranianPorts = [
        "Bandar Abbas",
        "Khorramshahr",
        "Abadan",
        "Chabahar",
        "Imam Khomeini",
        "Assaluyeh",
        "Shahid Rajaee",
        "Bahonar",
        "Lengeh"
    ];

    protected static array $chinesePorts = [
        "Shanghai",
        "Shenzhen",
        "Ningbo-Zhoushan",
        "Guangzhou",
        "Qingdao",
        "Tianjin",
        "Dalian",
        "Xiamen",
        "Hong Kong",
        "Suzhou",
        "Lianyungang",
        "Rizhao",
        "Nantong",
        "Zhoushan",
        "Quanzhou",
        "Tangshan",
        "Fuzhou",
        "Yantai",
        "Jiangyin",
        "Weihai"
    ];

    public static function getIranianPorts(): array
    {
        return Cache::remember('iranian_ports', 600, function () {
            $sortedPorts = self::$iranianPorts;
            sort($sortedPorts);
            return $sortedPorts;
        });
    }

    public static function getChinesePorts(): array
    {
        return Cache::remember('chinese_ports', 600, function () {
            $sortedPorts = self::$chinesePorts;
            sort($sortedPorts);
            return $sortedPorts;
        });
    }
}
