<?php

namespace ChurakovMike\Megakassa\filters;

use ChurakovMike\Megakassa\exceptions\WrongRequestSenderException;
use yii\base\ActionFilter;

/**
 * Class MegakassaAccessFilter
 * @package ChurakovMike\Megakassa\filters
 */
class MegakassaAccessFilter extends ActionFilter
{
    /**
     * List of allowed ip for callbacks.
     */
    public $allowedIps = [
        '5.196.121.217',
    ];

    /**
     * Server params.
     *
     * @var array
     */
    public $params = [
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws WrongRequestSenderException
     */
    public function beforeAction($action)
    {
        if (!$this->checkSenderIp()) {
            throw new WrongRequestSenderException();
        }

        return parent::beforeAction($action);
    }

    /**
     * Check sender IP
     *
     * @return bool
     */
    public function checkSenderIp()
    {
        foreach ($this->params as $param) {
            if (!empty($_SERVER[$param]) && in_array($_SERVER[$param], $this->allowedIps)) {
                $ipChecked = true;
            }
        }

        return $ipChecked;
    }
}
