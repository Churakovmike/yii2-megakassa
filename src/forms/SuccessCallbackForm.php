<?php

namespace ChurakovMike\Megakassa\forms;

use yii\base\Model;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Class SuccessCallbackForm
 * @package ChurakovMike\Megakassa\forms
 *
 * @property integer $uid
 * @property double $amount
 * @property double $amount_shop
 * @property double $amount_client
 * @property string $currency
 * @property string $order_id
 * @property integer $payment_method_id
 * @property string $payment_method_title
 * @property string $client_email
 * @property string $creation_time
 * @property string $payment_time
 * @property string $status
 * @property string $debug
 * @property string $signature
 * @property string $secretKey
 *
 * @mixin AttributeTypecastBehavior
 */
class SuccessCallbackForm extends Model
{
    const
        STATUS_SUCCESS = 'success',
        STATUS_FAIL = 'fail';

    /**
     * Statuses list.
     */
    const STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAIL,
    ];

    /**
     * Available currencies.
     */
    const CURRENCIES = [
        'RUB',
        'USD',
        'EUR',
    ];

    /**
     * Unique payment identifier.
     *
     * @var integer $uid
     */
    public $uid;

    /**
     * Initial amount.
     *
     * @var double $amount
     */
    public $amount;

    /**
     *  Amount credited to the site account.
     *
     * @var double $amount_shop
     */
    public $amount_shop;

    /**
     * Amount paid by customer.
     *
     * @var double $amount_client
     */
    public $amount_client;

    /**
     * Currency by ISO 4217(RUB, USD, EUR).
     *
     * @var string $currency
     */
    public $currency;

    /**
     * Your inique order identifier.
     *
     * @var string $order_id
     */
    public $order_id;

    /**
     * Payment system ID.
     *
     * @var integer $payment_method_id
     */
    public $payment_method_id;

    /**
     * Payment system name.
     *
     * @var string $payment_method_title
     */
    public $payment_method_title;

    /**
     * Client email.
     *
     * @var string $client_email
     */
    public $client_email;

    /**
     * @var string $creation_time
     */
    public $creation_time;

    /**
     * @var string $payment_time
     */
    public $payment_time;

    /**
     * @var string $status
     */
    public $status;

    /**
     * @var string $debug
     */
    public $debug;

    /**
     * @var string $signature
     */
    public $signature;

    /**
     * @var string $secretKey
     */
    public $secretKey;

    /**
     * SuccessCallbackForm constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['uid', 'amount', 'amount_shop', 'amount_client', 'currency', 'order_id', 'payment_method_id',
                'payment_method_title', 'client_email', 'signature'], 'required'],
            [['uid', 'payment_method_id'], 'integer'],
            [['amount', 'amount_shop', 'amount_client',], 'double'],
            [['currency', 'order_id', 'payment_method_title', 'client_email', 'creation_time', 'payment_time',
                'status', 'debug', 'signature'], 'string'],
            [['client_email'], 'email'],
            [['signature'], 'validateSignature'],
            [['status'], 'validateStatus'],
            [['currency'], 'validateCurrency'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'uid' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'payment_method_id' => AttributeTypecastBehavior::TYPE_INTEGER,
                    'amount' => AttributeTypecastBehavior::TYPE_FLOAT,
                    'amount_shop' => AttributeTypecastBehavior::TYPE_FLOAT,
                    'amount_client' => AttributeTypecastBehavior::TYPE_FLOAT,
                ],
                'typecastAfterFind' => false,
                'typecastBeforeSave' => false,
                'typecastAfterSave' => false,
            ],
        ];
    }

    /**
     * Validate signature format.
     *
     * @param $attribute
     * @param $params
     */
    public function validateSignature($attribute, $params)
    {
        if(!preg_match('/^[0-9a-f]{32}$/', $this->$attribute)) {
            $this->addError($attribute, 'Wrong signature format');
        }

        $singCalc = md5(implode(':', [
            $this->uid,
            $this->amount,
            $this->amount_shop,
            $this->amount_client,
            $this->currency,
            $this->order_id,
            $this->payment_method_id,
            $this->payment_method_title,
            $this->creation_time,
            $this->payment_time,
            $this->client_email,
            $this->status,
            $this->debug,
            $this->secretKey,
        ]));

        if ($singCalc !== $this->signature) {
            $this->addError('Signatures do not match');
        }
    }

    /**
     * Validate status.
     *
     * @param $attribute
     * @param $params
     */
    public function validateStatus($attribute, $params)
    {
        if (!in_array($attribute, self::STATUSES, true)) {
            $this->addError('Wrong payment status');
        }
    }

    /**
     * Validate currencies.
     *
     * @param $attribute
     * @param $params
     */
    public function validateCurrency($attribute, $params)
    {
        if (!in_array($attribute, self::CURRENCIES, true)) {
            $this->addError('Wrong currency value');
        }
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool|void
     */
    public function load($data, $formName = null)
    {
        parent::load($data, $formName);
        $this->typecastAttributes();
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values)) {
            $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $this->$name = $value;
                } elseif ($safeOnly) {
                    $this->onUnsafeAttribute($name, $value);
                }
            }
        }

        $this->typecastAttributes();
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isFail()
    {
        return $this->status === self::STATUS_FAIL;
    }
}
