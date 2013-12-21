<?php

namespace Negotiation\Decoder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
interface DecoderProviderInterface
{
    /**
     * @param string $format format
     *
     * @return boolean
     */
    public function supports($format);

    /**
     * @param string $format format
     *
     * @return \Symfony\Component\Serializer\SerializerInterface
     */
    public function getDecoder($format);
}
