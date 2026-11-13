<?php

namespace App\Http\Responses\Concerns;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $team = $this->currentTeam($request);

        // لسه بنثبّت الفريق الحالي عشان مسارات {current_team} تتولّد صح،
        // لكن شاشات النظام نفسها مش متداخلة تحت الفريق — بنرجّع المسار زي ما هو.
        URL::defaults(['current_team' => $team->slug]);

        return $redirect;
    }

    protected function currentTeam(Request $request): Team
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $team = $user->currentTeam ?? $user->personalTeam();

        abort_if(! $team, 403);

        return $team;
    }
}
