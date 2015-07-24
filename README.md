### APNS Server (php版本的推送服务)
#### 兼容协议

#### 使用方式

__ 创建服务对象 __

    /**
     * 构造函数 __construct
     * @param string $environment  //开发环境 ： production or sandbox
     *    生产环境(production) or 沙盒环境(sandbox)
     * @param string $category  // 接口类型 ： gateway or feedback
     *    推送接口(gateway) or 反馈接口(feedback)
     * @param string $certificate  //证书签名 (文件所在地址)
     * @param string $passphrase   //签名密钥 
     * @param object $logger       //输出日志对象(可选)
     * @param string $callback     //输出日志回调方法(可选)
     * @author rockywu
     **/
     
     //推送接口
     $server = new apns_server($environment, $category, $certificate, $passphrase, $logger=null, $callback="");
     

__ 默认兼容协议V 1.0 , 若要开启 V 2.0 __
    
    $server->use_new_interface(true);

__ 发送推送消息 __

    /**
     * 发送推送消息
     *
     * @param string $token  //用户推送token
     * @param string/array $message // 推送消息
     * @param string $mark //标签(只在V2.0有效)
     * @return boolean
     **/
    $server->send_notification($token, $message, $mark)

__ 获取无效token __
    
    /**
     * 返回无效token数组
     * @return array
     **/
    $server->get_invaild_tokens();
    
__ Author : Rockywu __

__ Email  : wjl19890427@hotmail.com__


