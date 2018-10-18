<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class WorldGroupon implements IWorldGroupon
{
    private $uid = NULL;

    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }

    function getInfo($type = 0)
    {
        Logger::trace("WorldGroupon getInfo start.");
        if($type != 0 && $type != 1)
        {
            throw new FakeException("error config type:[%d]", $type);
        }
        $ret = WorldGrouponLogic::getInfo($this->uid, $type);
        return $ret;
    }

    function buy($goodId, $num)
    {
        Logger::trace("WorldGroupon buy start. goodId:[%d], num:[%d]", $goodId, $num);
        if(empty($goodId) || empty($num) || $goodId <= 0 || $num <= 0)
        {
            throw new FakeException("WorldGroupon buy error config goodId:[%d] num:[%d]", $goodId, $num);
        }
        return WorldGrouponLogic::buy($this->uid, $goodId, $num);
    }

    function recReward($rewardId)
    {
        Logger::trace("WorldGroupon recReward start. rewardId:[%d]", $rewardId);
        if(empty($rewardId) || $rewardId <= 0)
        {
            throw new FakeException("WorldGroupon recReward rewardId:[%d]", $rewardId);
        }
        return WorldGrouponLogic::recReward($this->uid, $rewardId);
    }

    function forgeGoodNum($teamId, $goodId, $forgeNum)
    {
        Logger::trace("WorldGroupon forgeGoodNum start. teamId:[%d] goodId:[%d] forgeNum:[%d]", $teamId, $goodId, $forgeNum);
        WorldGrouponLogic::forgeGoodNum($teamId, $goodId, $forgeNum);
        return "ok";
    }

    function getTeamInfo4Plat()
    {
        Logger::trace("WorldGroupon getTeamInfo4Plat start.");
        return WorldGrouponLogic::getTeamInfo4Plat();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */