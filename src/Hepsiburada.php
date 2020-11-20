<?php

namespace Hepsiburada;

use GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Spatie\ArrayToXml\ArrayToXml;

/**
 * Hepsiburada REST API PHP Wrapper
 */
class Hepsiburada
{
    /**
     * @var string Hepsiburada integration username
     */
    protected $_username;

    /**
     * @var string Hepsiburada integration password
     */
    protected $_password;

    /**
     * @var string Hepsiburada integration merchant Id
     */
    protected $_merchantId;

    /**
     * @var string The variable that will store generated JWT token by username and password
     */
    protected $_token;

    /**
     * @var GuzzleHttp\Client REST API client to use Hepsiburada integration API
     */
    protected $_client;

    /**
     * @var array Header data to use Hepsiburada catalog methods by JWT token authorization
     */
    protected $_generalHeaders;

    /**
     * @var array Auth data to use listing methods by HTTP basic authentication
     */
    protected $_basicAuthInfo;

    /**
     * @var string Endpoint location to use listing methods
     */
    private $_listingSitUri = 'https://listing-external-sit.hepsiburada.com';

    /**
     * @var string Endpoint location to use Hepsiburada catalog methods
     */
    private $_mpopSitUri = 'https://mpop-sit.hepsiburada.com';

    /**
     * The Hepsiburada integration username, password and merchant Id
     * should be passed to the constructor.
     *
     * Example usage:
     *
     *      $hb = new Hepsiburada('<USERNAME>', '<PASSWORD>', '<MERCHANT_ID>');
     *
     * @param string $username
     * @param string $password
     * @param string $merchantId
     * @throws HepsiburadaException|GuzzleException
     */
    public function __construct(string $username, string $password, string $merchantId)
    {
        $this->_username = $username;
        $this->_password = $password;
        $this->_merchantId = $merchantId;

        $this->_client = new HepsiburadaRestClient();

        $this->setToken($this->generateToken());

        $this->_generalHeaders = [
            'Authorization' => \sprintf('Bearer %s', $this->_token),
            'Accept' => 'application/json'
        ];

        $this->_basicAuthInfo = [
            $this->_username,
            $this->_password
        ];
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username)
    {
        $this->_username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->_password = $password;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_merchantId;
    }

    /**
     * @param string $merchantId
     */
    public function setMerchantId(string $merchantId)
    {
        $this->_merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->_token = $token;
    }

    /**
     * Generate a JWT token to auth
     *
     * @return string
     * @throws HepsiburadaException|GuzzleException
     */
    public function generateToken()
    {
        $uri = \sprintf('%s/api/authenticate', $this->_mpopSitUri);

        $response = $this->_client->request('POST', $uri, [
            'json' => [
                'username' => $this->_username,
                'password' => $this->_password,
                'authenticationType' => 'INTEGRATOR'
            ]
        ]);

        if (!($token = \json_decode($response->getBody(), true)['id_token'])) {
            throw new HepsiburadaException('Getting token error.');
        }

        return $token;
    }

