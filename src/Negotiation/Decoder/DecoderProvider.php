<?php

namespace Negotiation\Decoder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class DecoderProvider implements DecoderProviderInterface
{
    /**
     * @var array
     */
    private $decoders;

    /**
     * @param array $decoders List of key (format) value (instance) of decoders
     */
    public function __construct(array $decoders)
    {
        $this->decoders = $decoders;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($format)
    {
        return isset($this->decoders[$format]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDecoder($format)
    {
        if (!$this->supports($format)) {
            throw new \InvalidArgumentException(sprintf("Format '%s' is not supported", $format));
        }

        return $this->decoders[$format];
    }
}
