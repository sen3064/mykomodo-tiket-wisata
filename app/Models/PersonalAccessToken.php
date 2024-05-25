<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPAT;

class PersonalAccessToken extends SanctumPAT
{
    protected $connection = 'kabtour_db';
}