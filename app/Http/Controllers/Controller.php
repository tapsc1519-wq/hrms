<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Return the authenticated user's organization ID.
     * Aborts with 403 if the user has no organization (e.g. super_admin
     * who should use the /super-admin portal instead).
     */
    protected function orgId(): int
    {
        $id = auth()->user()?->organization_id;

        abort_if(
            is_null($id),
            403,
            'This section is for organisation accounts only. Please use the Super Admin portal.'
        );

        return $id;
    }
}
