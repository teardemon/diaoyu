<?php
class BaseModelMailer {

    const IS_SENDMAIL = '0';
    const IS_SMTP = '1';
    const IS_MAIL = '2';

    private $mailer;

    /**
     * 构造函数
     * @param int $type 发送邮件方式
     */
    public function __construct($type=BaseModelMailer::IS_SENDMAIL){
        $this->mailer = new PHPMailer($exception=true);
        $this->mailer->CharSet = 'utf-8';
        switch ($type) {
            case BaseModelMailer::IS_SENDMAIL:
                $this->mailer->IsSendMail();
                break;
            case BaseModelMailer::IS_SMTP:
                $this->mailer->IsSMTP(); 
                $this->setMailServer('smtp.sina.com', 'web_monitor@sina.com', 'Ld24Tj@ldn5L3s');
                $this->mailer->SMTPAuth = true;
                if (defined(DAGGER_DEBUG)) {
                    $this->mailer->SMTPDebug = 2;
                }
                break;
            case IS_MAIL:
                break;
        }
            
        $this->mailer->SetFrom('web_monitor@sina.com', 'web_monitor');
        $this->mailer->Subject = 'web monitor report';
    }
    
    /**
     * 调用其它phpmailer函数
     */
    public function __call($func, $args)
    {
        return call_user_func_array(array($this->mailer, $func), $args);
    }

    /**
     * 设定邮件标题
     * @param string $subject 邮件标题
     */
    public function setSubject($subject)
    {
        $this->mailer->Subject = '=?'.$this->mailer->CharSet.'?B?'.base64_encode($subject).'?=';
    }

    /**
     * 设定邮件字符编码
     * @param string $charset 字符编码
     */
    public function setCharset($charset='utf-8')
    {
        $this->mailer->CharSet = $charset;
        $this->mailer->Encoding = 'base64';
    }

    /**
     * 收件人仅显示收件人自己
     */
    public function setSingleTo() 
    {
        $this->mailer->SingleTo = true;
    }

    /**
     * 设定SMTP服务器
     * @param string $host SMTP服务器地址
     * @param string $user 用户名
     * @param string $pwd  密码
     * @param string $port 发送端口
     */
    public function setMailServer($host, $user, $pwd, $port=25)
    {
        $this->mailer->Host = $host;
        $this->mailer->Username = $user;
        $this->mailer->Password = $pwd;
        $this->mailer->Port = $port;
    }

    /**
     * 设定发送者
     * @param string $fromMail 发件人地址
     * @param string $fromName 发件人显示名
     */
    public function setFrom($formMail, $fromName)
    {
        $this->mailer->SetFrom($formMail, $fromName);
    }

    /**
     * 发送函数
     */
    public function Send()
    {
        $error = 0;
        do {
            try {
                //edit by jinwen Sep. 14th,2012
                return $this->mailer->Send();
            } catch (Exception $e) {
                throw new BaseModelException($e->getMessage(), 90800, 'mail_trace');
                sleep(5);
            }
        } while (++$error <= 3);
    }
    
    /* 
     * 本函数基于动态池的邮件发送接口
     * $param string $addresses 发送地址，支持单个email('xx@sina.com.cn')，支持多个email(array('111@sina.com.cn','222'@sina.com.cn))
     * $param string $subject 邮件主题
     * $param string $content 邮件内容
     * $param array $options 其他参数，抄送等
     * return mix success return true and false return error code
     */
    public static function sysMail($addresses = '', $subject = "", $content = '', $options = array()) {
        // 环境变量设置
        $_SERVER['SINASRV_DPMAIL_TIMEOUT'] = 3;
        $_SERVER['SINASRV_DPMAIL_HOST'] = '10.44.6.21';
        $_SERVER['SINASRV_DPMAIL_URL'] = 'http://10.44.6.21/mailservice/api.php';
        $_SERVER['SERVER_NAME'] = 'topic.t.sina.com.cn';

        if(!class_exists('Mail', false)) {
            include 'DPUtils/Mail.php';
        }
        $mail = new Mail();
        
        $from = array('web_monitor@sina.com', 'web_monitor');
        /* 
        $subject = 'test';
        $content = 'test';
        $addresses = array(
            array('xuyan4@staff.sina.com.cn')
        );
        $options  = array('cc' => array(array('xuyan4@staff.sina.com.cn', 'xu yan'),),);
         */
        if(is_array($addresses)) {
            foreach($addresses as $k => $address) {
                    $recipients[$k] = (array)$address;
            }
        } else {
            $recipients = array((array)$addresses);
        }
        $result = $mail->send($from, $recipients, $subject, $content, $options);
        if($result === false) {
            $errno = $mail->errno();
            BaseModelCommon::debug($errno, 'mail_error');
            return $errno;
        } else {
            return true;
        }
    }
}