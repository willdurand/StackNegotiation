<?php

namespace Negotiation\Stack;

use Negotiation\FormatNegotiator;
use Negotiation\LanguageNegotiator;
use Negotiation\NegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Negotiation implements HttpKernelInterface
{
    private $app;

    private $formatNegotiator;

    private $languageNegotiator;

    public function __construct(HttpKernelInterface $app, NegotiatorInterface $formatNegotiator = null, NegotiatorInterface $languageNegotiator = null)
    {
        $this->app                = $app;
        $this->formatNegotiator   = $formatNegotiator ?: new FormatNegotiator();
        $this->languageNegotiator = $languageNegotiator ?: new LanguageNegotiator();
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // `Accept` header
        if (null !== $accept = $request->headers->get('Accept')) {
            $request->attributes->set('_accept', $this->formatNegotiator->getBest($accept));
        }

        // `Accept-Language` header
        if (null !== $accept = $request->headers->get('Accept-Language')) {
            $request->attributes->set('_accept_language', $this->languageNegotiator->getBest($accept));
        }

        // `Content-Type` header
        $this->decodeBody($request);

        return $this->app->handle($request, $type, $catch);
    }

    private function decodeBody(Request $request)
    {
        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH', 'DELETE'))) {
            $contentType = $request->headers->get('Content-Type');
            $format      = $this->formatNegotiator->getFormat($request->attributes->get('_accept')->getValue());
            $content     = $request->getContent();

            if (!empty($content)) {
                switch ($format) {
                    case 'json':
                        $data = json_decode($content, true);
                        break;

                    default:
                        // not supported
                        return;
                }

                if (is_array($data)) {
                    $request->request->replace($data);

                    return;
                }

                throw new BadRequestHttpException('Invalid ' . $format . ' message received');
            }
        }
    }
}
