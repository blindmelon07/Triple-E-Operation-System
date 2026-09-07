<?php

namespace App\Support;

/**
 * Shared helper for embedding the company logo in a PDF. DomPDF's default
 * chroot/remote settings can refuse an asset() URL or a plain public_path(),
 * so a data URI sidesteps both — it always renders.
 */
class CompanyLogo
{
    public static function dataUri(): ?string
    {
        $path = public_path('images/logo.png');

        if (! file_exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
