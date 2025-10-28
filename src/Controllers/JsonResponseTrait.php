<?php

namespace WebImage\Controllers;

use Psr\Http\Message\ResponseInterface;

trait JsonResponseTrait
{
    abstract public function getResponse(): ResponseInterface;

    protected function jsonSuccessResponse(array $data = null): ResponseInterface
    {
        return $this->jsonResponse($data);
    }


    protected function jsonResponse(array $data = null, int $responseCode = null): ResponseInterface
    {
        if (!function_exists('json_encode')) throw new \RuntimeException("Missing JSON library");

        $message = $data == null ? '' : json_encode($data);

        return $this->jsonMessageResponse($responseCode, $message);
    }

    protected function jsonMessageResponse(int $responseCode = null, string $message = null): ResponseInterface
    {
        $response = $this->getResponse();
        if (!$response->hasHeader('Content-Type')) $response = $response->withHeader('Content-Type', 'application/json');
        if ($responseCode !== null) $response = $response->withStatus($responseCode);
        $response->getBody()->write($message ?? '');

        return $response;
    }
}