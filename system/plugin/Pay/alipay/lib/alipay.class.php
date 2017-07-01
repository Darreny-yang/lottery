<?php 

include dirname(__FILE__).DIRECTORY_SEPARATOR."alipay_submit.class.php";
class alipay {
    
    private $config;
    private $url;
    public function __construct(){          
        $this->db=System::load_sys_class('model');      
    }   
    //主入口    
    
    public function config($config=null){
        $pay_type =$this->db->GetOne("SELECT * from `@#_payment` where `pay_class` = '$config[pay_class]' and `pay_start` = '1'");
        $config['pay_uid']=$pay_type['pay_uid'];
        $config['pay_account']=$pay_type['pay_account'];
        $config['pay_key']=$pay_type['pay_key'];
        $config['pay_type']=$pay_type['pay_type'];
        $payreturn1=array();$payreturn2=array();        
        $payreturn1['pay_class']=$pay_type['pay_class'];
        $payreturn1['pay_fun']="qiantai";       
        $payreturn1=base64_encode(json_encode($payreturn1));  
                             
        $payreturn2['pay_class']=$pay_type['pay_class'];
        $payreturn2['pay_fun']="houtai";    
        $payreturn2=base64_encode(json_encode($payreturn2)); 
        $config['pay_ReturnUrl']= G_WEB_PATH.'/index.php/plugin-Pay-return-alipayReturnUrl?';
        $config['pay_NotifyUrl']=G_WEB_PATH.'/index.php/plugin-Pay-return-alipayNotifyUrl?';            
        // $config['pay_ReturnUrl']= G_WEB_PATH.'/index.php/?plugin=true&api=Pay&action=return&data='.$payreturn1;
        // $config['pay_NotifyUrl']=G_WEB_PATH.'/index.php/?plugin=true&api=Pay&action=return&data='.$payreturn2;       
    
        $this->config = $config;
        if($config['pay_type'] == 1){
            $this->config_jsdz();
        }
        if($config['pay_type'] == 2){
            $this->config_dbjy();
        }
        
    }
    
    //即时到账
    private function config_jsdz(){
        $config = $this->config;
        $payment_type = "1";
         //服务器异步通知页面路径
        $notify_url = $config['pay_NotifyUrl'];
        //页面跳转同步通知页面路径
        $return_url = $config['pay_ReturnUrl'];     
        //卖家支付宝帐户 必填
        $seller_email = $config['pay_account'];
        //商户订单号 必填
        $out_trade_no = $config['pay_code'];
        //订单名称 必填
        $subject = $config['pay_title'];
        //付款金额 必填
        $total_fee = $config['pay_money'];
        //$total_fee = 0.01;
        //自定义订单类型参数  cloud_order  一元夺宝商品
        $order_type = $config['order_type'];
        //订单描述
        $body = '';
        //商品展示地址
        $show_url = '';
        //需以http://开头的完整路径，例如：http://www.xxx.com/myorder.html
        //防钓鱼时间戳
        $anti_phishing_key = "";
        //若要使用请调用类文件submit中的query_timestamp函数
        //客户端的IP地址
        $exter_invoke_ip = "";
        //非局域网的外网IP地址，如：221.0.0.1       
    

    
        $alipay_config_id = $config['pay_uid'];                                     //合作身份者id，以2088开头的16位纯数字                                  //合作身份者id，以2088开头的16位纯数字
        $alipay_config_key = $config['pay_key'];                                    //安全检验码，以数字和字母组成的32位字符
        $alipay_config_input_charset = strtolower('utf-8');
        
        //构造要请求的参数数组，无需改动
        $parameter = array(
                "service" => "create_direct_pay_by_user",
                "partner" => $alipay_config_id,
                "payment_type"  => $payment_type,
                "notify_url"    => $notify_url,
                "return_url"    => $return_url,
                "seller_email"  => $seller_email,
                "out_trade_no"  => $out_trade_no,
                "subject"   => $subject,
                "total_fee" => $total_fee,
                "body"  => $body,
                "show_url"  => $show_url,
                "extra_common_param" => $order_type,
                "anti_phishing_key" => $anti_phishing_key,
                "exter_invoke_ip"   => $exter_invoke_ip,
                "_input_charset"    => $alipay_config_input_charset
        );
        
        //签名方式 不需修改
        $alipay_config_sign_type = strtoupper('MD5');       
        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config_input_charset = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        //$alipay_config_cacert    = getcwd().'\\cacert.pem';   
        $alipay_config_cacert =  dirname(__FILE__).DIRECTORY_SEPARATOR."cacert.pem";        
        $alipay_config_transport   = 'http';
        
        $alipay_config=array(
            "partner"      =>$alipay_config_id,
            "key"          =>$alipay_config_key,
            "sign_type"    =>$alipay_config_sign_type,
            "input_charset"=>$alipay_config_input_charset,
            "cacert"       =>$alipay_config_cacert,
            "transport"    =>$alipay_config_transport
        );

        $alipaySubmit = new AlipaySubmit($alipay_config);
        $this->url = $alipaySubmit->buildRequestForm($parameter,'POST','submit');
        
    }
    
