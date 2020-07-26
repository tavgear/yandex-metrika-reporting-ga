<?php

declare(strict_types=1);

namespace Tvg\YandexMetrika\ReportingGa;

/**
 * Client and report exception
 */
class Exception extends \Exception
{

    const ERR_CODE = 1;

    /**
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $responce = null;

    /**
     * Error server response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponce(): \Psr\Http\Message\ResponseInterface
    {
        return $this->responce;
    }

    /**
     * Error server response
     *
     * @param \Psr\Http\Message\ResponseInterface $responce
     * @return $this
     */
    public function setResponce(\Psr\Http\Message\ResponseInterface $responce)
    {
        $this->responce = $responce;
        return $this;
    }

}
