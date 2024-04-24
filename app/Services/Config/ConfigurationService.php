<?php

namespace App\Services\Config;

use App\Models\Other\Configuration;

class ConfigurationService
{
    public function getMaxAttempts()
    {
        return Configuration::where('key', 'max_intentos')->value('value');
    }

    public function getLockoutTime()
    {
        return Configuration::where('key', 'tiempo_bloqueo')->value('value');
    }
}