<?php

function send_mail() {
        $url = 'http://api.sendcloud.net/apiv2/mail/sendtemplate';

        $vars = json_encode( array("to" => array('test@ifaxin.com'),
                                   "sub" => array("%code%" => Array('123456'))
                                   )
                );

        $API_USER = '...';
        $API_KEY = '...';
        $param = array(
            'apiUser' => $API_USER, # 使用api_user和api_key进行验证
            'apiKey' => $API_KEY,
            'from' => 'sendcloud@sendcloud.org', # 发信人，用正确邮件地址替代
            'fromName' => 'SendCloud',
            'xsmtpapi' => $vars,
            'templateInvokeName' => 'test12346',
            'subject' => 'Sendcloud php webapi template example',
            'respEmailId' => 'true'
        );
        

        $data = http_build_query($param);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
        ));
        $context  = stream_context_create($options);
        $result = file_get_contents($url, FILE_TEXT, $context);

        return $result;
}

echo send_mail();
?>
