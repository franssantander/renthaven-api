<?php

namespace App\Enum;

enum NotificationReadStatus: string
{
    case READ = 'read';
    case UNREAD = 'unread';
}
