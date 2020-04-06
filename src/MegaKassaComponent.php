<?php

namespace ChurakovMike\Megakassa;

use yii\base\Component;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;
use yii\web\Response;

/**
 * Class MegaKassaComponent.
 * @package ChurakovMike\Megakassa
 *
 * @property string $baseUrl
 * @property integer $shopId
 * @property string $secretKey
 * @property string $apiVersion
 * @property Client $httpClient
 */
class MegaKassaComponent extends Component
{

    /**
     * Available currencies.
     */
    const CURRENCIES = [
        'RUB',
        'USD',
        'EUR',
    ];

    /**
     * Request methods.
     */
    const
        ACTION_PAYMENT_METHODS_LIST = 'payment_methods_list',
        ACTION_SHOP_BALANCE = 'shop_balance',
        ACTION_WITHDRAW_CREATE = 'withdraw_create',
        ACTION_GET_WITHDRAW = 'get_withdraw',
        ACTION_WITHDRAW_LIST = 'withdraws_list';

    /**
     * Set this property in config.
     *
     * @var integer $shopId
     */
    public $shopId;

    /**
     * Set secret key in config.
     *
     * @var string $secretKey
     */
    public $secretKey;

    /**
     * @var string $baseUrl
     */
    protected $baseUrl = 'https://api.megakassa.ru';

    /**
     * Api version.
     *
     * @var string $version
     */
    protected $apiVersion = 'v1.0';

    /**
     * @var Client $_httpClient
     */
    private $_httpClient;

    /**
     * MegaKassaComponent constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * Get payment systems list.
     *
     * @return array|Response
     * @throws \Exception
     *
     * @see https://megakassa.ru/api/#method_1
     */
    public function getPaymentSystems()
    {
        return $this->request([
            'shop_id' => $this->shopId,
        ], self::ACTION_PAYMENT_METHODS_LIST);
    }

    /**
     * Get balance by all currencies.
     *
     * @return array|Response
     * @throws \Exception
     *
     * @see https://megakassa.ru/api/#method_2
     */
    public function getBalance()
    {
        return $this->request([
            'shop_id' => $this->shopId,
        ], self::ACTION_SHOP_BALANCE);
    }

    /**
     * Create withdraw by available payment systems.
     *
     * @param int $methodId
     * @param int $amount
     * @param int $amount_due
     * @param string $currencyFrom
     * @param string $wallet
     * @param string $comment
     * @param int $debug
     * @return array|Response
     * @throws \Exception
     * @see https://megakassa.ru/api/#method_3
     */
    public function createWithdraw(
        int $methodId = 0,
        float $amount = null,
        float $amount_due = null,
        string $currencyFrom,
        string $wallet,
        string $comment = '',
        int $debug = 0
    ) {
        $data = [
            'shop_id' => $this->shopId,
            'method_id' => $methodId,
            'currency_from' => $currencyFrom,
            'wallet' => $wallet,
            'debug' => $debug,
            'comment' => $comment,
        ];

        if (is_null($amount)) {
            $data['amount_due'] = $amount_due;
        }

        if (is_null($amount_due)) {
            $data['amount'] = $amount;
        }

        return $this->request($data, self::ACTION_WITHDRAW_CREATE);
    }

    /**
     * Get withdraw details.
     *
     * @param int $withdrawId
     * @return array|Response
     * @throws \Exception
     * @see https://megakassa.ru/api/#method_4
     */
    public function getWithdraw(int $withdrawId)
    {
        $data = [
            'shop_id' => $this->shopId,
            'withdraw_id' => $withdrawId,
        ];

        return $this->request($data, self::ACTION_GET_WITHDRAW);
    }

    /**
     * Get 50 withdraws on page.
     *
     * @param int $page
     * @return array|Response
     * @throws \Exception
     * @see https://megakassa.ru/api/#method_5
     */
    public function getWithdrawList(int $page = 0)
    {
        return $this->request([
            'shop_id' => $this->shopId,
            'page' => $page,
        ], self::ACTION_WITHDRAW_LIST);
    }

    /**
     * @param array $data
     * @return string
     */
    public function generateSignature(array $data): string
    {
        ksort($data);
        $sign = array_values($data);
        $sign[] = $this->secretKey;
        $sign = md5(join(':', $sign));

        return $sign;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function getHttpClient(): Client
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = \Yii::createObject($this->defaultHttpClientConfig());
        }

        return $this->_httpClient;
    }

    /**
     * Default settings for client.
     *
     * @return array
     */
    protected function defaultHttpClientConfig(): array
    {
        return [
            'class' => Client::class,
            'baseUrl' => $this->baseUrl,
            'transport' => CurlTransport::class,
        ];
    }

    /**
     * Base request with parameters.
     *
     * @param array $data
     * @param string $method
     * @return Response|array
     * @throws \Exception
     */
    protected function request(array $data, string $method)
    {
        $data['sign'] = $this->generateSignature($data);
        $url = $this->buildUrl($method);
        $rowData = http_build_query($data);

        try {
            $request = $this->httpClient->get($url ?? $this->httpClient->baseUrl, $data);
            $response = $request->send();
            if (!$response->isOk) {
                throw new \Exception($response->data);
            }

            return $response->data;
        } catch (\Exception $exception) {
            \Yii::error([
                'errorMessage' => $exception->getMessage(),
                'data' => $data,
                'dataRow' => $rowData,
            ]);

            throw $exception;
        }
    }

    /**
     * @param string $method
     * @return string
     */
    protected function buildUrl(string $method): string
    {
        return $this->baseUrl . '/' . $this->apiVersion . '/' . $method . '/?';
    }
}
