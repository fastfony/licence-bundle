<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\Security;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
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
        $validityInCache = $cache->getItem('validity-'.$licenseKey);
        $expireTime = 60;
        $validity = true;
        if (!$validityInCache->isHit() || $validityInCache->get() === false) {
            $client = HttpClient::create();

            try {
                $response = $client->request(
                    'GET',
                    'https://fastfony.com/api/licenses/'.$licenseKey,
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Referer' => $_SERVER['HTTP_HOST'],
                        ],
                    ]
                );

                if (404 === $response->getStatusCode()) {
                    return false;
                }

                $license = $response->toArray();
                if (isset($license['validUntil']) && $license['validUntil']) {
                    $expireTime = strtotime($license['validUntil']) - time();
                } elseif ($license['active']) {
                    $expireTime = 3600*24;
                }

                $validity = $license['active'];
            } catch (TransportException) {
                // True by default for benefit of the doubt
            }

            $validityInCache->expiresAfter($expireTime);
            $validityInCache->set($validity);
            $cache->save($validityInCache);
        }

        return $validityInCache->get();
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
