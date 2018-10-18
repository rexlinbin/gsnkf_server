<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: TopupRewardDao.class.php 119964 2014-07-11 08:42:10Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/TopupRewardDao.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-07-11 08:42:10 +0000 (Fri, 11 Jul 2014) $$
 * @version $$Revision: 119964 $$
 * @brief 
 *  
 **/
class TopupRewardDao
{
    public static function loadData($uid)
    {
        $data = new CData();
        $arrRet = $data->select(TopupRewardDef::$fields)
                ->from(TopupRewardDef::TBL_TOPUP_REWARD)
                ->where(array(TopupRewardDef::UID, '=', $uid))
                ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0];
        }
        return array();
    }

    public static function insertData($topupReward)
    {
        $data = new CData();
        $ret = $data->insertInto(TopupRewardDef::TBL_TOPUP_REWARD)
                ->values($topupReward)
                ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }

    public static function updateData($topupReward, $uid)
    {
        $data = new CData();
        $ret = $data->update(TopupRewardDef::TBL_TOPUP_REWARD)
                ->set($topupReward)
                ->where(array(TopupRewardDef::UID, '=', $uid))
                ->query();
        if ($ret['affected_rows'] == 0)
        {
            return false;
        }
        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */