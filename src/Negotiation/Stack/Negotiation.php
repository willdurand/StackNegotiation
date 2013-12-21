<?php

namespace Negotiation\Stack;

use Negotiation\FormatNegotiator;
use Negotiation\LanguageNegotiator;
use Negotiation\NegotiatorInterface;
use Negotiation\Decoder\DecoderProvider;
use Negotiation\Decoder\DecoderProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class Negotiation implements HttpKernelInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $app;

    /**
     * @var NegotiatorInterface
     */
    private $formatNegotiator;

    /**
     * @var NegotiatorInterface
     */
    private $languageNegotiator;

    /**
     * @var DecoderProviderInterface
     */
    private $decoderProvider;

    public function __construct(
        HttpKernelInterface $app,
        NegotiatorInterface $formatNegotiator = null,
        NegotiatorInterface $languageNegotiator = null,
        DecoderProviderInterface $decoderProvider = null
    ) {
        $this->app                = $app;
        $this->formatNegotiator   = $formatNegotiator ?: new FormatNegotiator();
        $this->languageNegotiator = $languageNegotiator ?: new LanguageNegotiator();
        $this->decoderProvider    = $decoderProvider ?: new DecoderProvider([
            'json' => new JsonEncoder(),
            'xml'  => new XmlEncoder(),
        ]);
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
        if (in_array($request->getMethod(), [ 'POST', 'PUT', 'PATCH', 'DELETE' ])) {
            $contentType = $request->headers->get('Content-Type');
            $format      = $this->formatNegotiator->getFormat($contentType);

            if (!$this->decoderProvider->supports($format)) {
                return;
            }

            $decoder = $this->decoderProvider->getDecoder($format);
            $content = $request->getContent();

            if (!empty($content)) {
                try {
                    $data = $decoder->decode($content, $format);
                } catch (\Exception $e) {
                    $data = null;
                }

                if (is_array($data)) {
                    $request->request->replace($data);
                } else {
                    throw new BadRequestHttpException('Invalid ' . $format . ' message received');
                }
            }
        }
    }
}
