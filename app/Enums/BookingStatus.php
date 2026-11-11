<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingWebhook = 'pending_webhook';
    case Booked = 'booked';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Rejected = 'rejected';
}
