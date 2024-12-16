<?php

namespace App\Services;

class DepartmentDetails
{
    protected static array $departments = [
        'AS' => [
            'name' => 'Administration & Support',
            'code' => 'AS',
            'description' => 'اداری و پشتیبانی',
        ],
        'AC' => [
            'name' => 'Accounting',
            'code' => 'AC',
            'description' => 'مالی',
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
        'MK' => [
            'name' => 'Marketing',
            'code' => 'MK',
            'description' => 'واحد بازاریابی',
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
        'SP' => [
            'name' => 'BAZORG (Sales Platform)',
            'code' => 'SP',
            'description' => 'پلتفرم فروش',
        ],
        'SA' => [
            'name' => 'Sales',
            'code' => 'SA',
            'description' => 'واحد(های) فروش',
        ],
        'WP' => [
            'name' => 'Wood Products',
            'code' => 'WP',
            'description' => 'فروش فراورده های چوب',
        ],
        'PERSOL' => [
            'name' => 'PERSOL',
            'code' => 'PERSOL',
            'description' => 'شرکت پرسال',
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
