<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\Security;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class LicenseChecker
{
    public function isValid(string $licenseKey): bool
    {
        $cache = new FilesystemAdapter();
        return $cache->get('validity-'.$licenseKey, function (ItemInterface $item): bool {
            $item->expiresAfter(3600);

            // TODO : do HTTP request to check the license key

            return true;
        });
    }

    public function generate(string $email): string
    {
        // TODO : generate a license key
        return 'test-license-key';
    }
}
