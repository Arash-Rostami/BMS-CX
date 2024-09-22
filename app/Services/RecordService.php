<?php

namespace App\Services;

class RecordService
{
    protected $type;
    protected $module;
    protected $prefix;
    protected $recordRoute;

    public function __construct($type, $module, $prefix, $recordRoute)
    {
        $this->type = $type;
        $this->module = $module;
        $this->prefix = $prefix;
        $this->recordRoute = $recordRoute;
    }

    public function persistReferenceNumber($record): void
    {
        $yearSuffix = date('y');
        $orderIndex = $record->id;
        $referenceNumber = sprintf('%s-%s%04d', $this->prefix, $yearSuffix, $orderIndex);
        $record->reference_number = $referenceNumber;
        $record->save();
    }

    public function notifyRecipients($record, $recipients): void
    {
        foreach ($recipients as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->getRecordReference($record),
                'type' => 'new',
                'module' => $this->module,
                'url' => route($this->recordRoute, ['record' => $record->id]),
            ]));
        }
    }

}
