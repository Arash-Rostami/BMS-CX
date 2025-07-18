<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PortMaker
{
    protected const IRANIAN_PORTS = [
        "Abadan",
        "Assaluyeh",
        "Bandar Abbas",
        "Bahonar",
        "Chabahar",
        "Imam Khomeini",
        "Isfahan",
        "Khorramshahr",
        "Lengeh",
        "Shahid Rajaee",
        "Tehran"
    ];

    protected const CHINESE_PORTS = [
        "Dalian",
        "Fuzhou",
        "Guangzhou",
        "Hong Kong",
        "Jiangyin",
        "Lianyungang",
        "Nantong",
        "Ningbo-Zhoushan",
        "Qingdao",
        "Quanzhou",
        "Rizhao",
        "Shanghai",
        "Shenzhen",
        "Suzhou",
        "Taicang",
        "Tangshan",
        "Tianjin",
        "Weihai",
        "Xiamen",
        "Yantai",
        "Zhoushan"
    ];

    public static function getIranianPorts(): array
    {
        return self::getCachedPorts('iranian_ports', self::IRANIAN_PORTS);
    }

    public static function getChinesePorts(): array
    {
        return self::getCachedPorts('chinese_ports', self::CHINESE_PORTS);
    }

    protected static function getCachedPorts(string $cacheKey, array $ports): array
    {
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($ports) {
            $sorted = $ports;
            sort($sorted);
            return $sorted;
        });
    }
}
