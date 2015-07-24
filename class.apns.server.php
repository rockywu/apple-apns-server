<?php
/*
 * apns server 使用php进行服务推送
 *
 * @created by www.rockywu.com 2015-07-23
 * @author rockywu wjl19890427@hotmail.com
 */
class apns_server
{

    private $timeout = 60;

    private $handle_log = null;

    private $max_length = 30;

    private $count = 0;

    private $handle = null;

    private $category = "";

    private $certificate = null;

    private $passphrase = null; //pushchat

    private $sandbox_configure = array(
        'feedback' => 'ssl://feedback.sandbox.push.apple.com:2196',
        'gateway' => 'ssl://gateway.sandbox.push.apple.com:2195',
    );

    private $production_configure = array(
        'feedback' => 'ssl://feedback.push.apple.com:2196',
        'gateway' => 'ssl://gateway.push.apple.com:2195',
    );

    private $configure = array();

    private $logger = null;

    private $callback = "message";

    private $invalid_tokens = array();

    private $new_interface = false;

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
     */
    public function __construct($environment, $category, $certificate, $passphrase, $logger=null, $callback="") {
        if($environment != "sandbox" && $environment != "production") {
            die("apple apns : environment is error");
        }
        if($category != 'gateway' &&$category != 'feedback') {
            die("apple apns : category is error");
        }
        // 设置环境
        $this-> set_environment($environment);
        // 设置服务类型
        $this-> category = $category;
        // 设置日志生成器
        $this-> set_logger($logger, $callback);
        //设置签名密钥
        $this-> set_passphrase($passphrase);
        //设置签名文件
        $this-> set_certificate($certificate);
        //创建链接
        $this-> handle_create();
    }

    public function use_new_interface($is_user = false) {
        if($is_user) {
            $this-> new_interface = true;
        } else {
            $this-> new_interface = false;
        }
    }

    public function set_max_length($length) {
        $length > 0 ? $this-> length : '';
    }

    public function log($message) {
        $message = $message . PHP_EOL;
        if($this-> logger) {
            call_user_method_array($this-> callback, $this-> logger, array($message));
        } else {
            $this-> message($message);
        }
    }

    public function message($message) {
        printf("Anjuke APNS -- %s", $message);
    }

    public function set_logger($logger, $callback) {
        if(method_exists($logger, $callback)) {
            $this-> logger = $logger;
            $this-> callback = $callback;
        }
    }

    public function set_environment($type="sandbox") {
        $this-> configure = $type == 'sandbox' ? $this-> sandbox_configure : $this-> production_configure;
    }

    private function set_passphrase($passphrase) {
        if($passphrase && is_string($passphrase)) {
            $this-> passphrase = $passphrase;
        } else {
            die("apple apns : passphrase is not exist");
        }
    }

    private function set_certificate($certificate) {
        if(is_readable($certificate)) {
            $this-> certificate = $certificate;
        } else {
            die("apple apns : certificate is not exist");
        }
    }

    private function handle_create() {
        $this-> handle_close();
        $this-> log("handle create");
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this-> certificate);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this-> passphrase);
        if($this-> category == 'feedback') {
            stream_context_set_option($ctx, 'ssl', 'verify_peer', false);
            $this-> log("create feedback handle");
        } else {
            $this-> log("create gateway handle");
        }
        $this-> handle = stream_socket_client(
            $this-> configure[$this->category],
            $error,
            $errorString,
            $this-> timeout,
            (STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT),
            $ctx
        );
        if(!$this-> handle) {
            $this->log("handle is error");
        }
    }

    private function handle_close() {
        if($this-> handle) {
            $this-> log("handle close");
            fclose($this-> handle);
        } else {
            $this-> handle = null;
        }
    }

    /*
     * 发送推送消息
     *
     * @param string $token
     * @param string $message
     * @param string $mark //标签
     * @return boolean
     */
    public function send_notification($token, $message, $mark = "markId") {
        if(is_array($message)) {
            $this-> log("\$message is array change to string");
            $message = json_encode($message);
        }
        if(!is_string($message)) {
            $this-> log("\$message is not string");
            return false;
        }
        if($this-> new_interface) {
            $expiry = time() + 120;
            $msg = chr(1).pack("N", $mark).pack("N",$expiry).pack("n",32).pack('H*',$token).pack("n",strlen($message)).$message;
        } else {
            $msg = chr(0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($message)) . $message;
        }
        if($this-> count > $this-> max_length) {
            $this-> count = 0;
            $this-> log("handle reopen");
            $this-> handle_create();
        }
        $result = fwrite($this-> handle, $msg, strlen($msg));
        $this-> count++;
        $this-> log("send : $result | interface is" . ($this-> new_interface ? 'V 2.0' : 'V 1.0'));
        return $result ? true : false;
    }

    public function get_invalid_tokens() {
        while ($devcon = fread($this-> handle, 38)){
            $arr = unpack("H*", $devcon);
            $rawhex = trim(implode("", $arr));
            $token = substr($rawhex, 12, 64);
            if(!empty($token)){
                $this-> invalid_tokens[] = $token;
            }
        }
        $count = count($this-> invalid_tokens);
        if($count) {
            $this-> log("count : $count | tokens : " . json_encode($this-> invalid_tokens));
        }
        return $this-> invalid_tokens;
    }
}
