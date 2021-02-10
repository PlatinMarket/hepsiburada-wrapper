<?php

namespace Hepsiburada;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HepsiburadaRestClient
 */
class HepsiburadaRestClient extends Client
{
    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws HepsiburadaException
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        try {
            return parent::request($method, $uri, $options);
        }
        catch (GuzzleException $e) {
            throw new HepsiburadaException($e);
        }
    }
}