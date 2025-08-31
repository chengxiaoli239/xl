<?php
namespace backend\service\Lucky5;

use backend\models\TzSystemsUsers;
use common\tools\Tool_Common;
use Yii;

/**
 * Lucky5 Cookie管理器
 * 用于处理cookie的存储、更新和验证
 */
class Lucky5CookieManager
{
    /**
     * @var array 支持的cookie类型
     */
    private static $supportedCookieTypes = [
        'session' => ['ASP.NET_SessionId', 'PHPSESSID', 'JSESSIONID'],
        'security' => ['Akamai_Cookie', 'GCLB', 'NOTICE_LOGIN_IN'],
        'user' => ['first_visit', 'robot7', 'user_token'],
        'tracking' => ['_ga', '_gid', '_gat', 'analytics_id']
    ];

    /**
     * @desc 保存cookie到数据库
     * @param int $uid 用户ID
     * @param int $tz_system_id 投注系统ID
     * @param array $cookies cookie数组
     * @return bool
     */
    public static function saveCookies($uid, $tz_system_id, $cookies)
    {
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid' => $uid, 'tz_system_id' => $tz_system_id]);
            if (!$TzSystemsUsers) {
                Tool_Common::log('Lucky5CookieManager::saveCookies', 'ERROR', '用户不存在', ['uid' => $uid, 'tz_system_id' => $tz_system_id]);
                return false;
            }

            $cookie_string = self::buildCookieString($cookies);
            $TzSystemsUsers->cookie = $cookie_string;
            $TzSystemsUsers->updated_at = time();
            
            $result = $TzSystemsUsers->save();
            
            if ($result) {
                Tool_Common::log('Lucky5CookieManager::saveCookies', 'INFO', 'Cookie保存成功', [
                    'uid' => $uid, 
                    'tz_system_id' => $tz_system_id, 
                    'cookie_count' => count($cookies)
                ]);
            } else {
                Tool_Common::log('Lucky5CookieManager::saveCookies', 'ERROR', 'Cookie保存失败', [
                    'uid' => $uid, 
                    'tz_system_id' => $tz_system_id, 
                    'errors' => $TzSystemsUsers->getErrors()
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Tool_Common::log('Lucky5CookieManager::saveCookies', 'ERROR', '保存Cookie异常', [
                'uid' => $uid, 
                'tz_system_id' => $tz_system_id, 
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @desc 从数据库获取cookie
     * @param int $uid 用户ID
     * @param int $tz_system_id 投注系统ID
     * @return string|null
     */
    public static function getCookies($uid, $tz_system_id)
    {
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid' => $uid, 'tz_system_id' => $tz_system_id]);
            return $TzSystemsUsers ? $TzSystemsUsers->cookie : null;
        } catch (\Exception $e) {
            Tool_Common::log('Lucky5CookieManager::getCookies', 'ERROR', '获取Cookie异常', [
                'uid' => $uid, 
                'tz_system_id' => $tz_system_id, 
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * @desc 验证cookie是否有效
     * @param string $cookie_string
     * @return bool
     */
    public static function validateCookies($cookie_string)
    {
        if (empty($cookie_string)) {
            return false;
        }

        $cookies = self::parseCookieString($cookie_string);
        
        # 检查必要的cookie是否存在
        $required_cookies = ['ASP.NET_SessionId', 'Akamai_Cookie'];
        foreach ($required_cookies as $required) {
            if (!isset($cookies[$required])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @desc 解析cookie字符串为数组
     * @param string $cookie_string
     * @return array
     */
    public static function parseCookieString($cookie_string)
    {
        $cookies = [];
        if (empty($cookie_string)) {
            return $cookies;
        }

        $parts = explode(';', $cookie_string);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            $name_value = explode('=', $part, 2);
            if (count($name_value) == 2) {
                $name = trim($name_value[0]);
                $value = trim($name_value[1]);
                if (!empty($name) && !empty($value)) {
                    $cookies[$name] = $value;
                }
            }
        }

        return $cookies;
    }

    /**
     * @desc 构建cookie字符串
     * @param array $cookies
     * @return string
     */
    public static function buildCookieString($cookies)
    {
        if (empty($cookies)) {
            return '';
        }

        $cookie_parts = [];
        foreach ($cookies as $cookie) {
            if (isset($cookie['name']) && isset($cookie['value'])) {
                $cookie_parts[] = $cookie['name'] . '=' . $cookie['value'];
            }
        }

        return implode('; ', $cookie_parts);
    }

    /**
     * @desc 合并cookie数组，去重并保持最新值
     * @param array $existing_cookies 现有cookie
     * @param array $new_cookies 新cookie
     * @return array
     */
    public static function mergeCookies($existing_cookies, $new_cookies)
    {
        $merged = [];
        
        # 先添加现有cookie
        foreach ($existing_cookies as $cookie) {
            if (isset($cookie['name'])) {
                $merged[$cookie['name']] = $cookie;
            }
        }
        
        # 用新cookie覆盖或添加
        foreach ($new_cookies as $cookie) {
            if (isset($cookie['name'])) {
                $merged[$cookie['name']] = $cookie;
            }
        }
        
        return array_values($merged);
    }

    /**
     * @desc 清理过期的cookie
     * @param array $cookies
     * @return array
     */
    public static function cleanExpiredCookies($cookies)
    {
        $valid_cookies = [];
        
        foreach ($cookies as $cookie) {
            # 过滤掉明显无效的cookie
            if (isset($cookie['name']) && isset($cookie['value']) && 
                !empty($cookie['name']) && !empty($cookie['value'])) {
                $valid_cookies[] = $cookie;
            }
        }
        
        return $valid_cookies;
    }

    /**
     * @desc 获取cookie统计信息
     * @param string $cookie_string
     * @return array
     */
    public static function getCookieStats($cookie_string)
    {
        $cookies = self::parseCookieString($cookie_string);
        $stats = [
            'total' => count($cookies),
            'by_type' => []
        ];

        foreach (self::$supportedCookieTypes as $type => $patterns) {
            $count = 0;
            foreach ($patterns as $pattern) {
                if (isset($cookies[$pattern])) {
                    $count++;
                }
            }
            $stats['by_type'][$type] = $count;
        }

        return $stats;
    }

    /**
     * @desc 检查cookie是否需要更新
     * @param int $uid 用户ID
     * @param int $tz_system_id 投注系统ID
     * @return bool
     */
    public static function needsCookieUpdate($uid, $tz_system_id)
    {
        try {
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid' => $uid, 'tz_system_id' => $tz_system_id]);
            if (!$TzSystemsUsers || empty($TzSystemsUsers->cookie)) {
                return true;
            }

            # 检查cookie是否过期（超过1小时）
            if (empty($TzSystemsUsers->updated_at) || 
                (time() - $TzSystemsUsers->updated_at) > 3600) {
                return true;
            }

            # 检查cookie是否有效
            return !self::validateCookies($TzSystemsUsers->cookie);
        } catch (\Exception $e) {
            Tool_Common::log('Lucky5CookieManager::needsCookieUpdate', 'ERROR', '检查Cookie更新异常', [
                'uid' => $uid, 
                'tz_system_id' => $tz_system_id, 
                'error' => $e->getMessage()
            ]);
            return true;
        }
    }

    /**
     * @desc 合并和整合cookie字符串
     * @param string $sessionId 初始session ID
     * @param string $additionalCookies 额外的cookie字符串
     * @return string 整合后的cookie字符串
     */
    public static function consolidateCookies($sessionId, $additionalCookies)
    {
        if (empty($additionalCookies)) {
            return $sessionId;
        }

        // 清理和标准化cookie
        $cleanSessionId = trim($sessionId, '; ');
        $cleanAdditional = trim($additionalCookies, '; ');
        
        // 移除常见的无效属性
        $cleanAdditional = str_replace(['; path=/; HttpOnly', '; HttpOnly', '; path=/'], '', $cleanAdditional);
        
        // 合并cookie
        $consolidated = $cleanSessionId;
        if (!empty($cleanAdditional)) {
            $consolidated .= '; ' . $cleanAdditional;
        }
        
        // 清理重复的分号
        $consolidated = preg_replace('/;+/', ';', $consolidated);
        $consolidated = trim($consolidated, '; ');
        
        Tool_Common::log('Lucky5CookieManager::consolidateCookies', 'INFO', 'Cookie整合完成', [
            'session_id' => $sessionId,
            'additional_cookies' => $additionalCookies,
            'consolidated' => $consolidated
        ]);
        
        return $consolidated;
    }

    /**
     * @desc 清理cookie字符串，移除无效字符和重复
     * @param string $cookieString 原始cookie字符串
     * @return string 清理后的cookie字符串
     */
    public static function cleanCookie($cookieString)
    {
        if (empty($cookieString)) {
            return '';
        }

        // 移除无效属性
        $cleaned = str_replace(['; path=/; HttpOnly', '; HttpOnly', '; path=/'], '', $cookieString);
        
        // 清理重复的分号
        $cleaned = preg_replace('/;+/', ';', $cleaned);
        $cleaned = trim($cleaned, '; ');
        
        // 移除空值
        $cookieParts = explode(';', $cleaned);
        $validParts = [];
        
        foreach ($cookieParts as $part) {
            $part = trim($part);
            if (!empty($part) && strpos($part, '=') !== false) {
                $validParts[] = $part;
            }
        }
        
        $result = implode('; ', $validParts);
        
        Tool_Common::log('Lucky5CookieManager::cleanCookie', 'INFO', 'Cookie清理完成', [
            'original' => $cookieString,
            'cleaned' => $result
        ]);
        
        return $result;
    }

    /**
     * @desc 从响应中更新cookie
     * @param int $uid 用户ID
     * @param int $tz_system_id 投注系统ID
     * @param array $response 响应数据
     * @return bool
     */
    public static function updateCookiesFromResponse($uid, $tz_system_id, $response)
    {
        try {
            // 检查响应中是否包含新的cookie信息
            if (isset($response['cookies']) || isset($response['Set-Cookie'])) {
                $newCookies = [];
                
                if (isset($response['cookies'])) {
                    $newCookies = $response['cookies'];
                } elseif (isset($response['Set-Cookie'])) {
                    $newCookies = $response['Set-Cookie'];
                }
                
                if (!empty($newCookies)) {
                    // 获取现有cookie
                    $existingCookieString = self::getCookies($uid, $tz_system_id);
                    $existingCookies = self::parseCookieString($existingCookieString);
                    
                    // 解析新cookie
                    $newCookieArray = [];
                    foreach ($newCookies as $cookie) {
                        $parsed = self::parseSingleCookie($cookie);
                        if ($parsed) {
                            $newCookieArray[] = $parsed;
                        }
                    }
                    
                    // 合并cookie
                    $mergedCookies = self::mergeCookies($existingCookies, $newCookieArray);
                    
                    // 保存到数据库
                    return self::saveCookies($uid, $tz_system_id, $mergedCookies);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Tool_Common::log('Lucky5CookieManager::updateCookiesFromResponse', 'ERROR', '更新Cookie异常', [
                'uid' => $uid, 
                'tz_system_id' => $tz_system_id, 
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @desc 解析单个cookie字符串
     * @param string $cookieString
     * @return array|null
     */
    private static function parseSingleCookie($cookieString)
    {
        if (empty($cookieString)) {
            return null;
        }

        $parts = explode(';', $cookieString);
        $cookie = [];
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
                $cookie['name'] = trim($key);
                $cookie['value'] = trim($value);
                break;
            }
        }
        
        return !empty($cookie['name']) ? $cookie : null;
    }
}
