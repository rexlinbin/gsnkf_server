<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: TopupReward.class.php 122072 2014-07-22 08:05:51Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/TopupReward.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-07-22 08:05:51 +0000 (Tue, 22 Jul 2014) $$
 * @version $$Revision: 122072 $$
 * @brief 
 *  
 **/
class TopupReward implements ITopupReward
{

    function getInfo()
    {
        Logger::trace('TopupReward::getInfo start');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('TopupReward::getInfo end');
        return TopupRewardLogic::getInfo($uid);
    }

    function rec($day)
    {
        Logger::trace('TopupReward::rec start');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('TopupReward::rec end');
        return TopupRewardLogic::rec($uid, $day);
    }

    public function rewardUserOnLogin($uid)
    {
        Logger::trace('topupReward::rewardUserOnLogin start, uid:%d', $uid);
        TopupRewardLogic::rewardUserOnLogin($uid);
        Logger::trace('topupReward::rewardUserOnLogin end, uid:%d', $uid);
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */