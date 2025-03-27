<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\Security;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LicenseChecker
{
    public function isValid(string $licenseKey): bool
    {
        $cache = new FilesystemAdapter();
        return $cache->get('validity-'.$licenseKey, function (ItemInterface $item) use ($licenseKey): bool {
            $item->expiresAfter(60);
            $client = HttpClient::create();

            try {
                $response = $client->request(
                    'GET',
                    'https://fastfony.com/api/licenses/'.$licenseKey,
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                        ],
                    ]
                );

                if (404 === $response->getStatusCode()) {
                    return false;
                }

                $license = $response->toArray();
                if (isset($license['validUntil']) && $license['validUntil']) {
                    $item->expiresAt(new \DateTime($license['validUntil']));
                } elseif ($license['active']) {
                    $item->expiresAfter(3600*24);
                }

                return $license['active'] ?? true; // True by default for benefit of the doubt
            } catch (TransportException) {
                return true;
            }
        });
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function generate(
        string $email,
        string $productSlug,
    ): ?string {
        $client = HttpClient::create();
        $response = $client->request(
            'POST',
            'https://fastfony.com/api/license_requests/',
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'email' => $email,
                    'product' => ['slug' => $productSlug],
                ],
            ],
        );

        if (201 !== $response->getStatusCode()) {
            return null;
        }

        return $response->toArray()['license']['uuid'];
    }
}
