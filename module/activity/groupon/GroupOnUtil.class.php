<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GroupOnUtil.class.php 151576 2015-01-10 09:59:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/GroupOnUtil.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-01-10 09:59:17 +0000 (Sat, 10 Jan 2015) $$
 * @version $$Revision: 151576 $$
 * @brief 
 *  
 **/
class GroupOnUtil
{
    /**
     * @param $day int 某一天
     * @return array [goodid => soldNum]
     * @throws ConfigException
     */
    public static function getDayGoodListFromConf($day)
    {
        $arrId = self::getDayGoodListIds($day);
        return array_fill_keys($arrId, 0);
    }

    public static function getDayGoodListIds($day)
    {
        $actConf = self::getActConf();
        if( !isset( $actConf[GroupOnDef::GROUPONIDS][$day]  ) )
        {
            throw new ConfigException('not found goodlist for day:%d', $day);
        }
        $arrId = $actConf[GroupOnDef::GROUPONIDS][$day];
        return $arrId;
    }

    public static function getActConf()
    {
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        return $conf['data'][GroupOnConf::DEFAULT_ID];
    }
    public static function getGoodConf()
    {
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        return $conf['data'];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */