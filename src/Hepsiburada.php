<?php


class Hepsiburada
{
    protected $username;
    protected $password;
    protected $merchantId;
    protected $token;
    protected $client;
    protected $generalHeaders;

    /**
     * Hepsiburada constructor.
     * @param $username
     * @param $password
     * @param string $merchantId
     */
    public function __construct($username, $password, $merchantId = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->merchantId = $merchantId;

        $this->client = new GuzzleHttp\Client();

        $this->generateToken();

        $this->generalHeaders = [
            'Authorization' => sprintf('Bearer %s', $this->token),
            'Accept' => 'application/json'
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
        $response = $this->client->request('POST', 'https://mpop-sit.hepsiburada.com/api/authenticate', [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
                'authenticationType' => 'INTEGRATOR'
            ]
        ]);

        if (!($token = json_decode($response->getBody(), true)['id_token'])) {
            throw new Exception('Getting token error.');
        }

        $this->token = $token;

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

        $query = http_build_query($parameters);

        if (!empty($page) || !empty($size)) {
            $query = sprintf('?%s', $query);
        }

        $uri = sprintf('https://mpop-sit.hepsiburada.com/product/api/categories/get-all-categories%s', $query);

        $response = $this->client->request('GET', $uri, [
            'headers' => $this->generalHeaders
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getListings() {
        $uri = sprintf(
            'https://listing-external-sit.hepsiburada.com/listings/merchantid/%s',
            $this->merchantId
        );

        $response = $this->client->request('GET', $uri, [
            'auth' => [
                $this->username,
                $this->password
            ]
        ]);

        return $response->getBody();
    }
}