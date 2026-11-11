<?php

namespace App\Enums;

enum FunnelStatus: string
{
    case HvcoLead = 'HVCO_LEAD';
    case TasteProfileComplete = 'TASTE_PROFILE_COMPLETE';
    case CallBooked = 'CALL_BOOKED';
    case CallCompleted = 'CALL_COMPLETED';
    case NoShow = 'NO_SHOW';
}
