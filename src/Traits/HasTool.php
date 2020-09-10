<?php

namespace Guanshengo\SinaPay\Traits;

use Guanshengo\SinaPay\Support\Log;

trait HasTool
{
    /**
     * 置入原始参数sParaTemp
     * @param array $sParaTemp
     * @return array
     */
    protected function Encryption_sParaTemp(array $sParaTemp = []){
        foreach($this->needRSA as $k => $v){
            if (array_key_exists($v,$sParaTemp)){
                $sParaTemp[$v] = $this->rsaEncrypt($sParaTemp[$v], $this->config['sinapay_rsa_public__key'],"UTF-8");;
            }
        }
        return ($sParaTemp);
    }

    /**
     * getSignMsg 计算签名
     * @param array $pay_params 计算签名数据
     * @param string $sign_type 签名类型
     * @param string $_input_charset 签名字符集编码
     * @return string $signMsg 返回密文
     */
    function getSignMsg(array $pay_params = array(), string $sign_type = 'RSA',$_input_charset = 'UTF-8') {
        $params_str = "";
        $signMsg = "";
        foreach ( $pay_params as $key => $val ) {
            if ($key != "sign" && $key != "sign_type" && $key != "sign_version" && isset ( $val ) && @$val != "") {
                $params_str .= trim($key) . "=" . trim($val) . "&";
            }
        }
        $params_str = substr( $params_str, 0, -1 );
        //$params_str=mb_convert_encoding($params_str,$_input_charset);
        switch (@$sign_type) {
            case 'RSA' :
                $priv_key = file_get_contents($this->config['sinapay_rsa_sign_private_key'] );
                $pkeyid = openssl_pkey_get_private($priv_key);
                openssl_sign( $params_str, $signMsg, $pkeyid, OPENSSL_ALGO_SHA1 );
                openssl_free_key( $pkeyid );
                $signMsg = base64_encode( $signMsg );
                break;
            case 'MD5' :
            default :
                break;
        }
        return $signMsg;
    }

    /**
     * 拼接模拟提交数据
     * @param $pay_params
     * @return false|string
     */
    protected function createcurl_data($pay_params){
        $params_str = "";
        foreach ( $pay_params as $key => $val ) {
            if (isset ( $val ) && ! is_null ( $val ) && @$val != "") {
                $params_str .= "&" . trim($key) . "=" . urlencode ( urlencode ( trim ( $val ) ) );
            }
        }
        if ($params_str) {
            $params_str = substr ( $params_str, 1 );
        }
        return $params_str;
    }

    /**
     * 通过公钥进行rsa加密
     *
     * @param array $data 进行rsa公钥加密的数必传
     * @param string $public_key 加密用的公钥 必传
     * @param string $_input_charset 字符集编码
     * @return string 加密好的密文
     */
    protected function rsaEncrypt($data, $public_key,$_input_charset) {
        $encrypted = "";
        $cert = file_get_contents ($public_key );
        $pu_key = openssl_pkey_get_public ( $cert ); // 这个函数可用来判断公钥是否是可用�?
        openssl_public_encrypt ( trim($data), $encrypted, $pu_key ); // 公钥加密
        $encrypted = base64_encode ( $encrypted ); // 进行编码
        return $encrypted;
    }

    /**
     * checkSignMsg 回调签名验证
     * @param array $pay_params 参与签名验证的数据
     * @param string $sign_type  签名类型
     * @param string $_input_charset   签名字符集编码
     * @return boolean  签名结果
     */
    protected function checkSignMsg(array $pay_params = array(), string $sign_type = 'RSA',string $_input_charset = 'UTF-8') {
        $params_str = "";
        $signMsg = "";
        $return = false;
        foreach ( $pay_params as $key => $val ) {
            if ($key != "sign" && $key != "sign_type" && $key != "sign_version" && ! is_null ( $val ) && @$val != "") {
                $params_str .= "&" . trim($key) . "=" . trim($val);
            }
        }
        if ($params_str) {
            $params_str = substr ( $params_str, 1 );
        }
        //验证签名demo需要支持多字符集所以此处对字符编码进行转码处理,正常商户不存在多字符集问题
        //$params_str=mb_convert_encoding($params_str,$_input_charset,"UTF-8");
        switch (@$sign_type) {
            case 'RSA' :
                $cert = file_get_contents( $this->config['sinapay_rsa_sign_public_key'] );
                $pubkeyid = openssl_pkey_get_public( $cert );
                $ok = openssl_verify( $params_str, base64_decode ($pay_params['sign']), $cert, OPENSSL_ALGO_SHA1 );
                $return = $ok == 1 ? true : false;
                openssl_free_key($pubkeyid );
                break;
            default :
                break;
        }
        return $return;
    }