    /**
     * @param int|string|null $page
     * @param int|string|null $size
     * @return array
     * @throws HepsiburadaException|GuzzleException
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

        $uri = \sprintf('%s/product/api/categories/get-all-categories%s', $this->_mpopSitUri, $query);

        $response = $this->_client->request('GET', $uri, [
            'headers' => $this->_generalHeaders
        ]);

        return (array) \json_decode($response->getBody(), true);
    }

    /**
     * Request to add products to Hepsiburada catalog. It returns the tracking id of the request.
     *
     * Example usage:
     *
     * $hb->sendProducts('[
     *      {
     *          "categoryId": 18021982,
     *          "merchant": "6fc6d90d-ee1d-4372-b3a6-264b1275e9ff",
     *          "attributes": {
     *              "merchantSku": "SAMPLE-SKU-INT-0",
     *              "VaryantGroupID": "Hepsiburada0",
     *              "Barcode": "1234567891234",
     *              "UrunAdi": "Roth Tyler",
     *              "UrunAciklamasi": "Duis enim duis magna ex veniam elit id Lorem cillum minim nisi id aliquip.
     *              Laboris magna id est et deserunt adipisicing tempor eu ea officia ipsum deserunt. Irure occaecat
     *              sit aliquip elit ipsum sint dolore quis est amet aute pariatur cupidatat fugiat. Cillum pariatur
     *              pariatur occaecat sint. Aliqua qui in exercitation nulla aliquip id ipsum aliquip ad ut excepteur
     *              culpa consequat aliquip. Nisi ut ex tempor enim adipisicing anim irure pariatur.\r\n",
     *              "Marka": "Nike",
     *              "GarantiSuresi": 24,
     *              "kg": "1",
     *              "tax_vat_rate": "5",
     *              "Image1": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image2": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image3": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image4": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image5": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "renk_variant_property": "Siyah",
     *              "ebatlar_variant_property": "Büyük Ebat"
     *          }
     *      },
     *      {
     *          "categoryId": 18021982,
     *          "merchant": "6fc6d90d-ee1d-4372-b3a6-264b1275e9ff",
     *          "attributes": {
     *              "merchantSku": "SAMPLE-SKU-INT-1",
     *              "VaryantGroupID": "Hepsiburada1",
     *              "Barcode": "987654321987",
     *              "UrunAdi": "Roth Tyler",
     *              "UrunAciklamasi": "Duis enim duis magna ex veniam elit id Lorem cillum minim nisi id aliquip.
     *              Laboris magna id est et deserunt adipisicing tempor eu ea officia ipsum deserunt. Irure occaecat
     *              sit aliquip elit ipsum sint dolore quis est amet aute pariatur cupidatat fugiat. Cillum pariatur
     *              pariatur occaecat sint. Aliqua qui in exercitation nulla aliquip id ipsum aliquip ad ut excepteur
     *              culpa consequat aliquip. Nisi ut ex tempor enim adipisicing anim irure pariatur.\r\n",
     *              "Marka": "Nike",
     *              "GarantiSuresi": 24,
     *              "kg": "1",
     *              "tax_vat_rate": "5",
     *              "Image1": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image2": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image3": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image4": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "Image5": "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *              "renk_variant_property": "Kırmızı",
     *              "ebatlar_variant_property": "Büyük Ebat"
     *          }
     *      }
     *   ]');
     *
     *
     * @param string $productsJson
     * @return string
     */
    public function sendProducts(string $productsJson)
    {
        $tempFile = \tempnam(\sys_get_temp_dir(), 'products');

        // Convert text JSON to a temp file so it can be uploaded
        $newTempFile = \sprintf('%s.json', $tempFile);
        \rename($tempFile, $newTempFile);
        $tempFile = $newTempFile;
        \file_put_contents($tempFile, $productsJson);

        $uri = \sprintf('%s/product/api/products/import', $this->_mpopSitUri);

        try {
            $response = $this->_client->request('POST',
                $uri, [
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => \fopen($tempFile, 'rb')
                        ]
                    ],
                    'headers' => $this->_generalHeaders
                ]);

