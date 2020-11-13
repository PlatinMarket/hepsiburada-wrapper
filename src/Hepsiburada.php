<?php

namespace Hepsiburada;

use GuzzleHttp;

/**
 * Class Hepsiburada
 */
class Hepsiburada
{
    protected $username;
    protected $password;
    protected $merchantId;
    protected $token;
    protected $client;
    protected $generalHeaders;
    protected $basicAuthInfo;

    private $listingSitUri = 'https://listing-external-sit.hepsiburada.com';
    private $mpopSitUri = 'https://mpop-sit.hepsiburada.com';

    /**
     * Hepsiburada constructor.
     * @param $username
     * @param $password
     * @param string $merchantId
     */
    public function __construct($username, $password, $merchantId)
    {
        $this->username = $username;
        $this->password = $password;
        $this->merchantId = $merchantId;

        $this->client = new GuzzleHttp\Client();

        $this->setToken($this->generateToken());

        $this->generalHeaders = [
            'Authorization' => \sprintf('Bearer %s', $this->token),
            'Accept' => 'application/json'
        ];

        $this->basicAuthInfo = [
            $this->username,
            $this->password
        ];
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param $merchantId
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function generateToken()
    {
        $uri = \sprintf('%s/api/authenticate', $this->mpopSitUri);

        $response = $this->client->request('POST', $uri, [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
                'authenticationType' => 'INTEGRATOR'
            ]
        ]);

        if (!($token = \json_decode($response->getBody(), true)['id_token'])) {
            throw new \Exception('Getting token error.');
        }

        return $token;
    }

    /**
     * @param null $page
     * @param null $size
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchCategories($page = null, $size = null)
    {
        $parameters = [
            'page' => $page,
            'size' => $size
        ];

        $query = \http_build_query($parameters);

        if (!empty($page) || !empty($size)) {
            $query = \sprintf('?%s', $query);
        }

        $uri = \sprintf('%s/product/api/categories/get-all-categories%s', $this->mpopSitUri, $query);

        $response = $this->client->request('GET', $uri, [
            'headers' => $this->generalHeaders
        ]);

        return \json_decode($response->getBody(), true);
    }

    /**
     * @param $json
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendProducts($json)
    {
        $tempFile = \tempnam(\sys_get_temp_dir(), 'products');

        // Convert text JSON to a temp file so it can be uploaded
        $newTempFile = \sprintf('%s.json', $tempFile);
        \rename($tempFile, $newTempFile);
        $tempFile = $newTempFile;
        \file_put_contents($tempFile, $json);

        $uri = \sprintf('%s/product/api/products/import', $this->mpopSitUri);

        $response = $this->client->request('POST',
            $uri, [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => \fopen($tempFile, 'rb')
                    ]
                ],
                'headers' => $this->generalHeaders
            ]);

        return $response;
    }

    /**
     * @param $trackingId
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function productSendingStatus($trackingId) {
        $uri = \sprintf('%s/product/api/products/status/%s', $this->mpopSitUri, $trackingId);

        $response = $this->client->request('GET', $uri, [
            'headers' => $this->generalHeaders
        ]);

        return \json_decode($response->getBody(), true);
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchListings($offset = null, $limit = null)
    {
        $parameters = [
            'offset' => $offset,
            'limit' => $limit
        ];

        $query = \http_build_query($parameters);

        if (!empty($offset) || !empty($limit)) {
            $query = \sprintf('?%s', $query);
        }

        $uri = \sprintf(
            '%s/listings/merchantid/%s%s',
            $this->listingSitUri,
            $this->merchantId,
            $query
        );

        $response = $this->client->request('GET', $uri, [
            'auth' => $this->basicAuthInfo
        ]);

        $listings = $this->streamToText($response->getBody());

        return \json_decode($listings, true);
    }

    /**
     * @param $sku
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function activateListing($sku)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/activate',
            $this->listingSitUri,
            $this->merchantId,
            $sku
        );

        try {
            $response = $this->client->request('POST', $uri, [
                'auth' => $this->basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $sku
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deactivateListing($sku)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/deactivate',
            $this->listingSitUri,
            $this->merchantId,
            $sku
        );

        try {
            $response = $this->client->request('POST', $uri, [
                'auth' => $this->basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $data
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrUpdateListing($data)
    {
        $root = [
            'rootElementName' => 'listings',
            '_attributes' => [
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xmlns:xsd' => 'http://www.w3.org/2001/XMLSchema',
            ]
        ];

        $xml = \Spatie\ArrayToXml\ArrayToXml::convert(['listing' => $data], $root);

        $uri = \sprintf(
            '%s/listings/merchantid/%s/inventory-uploads',
            $this->listingSitUri,
            $this->merchantId
        );

        $response = $this->client->request('POST', $uri, [
            'body' => $xml,
            'auth' => $this->basicAuthInfo
        ]);

        $body = $this->streamToText($response->getBody());

        return \json_decode($response->getBody(), true);
    }

    /**
     * @param $trackingId
     * @return \Psr\Http\Message\ResponseInterface
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function listingUpdateStatus($trackingId)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/inventory-uploads/id/%s',
            $this->listingSitUri,
            $this->merchantId,
            $trackingId
        );

        $response = $this->client->request('GET', $uri, [
            'auth' => $this->basicAuthInfo
        ]);

        return $response;
    }

    /**
     * @param $sku
     * @param $merchantSku
     * @return bool
     * @throws GuzzleHttp\Exception\GuzzleException
     */
    public function deleteListing($sku, $merchantSku) {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/merchantsku/%s',
            $this->listingSitUri,
            $this->merchantId,
            $sku,
            $merchantSku
        );

        try {
            $response = $this->client->request('DELETE', $uri, [
                'auth' => $this->basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $body
     * @return string
     */
    protected function streamToText($body)
    {
        $buffer = "";

        while (!$body->eof()) {
            $buffer .= $body->read(1024);
        }

        return $buffer;
    }
}