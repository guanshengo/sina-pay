<?php

namespace Guanshengo\SinaPay\Contracts;

interface GatewayInterface
{
    /**
     * set request param
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = array());

    /**
     * send request
     * @return mixed
     */
    public function send();
}