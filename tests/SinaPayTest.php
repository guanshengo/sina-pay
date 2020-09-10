<?php

namespace Guanshengo\SinaPay\Tests;

use Guanshengo\SinaPay\SinaPay;
use Guanshengo\SinaPay\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SinaPayTest extends TestCase
{
    public function testGetSinpayWithInvalid()
    {
        $config = [
            'sinapay_file_path'=>"../files/",
            'sinapay_rsa_sign_private_key'=>dirname(__File__) . "/../key/rsa_sign_private.pem",
            "sinapay_rsa_sign_public_key"=>dirname(__File__) . "/../key/rsa_sign_public.pem",
            "sinapay_rsa_public__key"=>dirname(__File__) . "/../key/rsa_public.pem",
            "sinapay_debug_status"=>true,
            "sinapay_sftp_address"=>"222.73.39.37",
            "sinapay_sftp_port"=> "50022",
            "sinapay_sftp_username"=>"200009166773",
            "sinapay_sftp_privatekey"=>dirname(__File__) . "/../key/id_rsa",
            "sinapay_sftp_publickey" => dirname(__File__) . "/../key/id_rsa.pub",
            "sinapay_sftp_upload_directory"=>dirname(__File__) . "/upload"
        ];
        $p = [
            'service'=>'query_merchant_config',
            'partner_id'=>'200009166773',
            'version'=>1.2,
            'config_key'=>'MEMBER_INFO_GENERAL_NOTIFY_URL'
        ];

        $s = new SinaPay($config);
        $s->setParams($p);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('config does not exist');
        $r = $s->send();
        var_dump($r);die;
        $this->fail('Failed to assert getWeather throw exception with invalid argument.');
    }
}