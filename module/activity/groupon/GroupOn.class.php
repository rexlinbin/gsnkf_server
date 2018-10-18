<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GroupOn.class.php 151576 2015-01-10 09:59:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/GroupOn.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-10 09:59:17 +0000 (Sat, 10 Jan 2015) $$
 * @version $$Revision: 151576 $$
 * @brief 
 *  
 **/
class GroupOn implements IGroupOn
{


    function getShopInfo()
    {
        Logger::trace('GroupOn::getShopInfo start');
        GroupOnLogic::checkState();
        $shopInfo = GroupOnLogic::getShopInfo();
        EnUser::getUserObj()->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        Logger::trace('GroupOn::getShopInfo end');
        return $shopInfo;
    }

    function buyGood($goodid)
    {
        Logger::trace('GroupOn:butyGood start');
        if(empty($goodid))
        {
            throw new FakeException('error params goodid:%d', $goodid);
        }
        GroupOnLogic::checkState();
        $ret = GroupOnLogic::buyGood($goodid);
        EnUser::getUserObj()->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        Logger::trace('GroupOn:butyGood end');
        return $ret;
    }

    function recReward($goodid, $rewardId)
    {
        Logger::trace('GroupOn:recReward start');
        if(empty($goodid) || empty($rewardId))
        {
            throw new FakeException('error params goodid:%d rewardId:%d',$goodid, $rewardId);
        }
        $uid = RPCContext::getInstance()->getUid();
        GroupOnLogic::checkState();
        $ret = GroupOnLogic::recReward($uid, $goodid, $rewardId);
        EnUser::getUserObj()->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        Logger::trace('GroupOn:recReward end');
        return $ret;
    }

    function leaveGroupOn()
    {
        Logger::trace('GroupOn::leaveGroupOn start.');
        RPCContext::getInstance()->unsetSession( SPECIAL_ARENA_ID::SESSION_KEY );
        return 'ok';
        Logger::trace('GroupOn::leaveGroupOn start.');
    }

    /**
     * 刷新团购列表 内部调用 不对前端发布
     * @param $specialUid
     * @throws FakeException
     */
    public function refGoodsList($specialUid)
    {
        Logger::trace('GroupOn::refGoodsList Start.');
        if($specialUid <= 0)
        {
            throw new FakeException('Invalid specialUid:%d', $specialUid);
        }
        GroupOnLogic::refGoodsList();
        Logger::trace('GroupOn::guildDataRefresh End.');
    }

    /**
     * 增加商品团购数量 内部调用 不对前端发布
     * @param $specialUid
     * @throws FakeException
     */
    public function incGroupOnTimes($specialUid, $goodid)
    {
        Logger::trace('GroupOn::incGOTimes Start.');
        if($specialUid <= 0)
        {
            throw new FakeException('Invalid uid:%d', $specialUid);
        }
        GroupOnLogic::incGroupOnTimes($goodid);
        Logger::trace('GroupOn::incGOTimes End.');
    }
    
    /**
     * 由timer触发调用
     */
    public function reissueForTime()
    {
    	Util::asyncExecute('groupon.doReissue', array());
    }
    
    /**
     * 上面的checkReward函数触发，用长请求方式执行
     */
    public function doReissue()
    {
    	GroupOnLogic::doReissue();
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */