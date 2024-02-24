<?php

namespace App\Services\traits;

trait NotificationConfig
{

    public static array $type = [
        'new' => 'success',
        'edit' => 'warning',
        'delete' => 'danger',
        'reminder' => 'info',
    ];

    public static array $title = [
        'new' => "<span style='color:rgb(52, 211, 153)'>ADDED</span>",
        'edit' => "<span style='color:rgb(251, 146, 60)'>UPDATED</span>",
        'delete' => "<span style='color:rgb(251, 113, 133)'>REMOVED</span>",
        'reminder' => "<span style='color:rgb(96, 165, 250)'>REMINDER</span>"
    ];

    public static array $icon = [
        'new' => 'heroicon-s-squares-plus',
        'edit' => 'heroicon-c-adjustments-horizontal',
        'delete' => 'heroicon-c-trash',
        'reminder' => 'heroicon-c-calendar-days',
    ];


    public static array $body = [
        'orderRequest' => [
            'new' => 'One order request for %s made.',
            'edit' => 'Order request for %s updated.',
            'delete' => 'Order request for %s deleted.',
            'reminder' => 'Order request for %s requires your attention.'
        ],
        'order' => [
            'new' => 'The order - %s - created.',
            'edit' => 'Order - %s - modified.',
            'delete' => 'Order - %s - removed.',
            'reminder' => 'Order - %s - requires your attention.'
        ],
        'paymentRequest' => [
            'new' => 'One payment request for %s created.',
            'edit' => 'The payment request for %s edited.',
            'delete' => 'The payment request for %s deleted.',
            'reminder' => 'The payment request for %s requires your attention.'
        ],
        'payment' => [
            'new' => 'Payment received for %s.',
            'edit' => 'Payment details for %s changed.',
            'delete' => 'Payment for %s deleted.',
            'reminder' => 'Payment for %s requires your attention.'
        ],
    ];

    public static array $titleStatus = [
        'pending' => "PENDING",
        'processing' => "PROCESSING",
        'approved' => "APPROVED",
        'rejected' => "DECLINED",
        'completed' => "COMPLETED",
        'cancelled' => "CANCELLED",
    ];

    public static array $iconStatus = [
        'pending' => "heroicon-s-clock",
        'processing' => "heroicon-o-arrow-path-rounded-square",
        'approved' => "heroicon-s-check-badge",
        'rejected' => "heroicon-c-x-circle",
        'completed' => "heroicon-s-flag",
        'cancelled' => "heroicon-s-hand-raised",
    ];

    public static array $bodyStatus = [
        'order' => [
            'pending' => 'Order request for %s is pending.',
            'processing' => 'Order request for %s is under review.',
            'approved' => 'Order request for %s has been approved.',
            'rejected' => 'Order request for %s has been rejected.',
            'completed' => 'Order request for %s has been fulfilled.',
        ],
        'payment' => [
            'pending' => 'Payment request for %s is pending.',
            'processing' => 'Payment for %s in progress.',
            'approved' => 'Payment for %s authorized for processing.',
            'rejected' => 'Payment request for %s declined.',
            'completed' => 'Payment for %s successfully completed.',
            'cancelled' => 'Payment for %s cancelled.',
        ],
    ];


    public static $recipients;

    public static $items;

    public static string $url;


    /**
     * @return string
     */
    public static function getType($type): string
    {
        return self::$type[$type];
    }


    /**
     * @return string
     */
    public static function getTitle($title): string
    {
        return self::$title[$title];
    }


    /**
     * @return string
     */
    public static function getBody(string $module, string $type): string
    {
        return self::$body[$module][$type];
    }


    /**
     * @return mixed
     */
    public static function getRecipients()
    {
        return self::$recipients;
    }

    /**
     * @param mixed $recipients
     */
    public static function setRecipients($recipients): void
    {
        self::$recipients = $recipients;
    }


    /**
     * @return string
     */
    public static function getIcon($type): string
    {
        return self::$icon[$type];
    }

    /**
     * @return mixed
     */
    public static function getItems()
    {
        return self::$items;
    }

    /**
     * @param mixed $items
     */
    public static function setItems($items): void
    {
        self::$items = $items;
    }

    /**
     * @return string
     */
    public static function getUrl(): string
    {
        return self::$url;
    }

    /**
     * @param string $url
     */
    public static function setUrl(string $url): void
    {
        self::$url = $url;
    }

    /**
     * @return string
     */
    public static function getTitleStatus($type): string
    {
        return self::$titleStatus[$type];
    }

    /**
     * @return string
     */
    public static function getIconStatus($type): string
    {
        return self::$iconStatus[$type];
    }


    /**
     * @return string
     */
    public static function getBodyStatus(string $module, string $type): string
    {
        return self::$bodyStatus[$module][$type];
    }
}
