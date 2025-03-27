<?php

declare(strict_types=1);

namespace Fastfony\LicenseBundle\EventSubscriber;

use Fastfony\LicenseBundle\Security\LicenseChecker;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LicenseChecker $licenseChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.response' => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $route = $event->getRequest()->attributes->get('_route');
        // We test only on HTML responses and not on installation page and api
        if (!$response instanceof Response
            || !str_contains($response->headers->get('Content-Type'), 'text/html')
            || ($route && str_contains($event->getRequest()->attributes->get('_route'), 'installation'))
            || ($route && str_contains($event->getRequest()->attributes->get('_route'), 'api'))
        ) {
            return;
        }

        // We test only after 10 requests
        $cache = new FilesystemAdapter();
        $checkCount = $cache->getItem('license.check_count');
        if (!$checkCount->isHit()) {
            $checkCount->set(0);
            $checkCount->expiresAfter(60);
            $cache->save($checkCount);
        }

        $counter = $checkCount->get();
        $checkCount->set(++$counter);
        $cache->save($checkCount);

        $licenseKey = $this->parameterBag->get('fastfony_license.key');

        // We check the license key remotely only after 10 requests
        if ($counter > 10 && (empty($licenseKey) || !$this->licenseChecker->isValid($licenseKey))) {
            $content = $response->getContent();
            $script = "<script>document.addEventListener('DOMContentLoaded', function(){document.body.insertAdjacentHTML('afterbegin', '<div class=\"fixed top-0 left-0 w-full bg-red-600 text-white p-4 shadow-lg text-center font-bold z-50\">Invalid Fastfony license key. <a href=\"/admin/parameters\" class=\"underline hover:text-red-200\">Edit here</a></div><div class=\"fixed top-0 left-0 w-full h-full bg-white/10 backdrop-blur-sm z-40\"></div>');});</script>";
            $content = str_replace('</body>', $script . '</body>', $content);

            $response->setContent($content);
        }
    }
}
