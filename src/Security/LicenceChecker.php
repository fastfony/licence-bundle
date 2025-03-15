<?php

declare(strict_types=1);

namespace Fastfony\LicenceBundle\Security;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class LicenceChecker
{
    public function isValid(string $licenceKey): bool
    {
        $cache = new FilesystemAdapter();
        return $cache->get('validity-'.$licenceKey, function (ItemInterface $item): bool {
            $item->expiresAfter(3600);

            // TODO : do HTTP request to check the licence key

            return true;
        });
    }

    public function generate(string $email): string
    {
        // TODO : generate a licence key
        return 'test-licence-key';
    }
}
