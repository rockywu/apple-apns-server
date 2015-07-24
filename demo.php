<?php
include_once("class.apns.server.php");
// notification 字段是自定义数据内容
$body['aps'] = array(
    'alert' => "测试消息",
    'sound' => 'default',
    'notification' => array(
        'self1' => 'myself-notification',
        'self2' => 'myself-notification',
        'self3' => 'myself-notification',
        'self4' => 'myself-notification',
        'self5' => 'myself-notification',
    ),
);

/*
 * 每推送30条记录将重新建立apns连接，避免出现连接超时关闭情况
 */
//新建推送服务
$send_server  = new apns_server("production", "gateway", "my-production.pem", 'passphrase');
$user_token = "aaaa1111bbbb2222cccc3333dddd4444eeee5555";
$mark_id = 1001; 
//模式使用V 1.0协议 ，若使用V 2.0
//$send_server->use_new_interface(true);
$send_server->send_notification($user_token, $body, $markid);

//新建返回服务
$feedback_server  = new apns_server("production", "feedback", "my-production.pem", 'passphrase');
$tokens = $feedback_server->get_invaild_tokens();
print_r($tokens);
