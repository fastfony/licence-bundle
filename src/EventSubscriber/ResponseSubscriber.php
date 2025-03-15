<?php

declare(strict_types=1);

namespace Fastfony\LicenceBundle\EventSubscriber;

use Fastfony\LicenceBundle\Security\LicenceChecker;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LicenceChecker $licenceChecker,
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
        // We test only on HTML responses and not on installation page
        if (!$response instanceof Response
            || !str_contains($response->headers->get('Content-Type'), 'text/html')
            || ($route && str_contains($event->getRequest()->attributes->get('_route'), 'installation'))
        ) {
            return;
        }

        // We test only after 20 requests
        $cache = new FilesystemAdapter();
        $checkCount = $cache->getItem('licence.check_count');
        if (!$checkCount->isHit()) {
            $checkCount->set(0);
            $checkCount->expiresAfter(60);
            $cache->save($checkCount);
        }

        $counter = $checkCount->get();
        $checkCount->set(++$counter);
        $cache->save($checkCount);

        // We check the licence key remotely only after 20 requests
        if ($counter < 20 || $this->licenceChecker->isValid($this->parameterBag->get('fastfony_licence.key'))) {
            return;
        }

        $content = $response->getContent();
        $script = "<script>document.addEventListener('DOMContentLoaded', function(){let o=document.createElement('div');o.style.position='fixed';o.style.top='0';o.style.left='0';o.style.width='100%';o.style.height='100%';o.style.backgroundColor='darkred';o.style.color='yellow';o.style.fontSize='3rem';o.style.display='flex';o.style.flexDirection='column';o.style.justifyContent='center';o.style.alignItems='center';o.style.zIndex='99999';o.innerHTML='<h2>Invalid Fastfony licence key.</h2><p class=\"fs-sm\">Edit <a href=\"/admin/parameters\" class=\"underline\">here</a>.</p>';document.body.appendChild(o);});</script>";
        $content = str_replace('</body>', $script . '</body>', $content);

        $response->setContent($content);
    }
}
