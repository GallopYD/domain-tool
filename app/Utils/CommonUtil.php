<?php

namespace App\Utils;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CommonUtil
{

    /**
     * 解析请求参数
     * @param string|array|object $args 参数结构
     * @param string $defaults 默认值结构
     * @return array
     */
    public static function parseArgs($args, $defaults = '')
    {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } else if (is_array($args)) {
            $r =& $args;
        } else {
            $r = array();
            static::parseStr($args, $r);
        }

        if (is_array($defaults)) {
            $r = array_merge($defaults, $r);
        }
        return $r;
    }

    /**
     * 解析请求串，引用型返回
     * @param $string string 请求口串
     * @param $array array 引用型返回数组
     */
    public static function parseStr($string, &$array)
    {
        parse_str($string, $array);
    }

    /**
     * 获取 true/false字符串
     * @param $bool
     * @return string
     */
    public static function getBooleanStr($bool)
    {
        return $bool ? 'true' : 'false';
    }


    /**
     * @param Collection $collection
     * @return bool|string
     */
    public static function toString(Collection $collection)
    {
        $string = null;
        foreach ($collection as $coll) {
            $str = $coll->type . '=' . $coll->account . "&";
            $string = $string . $str;
        }
        return substr($string, 0, strlen($string) - 1);
    }

    /**
     * 获取当前环境的ip地址
     * @return array|false|string
     */
    public static function getIp()
    {
        $ip = '';
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (strpos($ip, ',') !== false) {
            $ip = substr($ip, 0, strpos($ip, ','));
        }
        return $ip;
    }


    public static function uuid($userCode)
    {
        return date('YmdHi') . sprintf("%06d", $userCode) . rand(1000, 9999);
    }

    /**
     * 生成TXT验证信息
     * @param $user_id
     * @return array
     */
    public static function genTxtVerify($user_id)
    {
        $str = md5($user_id . time());
        $head = str_shuffle(substr($str, 0, 10));
        $value = str_shuffle(substr($str, 0, 30));
        return compact('head', 'value');
    }

    /**
     * 分割字符串
     * @param $string
     * @param int $splitLength
     * @param string $charset
     * @return array|bool
     */
    public static function mbSplit($string, $splitLength = 1, $charset = "utf-8")
    {
        if ($splitLength < 1)
            return false;
        $length = mb_strlen($string, $charset);
        $array = array();
        for ($i = 0; $i < $length; $i += $splitLength) {
            $array[] = mb_substr($string, $i, $splitLength, $charset);
        }
        return $array;
    }

    /**
     * 判断是否是正确的邮箱格式
     * @param $email
     * @return bool
     */
    public static function isEmail($email)
    {
        $mode = '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/';
        if (preg_match($mode, $email)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 二维数组去重
     * @param $array2D array 二维数组
     * @return array
     */
    public static function array_unique_fb($array2D)
    {
        $unique = $array_keys = [];
        foreach ($array2D as $k => $v) {
            //每个数组键值（外部域名有的格式不一致）
            $array_keys[$k] = array_keys($v);
            //降维
            $v = implode(',', $v);
            $temp[$k] = $v;
        }
        //去掉重复的字符串
        $temp = array_unique($temp);
        foreach ($temp as $k => $v) {
            $array = explode(',', $v);
            //按照键值重新拼装
            foreach ($array_keys[$k] as $key => $val) {
                $data[$val] = $array[$key];
            }
            array_push($unique, $data);
        }
        return $unique;
    }

    /**
     * 检查url是否带http/https，不带则补上
     * @param $url
     * @return string
     */
    public static function checkUrlHttp($url)
    {
        if (strpos($url, 'http') === false && strpos($url, 'https') === false) {
            $url = 'http://' . $url;
        }
        return $url;
    }


    /**
     * Execute a command and return it's output. Either wait until the command exits or the timeout has expired.
     *
     * @param string $cmd Command to execute.
     * @param number $timeout Timeout in seconds.
     * @return bool|string Output of the command.
     * @throws \Exception
     */
    public static function exec_timeout($cmd, $timeout)
    {
        try {
            // File descriptors passed to the process.
            $descriptors = array(
                0 => array('pipe', 'r'),  // stdin
                1 => array('pipe', 'w'),  // stdout
                2 => array('pipe', 'w')   // stderr
            );
            // Start the process.
            $process = proc_open('exec ' . $cmd, $descriptors, $pipes);
            if (!is_resource($process)) {
                throw new \Exception('Could not execute process');
            }
            // Set the stdout stream to none-blocking.
            stream_set_blocking($pipes[1], 0);
            // Turn the timeout into microseconds.
            $timeout = $timeout * 1000000;
            // Output buffer.
            $buffer = '';
            // While we have time to wait.
            while ($timeout > 0) {
                $start = microtime(true);
                // Wait until we have output or the timer expired.
                $read = array($pipes[1]);
                $other = array();
                stream_select($read, $other, $other, 0, $timeout);
                // Get the status of the process.
                // Do this before we read from the stream,
                // this way we can't lose the last bit of output if the process dies between these functions.
                $status = proc_get_status($process);
                // Read the contents from the buffer.
                // This function will always return immediately as the stream is none-blocking.
                $buffer .= stream_get_contents($pipes[1]);
                if (!$status['running']) {
                    // Break from this loop if the process exited before the timeout.
                    break;
                }
                // Subtract the number of microseconds that we waited.
                $timeout -= (microtime(true) - $start) * 1000000;
            }
            // Check if there were any errors.
            $errors = stream_get_contents($pipes[2]);
            if (!empty($errors)) {
                throw new \Exception($errors);
            }
            // Kill the process in case the timeout expired and it's still running.
            // If the process already exited this won't do anything.
            proc_terminate($process, 9);
            // Close all streams.
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $buffer;
        } catch (\Exception $exception) {
            Log::error('exec_timeout error:' . $exception->getMessage());
        }
        return false;
    }
}