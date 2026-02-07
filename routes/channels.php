<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('location.{locationId}', function ($user, $locationId) {
    // simplest version: allow any authenticated user
    return true;

    // OR real logic (recommended later)
    // return $user->location_id == $locationId;
});
