<?php

namespace App\Service;

use App\Utils\CommonUtil;
use App\Utils\DomainUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class WhoisService extends BaseService
{

    /**
     * 域名Whois查询
     * @param $domain
     * @param bool $fresh
     * @return array
     */
    public function check($domain, $fresh = false)
    {
        $domain = DomainUtil::getTopDomain(trim($domain));
        $domain = DomainUtil::punycode_encode($domain);

        $whois_info = $this->get_whois_info($domain, $fresh);
        $whois_info_arr = $this->whois_info_parse($whois_info['info']);
        return compact('domain', 'whois_info', 'whois_info_arr');
    }

    /**
     * whois查询
     * @param $domain
     * @param bool $fresh
     * @return array|mixed
     */
    public function get_whois_info($domain, $fresh = false)
    {
        $updated_at = Carbon::now()->format('Y-m-d H:i');
        if ($fresh) {
            $data = $this->execWhois($domain, $updated_at);
            return $data;
        } else {
            if (!$whois_info = Redis::hget('whois', $domain)) {
                $data = $this->execWhois($domain, $updated_at);
                return $data;
            } else {
                return json_decode($whois_info, 'true');
            }
        }
    }

    /**
     * whois数据格式化
     * @param $whoisInfoStr
     * @return array
     */
    public function whois_info_parse($whoisInfoStr)
    {
        $infoData = [];
        $domainName = '';
        if (preg_match("/Domain\s*Name:\s*(.*?)\\n/i", $whoisInfoStr, $matchs)) {
            $domainName = $matchs[1];
        }
        $infoData['domain_name'] = DomainUtil::punycode_decode(trim(DomainUtil::getTopDomain($domainName)));
        $suffixSplit = explode('.', $domainName);
        $suffixStr = '.' . array_pop($suffixSplit);
        $whoisInfoList = explode("\n", $whoisInfoStr);
        if ($suffixStr == '.cn' || $suffixStr == '.中国') {
            foreach ($whoisInfoList as $key => $value) {
                $splitTemp = explode(':', $value, 2);
                $splitTemp[0] = str_replace(array("\r\n", "\n"), '', $splitTemp[0]);
                // 所有者联系邮箱
                if (strpos($value, 'Contact Email') !== false && preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/', trim($splitTemp[1]))) {
                    $infoData['Registrant_Email'] = trim($splitTemp[1]);
                }
                // 所有者联系电话
                if (strpos($value, 'Contact Phone') !== false) {
                    $temp = trim($splitTemp[1]);
                    $infoData['Registrant_Phone'] = substr($temp, strpos($temp, '.') + 1);
                } //注册商
                elseif (strpos($value, 'Sponsoring Registrar') !== false) {
                    $infoData['Registrar'] = trim($splitTemp[1]);
                } //注册者
                elseif ($splitTemp[0] == 'Registrant') {
                    $infoData['Registrant_Name'] = trim($splitTemp[1]);
                } //注册日期
                elseif (strpos($value, 'Registration Time') !== false) {
                    $infoData['Creation_Date'] = trim($splitTemp[1]);
                } //到期日期
                elseif (strpos($value, 'Expiration Time') !== false) {
                    $infoData['Expiration_Date'] = trim($splitTemp[1]);
                } //DNS服务器
                elseif (strpos($value, 'Name Server') !== false) {
                    if (empty($infoData['Name_Server']))
                        $infoData['Name_Server'] = array();
                    $infoData['Name_Server'][] = trim($splitTemp[1]);
                } //域名状态
                elseif (strpos($value, 'Domain Status') !== false) {
                    if (empty($infoData['Domain_Status']))
                        $infoData['Domain_Status'] = array();
                    $value = trim($splitTemp[1]);
                    if (strpos($value, 'clientDeleteProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_DELETE_PROHIBIT') . $value;
                        //（注册商设置禁止删除）
                    } elseif (strpos($value, 'clientTransferProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_TRANSFER_PROHIBIT') . $value;
                        //（注册商设置禁止转移）
                    } elseif (strpos($value, 'clientUpdateProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_UPDATE_PROHIBIT') . $value;
                        //'（注册商设置禁止更新）'
                    } elseif (strpos($value, 'serverDeleteProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_DELETE_PROHIBIT') . $value;
                        //（注册局设置禁止删除）
                    } elseif (strpos($value, 'serverTransferProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_TRANSFER_PROHIBIT') . $value;
                        //（注册局设置禁止转移）
                    } elseif (strpos($value, 'serverUpdateProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_UPDATE_PROHIBIT') . $value;
                        //'（注册局设置禁止更新）'
                    }
                    $infoData['Domain_Status'][] = $value;
                }
            }
            return $infoData;
        } else {
            foreach ($whoisInfoList as $key => $value) {
                $splitTemp = explode(':', $value, 2);
                // 			$splitTemp[0] = str_replace(array("\r\n","\n"), '', $splitTemp[0]);
                //所有者
                if (strpos($value, 'Registrant Name') !== false && strpos($value, 'PRIVACY') === false) {
                    if ($temp = trim($splitTemp[1])) {
                        $infoData['Registrant_Name'] = $temp;
                    }
                } // 所有者联系邮箱
                elseif (strpos($value, 'Registrant Email') !== false && preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/', trim($splitTemp[1]))) {
                    $infoData['Registrant_Email'] = trim($splitTemp[1]);
                }
                // 所有者联系电话
                if (strpos($value, 'Registrant Phone:') !== false && strpos($value, 'PRIVACY') === false) {
                    $temp = trim($splitTemp[1]);
                    if ($phone = substr($temp, strpos($temp, '.') + 1)) {
                        $infoData['Registrant_Phone'] = $phone;
                    }
                } //注册商
                elseif (strpos($value, 'Registrar:') !== false) {
                    $infoData['Registrar'] = trim($splitTemp[1]);
                } //注册日期
                elseif (strpos($value, 'Creation Date') !== false) {
                    $data = trim($splitTemp[1]);
                    $dataSplit = explode('T', $data);
                    $infoData['Creation_Date'] = $dataSplit[0];
                } //到期日期
                elseif (strpos($value, 'Expiration Date') !== false) {
                    $data = trim($splitTemp[1]);
                    $dataSplit = explode('T', $data);
                    $infoData['Expiration_Date'] = $dataSplit[0];
                } elseif (strpos($value, 'Registry Expiry Date') !== false) {
                    $data = trim($splitTemp[1]);
                    $dataSplit = explode('T', $data);
                    $infoData['Expiration_Date'] = $dataSplit[0];
                } elseif (strpos($value, 'Updated Date') !== false) {
                    $data = trim($splitTemp[1]);
                    $dataSplit = explode('T', $data);
                    $infoData['Updated_Date'] = $dataSplit[0];
                } //域名状态
                elseif (strpos($value, 'Domain Status') !== false) {
                    if (empty($infoData['Domain_Status']))
                        $infoData['Domain_Status'] = array();
                    $value = trim($splitTemp[1]);
                    if (strpos($value, 'clientDeleteProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_DELETE_PROHIBIT') . $value;
                        //（注册局设置禁止删除）
                    } elseif (strpos($value, 'clientTransferProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_TRANSFER_PROHIBIT') . $value;
                        //（注册局设置禁止转移）
                    } elseif (strpos($value, 'clientUpdateProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_CLIENT_UPDATE_PROHIBIT') . $value;
                        //'（注册商设置禁止更新）'
                    } elseif (strpos($value, 'serverDeleteProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_DELETE_PROHIBIT') . $value;
                        //（注册局设置禁止删除）
                    } elseif (strpos($value, 'serverTransferProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_TRANSFER_PROHIBIT') . $value;
                        //（注册局设置禁止转移）
                    } elseif (strpos($value, 'serverUpdateProhibited') !== false) {
                        $value = __('status.WHOIS_DOMAIN_STATUS_SERVER_UPDATE_PROHIBIT') . $value;
                        //'（注册商设置禁止更新）'
                    }
                    $infoData['Domain_Status'][] = $value;
                } //DNS服务器
                elseif (strpos($value, 'Name Server') !== false) {
                    if (empty($infoData['Name_Server']))
                        $infoData['Name_Server'] = array();
                    $infoData['Name_Server'][] = trim($splitTemp[1]);
                }
            }
            return $infoData;
        }
    }

    /**
     * 获取域名whois信息数组
     * @param $domain
     * @param bool $fresh
     * @return array
     */
    public function get_whois_info_arr($domain, $fresh = false)
    {
        $info = $this->get_whois_info($domain, $fresh);
        $info_arr = $this->whois_info_parse($info['info']);
        return $info_arr;
    }

    /**
     * 命令行查询whois
     * @param $domain
     * @param $updated_at
     * @return array
     */
    private function execWhois($domain, $updated_at)
    {
        $whois_command = config('tool.whois_command');
        $whois_info_str = CommonUtil::exec_timeout($whois_command . ' ' . $domain, 3);
        //截取whois原始信息，部分插件会整理出2种格式信息
        $whois_info_str = substr($whois_info_str, strrpos($whois_info_str, 'Domain Name: '));
        if (strpos($whois_info_str, 'Domain Name:') !== false) {
            Redis::hset('whois', $domain, json_encode(['updated_at' => $updated_at, 'info' => $whois_info_str]));
        }
        return ['updated_at' => $updated_at, 'info' => $whois_info_str];
    }

}
