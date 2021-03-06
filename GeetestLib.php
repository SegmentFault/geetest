<?php

namespace Geetest;

/**
 * 极验行为式验证安全平台，php 网站主后台包含的库文件
 * Class GeetestLib
 * @package Geetest
 * @author Kumfo<kumfo@qq.com>
 */
class GeetestLib
{
    const GT_SDK_VERSION = 'php_3.0.0';

    public static $connectTimeout = 1;
    public static $socketTimeout = 1;

    private string $captchaId;
    private string $privateKey;

    public function __construct(string $captchaId, string $privateKey)
    {
        $this->captchaId  = $captchaId;
        $this->privateKey = $privateKey;
        $this->domain     = "http://api.geetest.com";
    }

    /**
     * 初始化验证判断极验服务器是否down机
     * @param array $param
     * @param int $newCaptcha
     * @return array
     */
    public function preProcess(array $param, int $newCaptcha = 1)
    {
        $data      = [
            'gt'          => $this->captchaId,
            'new_captcha' => $newCaptcha
        ];
        $data      = array_merge($data, $param);
        $query     = http_build_query($data);
        $url       = $this->domain . "/register.php?" . $query;
        $challenge = $this->sendRequest($url);
        if (strlen($challenge) != 32) {
            return $this->failedProcess();
        }
        return $this->successProcess($challenge);
    }

    /**
     * 正常验证
     * @param string $challenge
     * @return array
     */
    private function successProcess(string $challenge)
    {
        $challenge = md5($challenge . $this->privateKey);
        return [
            'success'     => 1,
            'gt'          => $this->captchaId,
            'challenge'   => $challenge,
            'new_captcha' => 1
        ];
    }

    /**
     * 宕机验证
     * @return array
     */
    private function failedProcess()
    {
        $rnd1      = md5(rand(0, 100));
        $rnd2      = md5(rand(0, 100));
        $challenge = $rnd1 . substr($rnd2, 0, 2);
        return [
            'success'     => 0,
            'gt'          => $this->captchaId,
            'challenge'   => $challenge,
            'new_captcha' => 1
        ];
    }

    /**
     * 正常模式获取验证结果
     * @param string $challenge
     * @param string $validate
     * @param string $seccode
     * @param array $param
     * @param int $json_format
     * @return bool
     */
    public function successValidate(string $challenge, string $validate, string $seccode, array $param, int $json_format = 1)
    {
        if (!$this->checkValidate($challenge, $validate)) {
            return false;
        }
        $query        = [
            "seccode"     => $seccode,
            "timestamp"   => time(),
            "challenge"   => $challenge,
            "captchaid"   => $this->captchaId,
            "json_format" => $json_format,
            "sdk"         => self::GT_SDK_VERSION
        ];
        $query        = array_merge($query, $param);
        $url          = $this->domain . "/validate.php";
        $codeValidate = $this->postRequest($url, $query);
        $obj          = json_decode($codeValidate, true);
        if ($obj === false) {
            return false;
        }
        if ($obj['seccode'] == md5($seccode)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 宕机模式获取验证结果
     * @param string $challenge
     * @param string $validate
     * @param string $seccode
     * @return bool
     */
    public function failedValidate(string $challenge, string $validate, string $seccode)
    {
        if (md5($challenge) == $validate) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $challenge
     * @param string $validate
     * @return bool
     */
    private function checkValidate(string $challenge, string $validate)
    {
        if (strlen($validate) != 32) {
            return false;
        }
        if (md5($this->privateKey . 'geetest' . $challenge) != $validate) {
            return false;
        }

        return true;
    }

    /**
     * GET 请求
     * @param string $url
     * @return bool|int|string
     */
    private function sendRequest(string $url)
    {

        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data       = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
            if ($curl_errno > 0) {
                return 0;
            } else {
                return $data;
            }
        } else {
            $opts    = array(
                'http' => array(
                    'method'  => "GET",
                    'timeout' => self::$connectTimeout + self::$socketTimeout,
                )
            );
            $context = stream_context_create($opts);
            $data    = @file_get_contents($url, false, $context);
            if ($data) {
                return $data;
            } else {
                return 0;
            }
        }
    }

    /**
     * post 请求
     * @param string $url
     * @param array $postdata
     * @return bool|string
     */
    private function postRequest(string $url, array $postdata)
    {
        if (!$postdata) {
            return false;
        }

        $data = http_build_query($postdata);
        if (function_exists('curl_exec')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$socketTimeout);

            //不可能执行到的代码
            if (!$postdata) {
                curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            $data = curl_exec($ch);

            if (curl_errno($ch)) {
                $err = sprintf("curl[%s] error[%s]", $url, curl_errno($ch) . ':' . curl_error($ch));
                $this->triggerError($err);
            }

            curl_close($ch);
        } else {
            if ($postdata) {
                $opts    = array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
                        'content' => $data,
                        'timeout' => self::$connectTimeout + self::$socketTimeout
                    )
                );
                $context = stream_context_create($opts);
                $data    = file_get_contents($url, false, $context);
            }
        }

        return $data;
    }


    /**
     * @param $err
     */
    private function triggerError($err)
    {
        trigger_error($err);
    }
}