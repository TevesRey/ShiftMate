<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;

/** Legacy name retained for response classes; SHIFTMATE has no team routing. */
trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        return $redirect ?: '/';
    }
}