            if (
                $response->getReasonPhrase() === 'OK'
                &&
                !empty($trackingId = ((array) json_decode($response->getBody(), true))['data']['trackingId'])
            ) {
                return $trackingId;
            }
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return null;
        }

        return null;
    }

    /**
     * The array input variant of the sendProducts() method.
     *
     * Example usage:
     *
     * $hb->sendProductsAsArray([
     *       [
     *           "categoryId" => 18021982,
     *           "merchant" => "6fc6d90d-ee1d-4372-b3a6-264b1275e9ff",
     *           "attributes" => [
     *               "merchantSku" => "SAMPLE-SKU-INT-0",
     *               "varyantGroupID" => "Hepsiburada0",
     *               "Barcode" => "1234567891234",
     *               "UrunAdi" => "Roth Tyler",
     *               "UrunAciklamasi" => "Duis enim duis magna ex veniam elit id Lorem cillum minim nisi id
     *               aliquip. Laboris magna id est et deserunt adipisicing tempor eu ea officia ipsum deserunt.
     *               Irure occaecat sit aliquip elit ipsum sint dolore quis est amet aute pariatur cupidatat fugiat.
     *               Cillum pariatur pariatur occaecat sint. Aliqua qui in exercitation nulla aliquip id ipsum aliquip
     *               ad ut excepteur culpa consequat aliquip. Nisi ut ex tempor enim adipisicing anim irure pariatur.",
     *               "Marka" => "Nike",
     *               "GarantiSuresi" => "24",
     *               "kg" => "1",
     *               "tax_vat_rate" => "5",
     *               "Image1" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image2" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image3" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image4" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image5" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "renk_variant_property" => "Siyah",
     *               "ebatlar_variant_property" => "Büyük Ebat",
     *           ]
     *       ],
     *       [
     *           "categoryId" => 18021982,
     *           "merchant" => "6fc6d90d-ee1d-4372-b3a6-264b1275e9ff",
     *           "attributes" => [
     *               "merchantSku" => "SAMPLE-SKU-INT-1",
     *               "varyantGroupID" => "Hepsiburada1",
     *               "Barcode" => "12547896523145",
     *               "UrunAdi" => "Roth Tyler",
     *               "UrunAciklamasi" => "Duis enim duis magna ex veniam elit id Lorem cillum minim nisi id
     *               aliquip. Laboris magna id est et deserunt adipisicing tempor eu ea officia ipsum deserunt.
     *               Irure occa*ecat sit aliquip elit ipsum sint dolore quis est amet aute pariatur cupidatat fugiat.
     *               Cillum pariatur pariatur occaecat sint. Aliqua qui in exercitation nulla aliquip id ipsum aliquip
     *               ad ut excepteur culpa consequat aliquip. Nisi ut ex tempor enim adipisicing anim irure pariatur.",
     *               "Marka" => "Nike",
     *               "GarantiSuresi" => "24",
     *               "kg" => "1",
     *               "tax_vat_rate" => "5",
     *               "Image1" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image2" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image3" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image4" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "Image5" => "https://productimages.hepsiburada.net/s/27/552/10194862145586.jpg",
     *               "renk_variant_property" => "Kırmızı",
     *               "ebatlar_variant_property" => "Küçük Ebat",
     *           ]
     *       ],
     *   ]);
     *
     * @param array $productsArray
     * @return string
     */
    public function sendProductsAsArray(array $productsArray)
    {
        $json = \json_encode($productsArray);

        return $this->sendProducts($json);
    }

    /**
     * @param string $trackingId
     * @return array
     */
    public function productSendingStatus(string $trackingId)
    {
        $uri = \sprintf('%s/product/api/products/status/%s', $this->_mpopSitUri, $trackingId);

        try {
            $response = $this->_client->request('GET', $uri, [
                'headers' => $this->_generalHeaders
            ]);

            return (array) \json_decode($response->getBody(), true);
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return null;
        }
    }

    /**
     * @param int|string|null $offset
     * @param int|string|null $limit
     * @return array
     * @throws HepsiburadaException|GuzzleException
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
            $this->_listingSitUri,
            $this->_merchantId,
            $query
        );

        $response = $this->_client->request('GET', $uri, [
            'auth' => $this->_basicAuthInfo
        ]);

        $listings = $this->_streamToText($response->getBody());

        return (array) \json_decode($listings, true);
    }

    /**
     * @param string $sku
     * @return bool
     */
    public function activateListing(string $sku)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/activate',
            $this->_listingSitUri,
            $this->_merchantId,
            $sku
        );

        try {
            $response = $this->_client->request('POST', $uri, [
                'auth' => $this->_basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return false;
        }
    }

    /**
     * @param string $sku
     * @return bool
     */
    public function deactivateListing(string $sku)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/deactivate',
            $this->_listingSitUri,
            $this->_merchantId,
            $sku
        );

        try {
            $response = $this->_client->request('POST', $uri, [
                'auth' => $this->_basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return false;
        }
    }

    /**
     * Update or if not exists create a listing to do selling by Hepsiburada SKU of a product.
     * It returns the tracking id of the request.
     *
     * Example usage:
     *
     *      $hb->createOrUpdateListing([
     *           'HepsiburadaSku' => 'HBV00000OHU6L',
     *           'MerchantSku' => 'TEST123',
     *           'Price' => 14,
     *           'AvailableStock' => 2,
     *           'DispatchTime' => 1,
     *           'CargoCompany1' => 'Aras Kargo',
     *           'CargoCompany2' => '',
     *           'CargoCompany3' => '',
     *           'ShippingAddressLabel' => 'BIRINCIL',
     *           'ClaimAddressLabel' => 'BIRINCIL',
     *           'MaximumPurchasableQuantity' => 10,
     *       ]);
     *
     * @param array $data
     * @return string
     */
    public function createOrUpdateListing(array $data)
    {
        $root = [
            'rootElementName' => 'listings',
            '_attributes' => [
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xmlns:xsd' => 'http://www.w3.org/2001/XMLSchema',
            ]
        ];

        $xml = ArrayToXml::convert(['listing' => $data], $root);

        $uri = \sprintf(
            '%s/listings/merchantid/%s/inventory-uploads',
            $this->_listingSitUri,
            $this->_merchantId
        );

        try {
            $response = $this->_client->request('POST', $uri, [
                'body' => $xml,
                'auth' => $this->_basicAuthInfo
            ]);

            if (
                $response->getReasonPhrase() === 'OK'
                &&
                !empty($trackingId = ((array) json_decode($response->getBody(), true))['id'])
            ) {
                return $trackingId;
            }
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return null;
        }
        return null;
    }

    /**
     * @param string $trackingId
     * @return ResponseInterface
     * @throws HepsiburadaException
     * @throws GuzzleException
     */
    public function listingUpdateStatus(string $trackingId)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/inventory-uploads/id/%s',
            $this->_listingSitUri,
            $this->_merchantId,
            $trackingId
        );

        $response = $this->_client->request('GET', $uri, [
            'auth' => $this->_basicAuthInfo
        ]);

        return \json_decode($response->getBody(), true);
    }

    /**
     * @param string $sku
     * @param string $merchantSku
     * @return bool
     */
    public function deleteListing(string $sku, string $merchantSku)
    {
        $uri = \sprintf(
            '%s/listings/merchantid/%s/sku/%s/merchantsku/%s',
            $this->_listingSitUri,
            $this->_merchantId,
            $sku,
            $merchantSku
        );

        try {
            $response = $this->_client->request('DELETE', $uri, [
                'auth' => $this->_basicAuthInfo
            ]);

            return $response->getReasonPhrase() === 'OK';
        }
        catch (HepsiburadaException|GuzzleException $e) {
            return false;
        }
    }

    /**
     * @param object $body
     * @return string
     */
    protected function _streamToText(object $body)
    {
        $buffer = "";

        while (!$body->eof()) {
            $buffer .= $body->read(1024);
        }

        return $buffer;
    }
}