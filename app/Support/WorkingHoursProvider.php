<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class WorkingHoursProvider
{
    /**
     * @return array{0:string,1:string}|null
     */
    public function workingHours(CarbonImmutable $date): ?array
    {
        if ($date->isSunday()) {
            return null;
        }

        if ($date->isSaturday()) {
            return ['10:00', '14:20'];
        }

        return ['09:00', '17:00'];
    }

}
