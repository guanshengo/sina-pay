<h1 align="center"> sina-pay </h1>

<p align="center"> 新浪支付组件。</p>


## 安装

```shell
$ composer require guanshengo/sina-pay -vvv
```

## 配置
```
$config = [
    'sinapay_file_path'=>"../files/",
    'sinapay_rsa_sign_private_key'=>dirname(__File__) . "/../key/rsa_sign_private.pem",
    "sinapay_rsa_sign_public_key"=>dirname(__File__) . "/../key/rsa_sign_public.pem",
    "sinapay_rsa_public__key"=>dirname(__File__) . "/../key/rsa_public.pem",
    "sinapay_debug_status"=>true,
    "sinapay_sftp_address"=>"222.73.39.37",
    "sinapay_sftp_port"=> "50022",
    "sinapay_sftp_Username"=>"200009166773",
    "sinapay_sftp_privatekey"=>dirname(__File__) . "/../key/id_rsa",
    "sinapay_sftp_publickey" => dirname(__File__) . "/../key/id_rsa.pub",
    "sinapay_sftp_upload_directory"=>dirname(__File__) . "/upload"
];
```

在使用本扩展之前，请熟悉<a href="http://bk.zjtghelp.com/"> 新浪钱包 </a>说明文档


## 使用

```
use Guanshengo\SinaPay\SinaPay;

public function index()
{
    $config = [
    ];

    $sinapay = new SinaPay($config);

    $sinapay->setParams($param)->send();

}

public function index()
{
    $config = [
    ];

    $sinapay = new SinaPay($config);

    try{
        $data = $sinapay->verify(); // 验签
    
        // 请自行对 notify_type 进行判断及其它逻辑进行判断
        // 其它业务逻辑情况
    
        Log::debug('notify', $data);
        return $sinapay->success()->send();// laravel 框架中请直接 `return $sinapay->success()`
    } catch (\Exception $e) {
        // $e->getMessage();
    }

}

```

## 代码贡献

+ [PHP 扩展包实战教程 - 从入门到发布](https://laravel-china.org/courses/creating-package?rf=23775)

## License

MIT