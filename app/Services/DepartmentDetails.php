<?php

namespace App\Services;

class DepartmentDetails
{
    protected static array $departments = [
        'AC' => [
            'name' => 'Accounting',
            'code' => 'AC',
            'description' => 'مالی',
        ],
        'AD' => [
            'name' => 'ADMONT',
            'code' => 'AD',
            'description' => 'شرکت ادمونت',
        ],
        'AS' => [
            'name' => 'Administration & Support',
            'code' => 'AS',
            'description' => 'اداری و پشتیبانی',
        ],
        'BD' => [
            'name' => 'Business Development',
            'code' => 'BD',
            'description' => 'توسعه کسب و کار',
        ],
        'CH' => [
            'name' => 'Chemical and Polymer Products',
            'code' => 'CH',
            'description' => 'فروش فراورده های شیمیایی و پلیمری',
        ],
        'CM' => [
            'name' => 'Commercial Import Operation',
            'code' => 'CM',
            'description' => 'بازرگانی (واردات و خرید داخلی)',
        ],
        'CS (A)' => [
            'name' => 'Cellulosic Sales (Alireza)',
            'code' => 'CS (A)',
            'description' => ' (علیرضا) فروش محصولات سلولزی',
        ],
        'CS (M)' => [
            'name' => 'Cellulosic Sales (Mahsa)',
            'code' => 'CS (M)',
            'description' => ' (محسا) فروش محصولات سلولزی',
        ],
        'CX' => [
            'name' => 'Commercial Export Operation',
            'code' => 'CX',
            'description' => 'بازرگانی (صادرات)',
        ],
        'HR' => [
            'name' => 'Human Resources',
            'code' => 'HR',
            'description' => 'منابع انسانی',
        ],
        'MA' => [
            'name' => 'Management',
            'code' => 'MA',
            'description' => 'مدیریت',
        ],
        'MA (PA)' => [
            'name' => 'Parva',
            'code' => 'MA (PA)',
            'description' => 'پروا سلطانی',
        ],
        'MA (PE)' => [
            'name' => 'Pedram',
            'code' => 'MA (PE)',
            'description' => 'پدرام سلطانی',
        ],
        'MK' => [
            'name' => 'Marketing',
            'code' => 'MK',
            'description' => 'واحد بازاریابی',
        ],
        'PERSOL' => [
            'name' => 'PERSOL',
            'code' => 'PERSOL',
            'description' => 'شرکت پرسال',
        ],
        'PERSORE' => [
            'name' => 'PERSORE',
            'code' => 'PERSORE',
            'description' => 'شرکت پرسور',
        ],
        'PO' => [
            'name' => 'Polymer Products',
            'code' => 'PO',
            'description' => 'فروش محصولات پلیمری',
        ],
        'PS' => [
            'name' => 'Planning & System',
            'code' => 'PS',
            'description' => 'برنامه‌ریزی و بهبود سیستم‌ها',
        ],
        'SA' => [
            'name' => 'Sales',
            'code' => 'SA',
            'description' => 'واحد(های) فروش',
        ],
        'SO' => [
            'name' => 'Solar Panel',
            'code' => 'SO',
            'description' => 'پنل خورشیدی',
        ],
        'SOLSUN' => [
            'name' => 'SOLSUN',
            'code' => 'SOLSUN',
            'description' => 'کارگذاری',
        ],
        'SP' => [
            'name' => 'BAZORG (Sales Platform)',
            'code' => 'SP',
            'description' => 'پلتفرم فروش',
        ],
        'WP' => [
            'name' => 'Wood Products',
            'code' => 'WP',
            'description' => 'فروش فراورده های چوب',
        ],
    ];


    public static function getName($department)
    {
        return static::$departments[$department]['name'] ?? '';
    }

    public static function getCode($department)
    {
        return static::$departments[$department]['code'];
    }

    public static function getDescription($department)
    {
        return static::$departments[$department]['description'];
    }

    public static function getAllDepartments(): array
    {
        return array_keys(self::$departments);
    }

    public static function getAllDepartmentNames(): array
    {
        $departmentNames = [];
        foreach (self::$departments as $code => $department) {
            $departmentNames[$code] = $department['name'];
        }
        return $departmentNames;
    }
}