    //担保交易
    private function config_dbjy(){
        
        $config = $this->config;
        $payment_type = "1"; //支付类型 必填，不能修改
        $notify_url = $config['pay_NotifyUrl'];
        $return_url = $config['pay_ReturnUrl'];
        $seller_email = $config['pay_account']; //卖家支付宝帐户 必填
        $out_trade_no = $config['pay_code']; //商户订单号 必填
        $subject = $config['pay_title']; //订单名称 必填  
        $price = $config['pay_money']; //付款金额 必填

        $quantity = "1";    //商品数量 必填，建议默认为1，不改变值，把一次交易看成是一次下订单而非购买一件商品
        $logistics_fee = "0.00";   //物流费用 必填，即运费  
        $logistics_type = "EXPRESS";  //物流类型 必填，三个值可选：EXPRESS（快递）、POST（平邮）、EMS（EMS）
    
        //物流支付方式 必填，两个值可选：SELLER_PAY（卖家承担运费）、BUYER_PAY（买家承担运费）
        $logistics_payment = "SELLER_PAY";
        
        $body = '';   //订单描述   
        $show_url = ''; //商品展示地址

        $receive_name = ''; //收货人姓名
        $receive_address = ''; //收货人地址
        $receive_zip = '';  //收货人邮编 
        $receive_phone = ''; //收货人电话号码
        $receive_mobile = ''; //收货人手机号码
        
        //签名方式 不需修改
        $alipay_config_sign_type = strtoupper('MD5');       
        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config_input_charset = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        //$alipay_config_cacert    = getcwd().'\\cacert.pem';   
        $alipay_config_cacert =  dirname(__FILE__).DIRECTORY_SEPARATOR."alipay".DIRECTORY_SEPARATOR."cacert.pem";       
        $alipay_config_transport   = 'http';
        
        $alipay_config_id = $config['pay_uid'];                                     //合作身份者id，以2088开头的16位纯数字
        $alipay_config_key = $config['pay_key'];                                    //安全检验码，以数字和字母组成的32位字符
        $alipay_config_input_charset = strtolower('utf-8');
        
        
        $alipay_config=array(
            "partner"      =>$alipay_config_id,
            "key"          =>$alipay_config_key,
            "sign_type"    =>$alipay_config_sign_type,
            "input_charset"=>$alipay_config_input_charset,
            "cacert"       =>$alipay_config_cacert,
            "transport"    =>$alipay_config_transport
        );
        

        //构造要请求的参数数组，无需改动
        $parameter = array(
                    "service" => "create_partner_trade_by_buyer",
                    "partner" => $alipay_config_id,
                    "payment_type"  => $payment_type,
                    "notify_url"    => $notify_url,
                    "return_url"    => $return_url,
                    "seller_email"  => $seller_email,
                    "out_trade_no"  => $out_trade_no,
                    "subject"   => $subject,
                    "price" => $price,
                    "quantity"  => $quantity,
                    "logistics_fee" => $logistics_fee,
                    "logistics_type"    => $logistics_type,
                    "logistics_payment" => $logistics_payment,
                    "body"  => $body,
                    "show_url"  => $show_url,
                    "receive_name"  => $receive_name,
                    "receive_address"   => $receive_address,
                    "receive_zip"   => $receive_zip,
                    "receive_phone" => $receive_phone,
                    "receive_mobile"    => $receive_mobile,
                    "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
        );
        $alipaySubmit = new AlipaySubmit($alipay_config);
        
        //$this->url =  urldecode($alipaySubmit->buildRequestGet($parameter));      
        $this->url =  $alipaySubmit->buildRequestForm($parameter,'POST','submit');
        
            
    }
    
    //发送
    public function send_pay(){
         echo  $this->url;
         exit;
        //header("Location: $url"); 
    }
}

?>
