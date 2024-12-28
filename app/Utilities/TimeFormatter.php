<?php

namespace App\Utilities;

class TimeFormatter
{
    public static function formatMinutesToReadable($minutes)
    {
        if ($minutes <= 0) {
            return "0m"; 
        }

        $hours = intdiv($minutes, 60); 
        $remainingMinutes = $minutes % 60; 

        // Construir el formato evitando ceros redundantes
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}m"; 
        } elseif ($hours > 0) {
            return "{$hours}h"; 
        } else {
            return "{$remainingMinutes}m"; 
        }
    }
}
