<?php

namespace Guanshengo\SinaPay;

use Guanshengo\SinaPay\Contracts\GatewayInterface;
use Guanshengo\SinaPay\Exceptions\Exception;
use Guanshengo\SinaPay\Exceptions\FtpException;
use Guanshengo\SinaPay\Exceptions\HttpException;
use Guanshengo\SinaPay\Exceptions\InvalidArgumentException;
use Guanshengo\SinaPay\Exceptions\InvalidSignException;
use Guanshengo\SinaPay\Support\Config;
use Guanshengo\SinaPay\Support\Log;
use Guanshengo\SinaPay\Traits\HasHttpRequest;
use Guanshengo\SinaPay\Traits\HasTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SinaPay implements GatewayInterface
{
    use HasHttpRequest,HasTool;

    protected $config;

    protected $mustConfig = [
        'sinapay_rsa_sign_private_key',
        "sinapay_rsa_sign_public_key",
        "sinapay_rsa_public__key",
        "sinapay_debug_status",
    ];
    protected $ftpConfig = [
        "sinapay_sftp_address",
        'sinapay_file_path',
        "sinapay_sftp_port",
        "sinapay_sftp_Username",
        "sinapay_sftp_privatekey",
        "sinapay_sftp_publickey",
        "sinapay_sftp_upload_directory"
    ];
    protected $url_mgs  = 'https://testgate.pay.sina.com.cn/mgs/gateway.do'; //会员类网关地址
    protected $url_mas  = 'https://testgate.pay.sina.com.cn/mas/gateway.do'; //订单类网关地址
    /**
     * 基本参数
     * @var null[]
     */
    protected $baseParams = [
        "service" => null,//接口名称
        "version" => '1.2',//接口版本
//        'sign_version'=>1.2,
        "request_time" => null,//发起请求时间，格式yyyyMMddHHmmss
        "partner_id" => null,//签约合作方的钱包唯一用户号
        "_input_charset" => 'utf-8',//商户网站使用的编码格式，utf-8
        "sign_type" => 'RSA',//签名方式支持RSA
        "notify_url" => null,//钱包处理发生状态变迁后异步通知结果
        "return_url" => null,//钱包处理完请求后，当前页面自动跳转到商户网站里指定页面的http路径
        "memo" => null,//说明信息
        "cashdesk_addr_category" => null,//收银台地址类型，目前只包含MOBILE
        "sign" => null,
    ];

    /**
     * 基础业务参数
     * @var array
     */
    protected $params = [];
    /**
     * 传输的数据
     * @var array
     */
    protected $paramsData = null;
    /**
     * 返回需要加密的参数名的集合
     * @var array|string[]
     */
    protected $needRSA = ["real_name", "cert_no", "verify_entity","bank_account_no","account_name",
        "phone_no","validity_period","verification_value","telephone","email","organization_no",
        "legal_person","legal_person_phone","agent_name","license_no","agent_mobile"];
    //交易类网关服务
    protected $trade_Interface_service = array(
        "create_b2c_order"=>"交易创建",
        "pay_order"=>"再次支付",
        "advance_hosting_pay"=>"付款推进（快捷支付验证码推进）",
        "settle_b2c_order"=>"交易结算",
        "close_b2c_order"=>"交易关闭",
        "cancel_b2c_order"=>"交易撤销",
        "extend_settle_time"=>"延长交易结算时间",
        "query_pay_result"=>"支付结果查询",
        "query_hosting_trade"=>"交易查询",
        "create_hosting_refund"=>"退款",
        "query_hosting_refund"=>"退款查询",
        "create_batch_pay2bank"=>"批量付款到银行卡",
        "query_b2c_batch_fundout_order"=>"出款批次查询",
    );

    /**
     * SinaPay constructor.
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        $this->verifyBaseConfig($config);
        $this->config = new Config($config);
    }

    /**
     * 验证配置数据
     * @param array $config
     * @throws InvalidArgumentException
     */
    protected function verifyBaseConfig(array $config){
        if(count($config) <= 0){
            throw new InvalidArgumentException('config does not exist');
        }else{
            foreach ($this->mustConfig as $key){
                if(array_key_exists($key,$config))throw new InvalidArgumentException('config ['.$key.'] does not exist');
                if(empty($config[$key]))throw new InvalidArgumentException('config ['.$key.'] is null');
            }
        }
    }

    /**
     * 验证FTP配置数据
     * @param array $config
     * @throws InvalidArgumentException
     */
    protected function verifyFtpConfig(array $config){
        if(count($config) <= 0){
            throw new InvalidArgumentException('config does not exist');
        }else{
            foreach ($this->ftpConfig as $key){
                if(array_key_exists($key,$config))throw new InvalidArgumentException('config ['.$key.'] does not exist');
                if(empty($config[$key]))throw new InvalidArgumentException('config ['.$key.'] is null');
            }
        }
    }
    /**
     * 设置传输参数
     * @param array $params
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setParams(array $params = array()){
        //基础参数合并
        $this->baseParams["request_time"] = date("YmdHis");
        $params = array_merge($this->baseParams,$params);
        //进行空值剔除处理
        $sParaTemp = array();
        foreach($params as $k => $v){
            if( ($v !=null) || ($v != '')){
                $sParaTemp[$k]=$v;
            }
        }
        ksort($sParaTemp);
        if(!array_key_exists('service',$sParaTemp) || empty($sParaTemp['service'])){
            throw new InvalidArgumentException('param [service] does not exist');
        }
        if(!array_key_exists('version',$sParaTemp)){
            throw new InvalidArgumentException('param [version] does not exist');
        }
        if(!array_key_exists('partner_id',$sParaTemp) || empty($sParaTemp['partner_id'])){
            throw new InvalidArgumentException('param [partner_id] does not exist');
        }

        $sParaTemp = $this->Encryption_sParaTemp($sParaTemp);
        //计算签名值
        $sParaTemp['sign'] = $this->getSignMsg($sParaTemp, $sParaTemp['sign_type'],$sParaTemp['_input_charset']);
        $this->params = $sParaTemp;
        $this->paramsData = $this->createcurl_data($sParaTemp);

        return $this;
    }

    /**
     * 发送请求
     * @return array|string
     * @throws HttpException
     */
    public function send(){
        if(array_key_exists($this->params['service'],$this->trade_Interface_service)){
            $url =  "https://testgate.pay.sina.com.cn/mas/gateway.do";
        }else{
            $url =  "https://testgate.pay.sina.com.cn/mgs/gateway.do";
        }
        try{
            $result = $this->post($url,$this->paramsData,[
                'headers'=>['Content-Type'=>'application/x-www-form-urlencoded;']
            ]);
            $splitdata = json_decode($result, true);
            //对返回数据进行排序
            ksort($splitdata);
            if($this->checkSignMsg($splitdata, $splitdata['sign_type'], $splitdata['_input_charset'])){
                //验签成功。可信任数据
                return $result;
            }else{
                //验签失败，请检查报文
                return $result;
            }
        }catch (Exception $exception){
            Log::error("新浪支付错误:".$exception->getMessage().',文件:'.$exception->getFile().',行数:'.$exception->getLine());
            throw new HttpException($exception->getMessage());
        }
    }

    /**
     * 验证回调数据
     * @param null $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public function verify($data = null){
        if (is_null($data)) {
            $request = Request::createFromGlobals();

            $data = $request->request->count() > 0 ? $request->request->all() : $request->query->all();
        }
        ksort($data);
        if(empty($data['sign']))throw new InvalidArgumentException("sign is null");
        if ($this->checkSignMsg($data, $data["sign_type"],$data["_input_charset"])) {
            if(!in_array($data["notify_type"],[
                'trade_status_sync','refund_status_sync','deposit_status_sync','withdraw_status_sync','batch_trade_status_sync',
                'audit_status_sync','bid_status_sync','mig_set_pay_password','mig_binding_card','mig_unbind_card','mig_apply_withhold',
                'mig_apply_withhold','mig_apply_withhold','mig_cancel_withhold','mig_change_card'
            ])) {
                throw new InvalidSignException("notify_type error");
            }
            return $data;
        } else {
            throw new InvalidSignException("sign error or illegal request");
        }
    }

    /**
     * Reply success
     */
    public function success(): Response
    {
        return new Response('success');
    }
    /**
     * sftp上传
     * @param string $file   本地文件
     * @param string $filename  远程文件名
     * @return bool
     * @throws FtpException
     * @throws InvalidArgumentException
     */
    public function sftpUpload(string $file, string $filename){
        $this->verifyFtpConfig();
        Log::info("ssh2_connect status:".print_r(get_extension_funcs("ssh2_connect")));
        $resConnection = ssh2_connect($this->config['sinapay_sftp_address'], $this->config['sinapay_sftp_port']);

        $res = ssh2_auth_pubkey_file($resConnection, $this->config['sinapay_sftp_username'], $this->config['sinapay_sftp_publickey'], $this->config['sinapay_sftp_privatekey']);
        if (!$res) {
            throw new FtpException("ftp connection fail");
        }

        $resSFTP = ssh2_sftp($resConnection);

        if (!copy($file, "ssh2.sftp://{$resSFTP}/upload/$filename")) {
            return false;
        }
    }

    /**
     * sftp下载
     * @author 王崇全
     * @param string $path     下载文件路径
     * @param string $filename 下载文件名称
     * @return bool
     * @throws FtpException
     * @throws InvalidArgumentException
     */
    public function sftpDownload(string $path, string $filename) {
        $this->verifyFtpConfig();
        Log::info("ssh2_connect status:".print_r(get_extension_funcs("ssh2_connect")));

        $resConnection = ssh2_connect($this->config['sinapay_sftp_address'], $this->config['sinapay_sftp_port']);

        if(!ssh2_auth_pubkey_file($resConnection, $this->config['sinapay_sftp_username'], $this->config['sinapay_sftp_publickey'], $this->config['sinapay_sftp_privatekey'])) {
            throw new FtpException("ftp connection fail");
        }

        $start_time = microtime(true);
        $resSFTP = ssh2_sftp($resConnection);
        $opts    = [
            'http' => [
                'method'  => "GET",
                'timeout' => 60,
            ],
        ];
        $context = stream_context_create($opts);
        $strData = file_get_contents("ssh2.sftp://{$resSFTP}/upload/busiexport/$filename", false, $context);
        $end_time = microtime(true);//获取程序执行结束的时间
        $total    = $end_time - $start_time; //计算差值
        if (!file_put_contents($path.$filename, $strData)) {
            Log::info($filename."下载失败，耗时".$total."秒");
            return false;
        }
        Log::info($filename."下载成功，耗时".$total."秒");
        return true;
    }

}