    /**
     * 文件摘要算法
     */
    protected function md5_file($filename) {
        return md5_file ( $filename );
    }

    /**
     * sftp上传企业资质
     * sftp upload
     * @param $file 上传文件路径
     * @param $filename 上传文件名
     * @return false 失败   true 成功
     */
    function sftp_upload($file,$filename) {
        $strServer = sinapay_sftp_address;
//        self::write_log("sftp连接地址:".$strServer);
        $strServerPort = sinapay_sftp_port;
//        self::write_log("sftp连接端口:".$strServerPort);
        $strServerUsername = sinapay_sftp_Username;
//        self::write_log("sftp连接用户名:".$strServerUsername);
        $strServerprivatekey = sinapay_sftp_privatekey;
//        self::write_log("sftp连接私钥:".sinapay_sftp_privatekey);
        $strServerpublickey = sinapay_sftp_publickey;
//        self::write_log("sftp连接公钥:".sinapay_sftp_publickey);
//        self::write_log("ssh2_connect status:".print_r(get_extension_funcs("ssh2_connect")));
        $resConnection = ssh2_connect($strServer,$strServerPort);
        if (ssh2_auth_pubkey_file ( $resConnection, $strServerUsername, $strServerpublickey, $strServerprivatekey ))
        {
            $resSFTP = ssh2_sftp ( $resConnection );
            if (!copy ( $file, "ssh2.sftp://{$resSFTP}/upload/$filename" )) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * sftp下载文件
     * sftp upload
     * @param $file 保存zip 下载文件路径
     * @param $filename 下载文件名称
     * @return FAIL 失败   SUCCESS 成功
     */
    function sftp_download($file,$filename) {
        $start_time=microtime(true);
        $strServer = sinapay_sftp_address;
        $strServerPort = sinapay_sftp_port;
        $strServerUsername = sinapay_sftp_Username;
        $strServerprivatekey = sinapay_sftp_privatekey;
        $strServerpublickey = sinapay_sftp_publickey;
        $resConnection = ssh2_connect ($strServer, $strServerPort );
        if (ssh2_auth_pubkey_file ( $resConnection, $strServerUsername, $strServerpublickey, $strServerprivatekey )) {
            $resSFTP = ssh2_sftp ( $resConnection );
            $opts = array(
                'http'=>array(
                    'method'=>"GET",
                    'timeout'=>60,
                )
            );
            $context = stream_context_create($opts);
            $strData = file_get_contents("ssh2.sftp://{$resSFTP}/upload/busiexport/$filename", false, $context);
            if (! file_put_contents($file.$filename, $strData)) {
                $end_time=microtime(true);//获取程序执行结束的时间
                $total=$end_time-$start_time; //计算差值
//                self::write_log($filename."下载失败，耗时".$total."秒");
                return false;
            }else{
                $end_time=microtime(true);//获取程序执行结束的时间
                $total=$end_time-$start_time; //计算差值
//                self::write_log($filename."下载成功，耗时".$total."秒");
            }
        }
        return true;
    }

    /**
     * @param string $path 需要创建的文件夹目录
     * @return bool true 创建成功 false 创建失败
     */
    function mkFolder($path)
    {
//        self::write_log("开始创建文件夹");
        if (!file_exists($path))
        {
            mkdir($path, 0777,true);
//            self::write_log("文件夹创建成功".$path);
            return true;
        }
//        self::write_log("文件夹创建失败".$path);
        return false;
    }

    /**
     * 获取IP范例，具体以实现代码已自身网络架构来进行编写
     * @return string
     */
    function get_ip(){
        if (isset($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], "unknown")){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], "unknown")){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else if (isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        else if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        else{
            $ip = "";
        }
        return ($ip);
    }


}