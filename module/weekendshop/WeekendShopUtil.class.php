<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: WeekendShopUtil.class.php 137231 2014-10-23 02:28:07Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/weekendshop/WeekendShopUtil.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-23 02:28:07 +0000 (Thu, 23 Oct 2014) $$
 * @version $$Revision: 137231 $$
 * @brief 
 *  
 **/
class WeekendShopUtil
{
    /**
     * 从数组中，循环取出对应index的值
     * @param $arr
     * @param $index
     * @return mixed
     * @throws Exception
     */
    public static function calCircleId($arr, $index)
    {
        if (!is_int($index) || !is_array($arr))
        {
            Logger::fatal("error params");
            throw new Exception("inter");
        }
        $realIndex = $index % count($arr);
        return $arr[$realIndex];
    }

    /**
     * 抽取arr中arrKey对应的val
     * @param $arr 二维数组 array[0 => array['id' => 123]]
     * @param $arrKey
     * @param $id 要取得第二维的键 默认‘id’
     * @return array
     */
    public static function extractValue($arr, $arrKey, $id='id')
    {
        $arrVal = array();
        foreach ($arrKey as $key)
        {
            $arrVal[] = $arr[$key][$id];
        }
        return $arrVal;
    }

    /**
     * 读取某条配置
     * @param $id
     * @return mixed
     */
    public static function getWeekendShopConf($id)
    {
        return btstore_get()->WEEKENDSHOP[$id];
    }

    public static function getDefaultConf()
    {
        return self::getWeekendShopConf(WeekendShopDef::DEFAULT_ID);
    }

    public static function getWeekendGoodsConf()
    {
        return btstore_get()->WEEKENDGOODS;
    }

    /**
     * @param $weekday 周几 格式应为1, 2 类似
     * @param $offset 偏移量
     * @throws Exception
     * @return timestamp
     */
    public static function getTimestampOfWeekdayAndHms($weekday, $offset)
    {
        if (!in_array($weekday, WeekendShopDef::$arrNumToWeekday)
            || !is_int($offset)
        )
        {
            Logger::fatal("error params weekday:%s, hms:%s", $weekday, $offset);
            throw new Exception("inter");
        }

        $curweekday = date('w');
        //为0 就是 星期七
        $curweekday = $curweekday?$curweekday:7;
        $res = 0;
        if ($curweekday > $weekday)
        {
            $res = strtotime('today') + $offset - ($curweekday - $weekday) * 86400;
        }
        else
        {
            $res = strtotime('today') + $offset + ($weekday - $curweekday) * 86400;
        }

        return $res;
    }

    /**
     * 检查本周的周末商店是否开启
     */
    public static function isWeekendShopOpen()
    {
        $defaultConfForShop = self::getDefaultConf();
        $arrLastTime = $defaultConfForShop[WeekendShopCsvDef::LAST_TIME];
        $startTime = self::getTimestampOfWeekdayAndHms(
            $arrLastTime[0][0],
            $arrLastTime[0][1]
        );
        $endTime = self::getTimestampOfWeekdayAndHms(
            $arrLastTime[1][0],
            $arrLastTime[1][1]
        );
        $now = Util::getTime();
        if($now <= $startTime || $now >= $endTime)
        {
            return false;
        }
        return true;
    }

    public static function getWeeksBetween()
    {
        $weekendShopStartTime = strtotime(WeekendShopDef::WEEKENDSHOP_STARTTIME);
        $dayBetween = Util::getDaysBetween($weekendShopStartTime);
        return intval($dayBetween / 7);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */