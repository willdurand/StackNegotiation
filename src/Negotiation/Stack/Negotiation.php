<?php

namespace Negotiation\Stack;

use Negotiation\FormatNegotiator;
use Negotiation\FormatNegotiatorInterface;
use Negotiation\LanguageNegotiator;
use Negotiation\NegotiatorInterface;
use Negotiation\Decoder\DecoderProvider;
use Negotiation\Decoder\DecoderProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var FormatNegotiatorInterface
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

    /**
     * @var array
     */
    private $defaultOptions = array(
        'format_priorities'   => array(),
        'language_priorities' => array(),
    );

    /**
     * @var array
     */
    private $options;

    public function __construct(
        HttpKernelInterface $app,
        FormatNegotiatorInterface $formatNegotiator = null,
        NegotiatorInterface $languageNegotiator     = null,
        DecoderProviderInterface $decoderProvider   = null,
        array $options = array()
    ) {
        $this->app                = $app;
        $this->formatNegotiator   = $formatNegotiator   ?: new FormatNegotiator();
        $this->languageNegotiator = $languageNegotiator ?: new LanguageNegotiator();
        $this->decoderProvider    = $decoderProvider    ?: new DecoderProvider(array(
            'json' => new JsonEncoder(),
            'xml'  => new XmlEncoder(),
        ));
        $this->options = array_merge($this->defaultOptions, $options);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // `Accept` header
        if (null !== $accept = $request->headers->get('Accept')) {
            $priorities = $this->formatNegotiator->normalizePriorities($this->options['format_priorities']);
            $accept     = $this->formatNegotiator->getBest($accept, $priorities);

            $request->attributes->set('_accept', $accept);

            if (null !== $accept && !$accept->isMediaRange()) {
                $request->attributes->set('_mime_type', $accept->getValue());
                $request->attributes->set('_format', $this->formatNegotiator->getFormat($accept->getValue()));
            }
        }

        // `Accept-Language` header
        if (null !== $accept = $request->headers->get('Accept-Language')) {
            $accept = $this->languageNegotiator->getBest($accept, $this->options['language_priorities']);
            $request->attributes->set('_accept_language', $accept);

            if (null !== $accept) {
                $request->attributes->set('_language', $accept->getValue());
            }
        }

        try {
            // `Content-Type` header
            $this->decodeBody($request);
        } catch (BadRequestHttpException $e) {
            if (true === $catch) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->app->handle($request, $type, $catch);
    }

    private function decodeBody(Request $request)
    {
        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH', 'DELETE'))) {
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
