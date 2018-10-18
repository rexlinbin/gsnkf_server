<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: StepCounter.class.php 136576 2014-10-17 06:09:20Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/stepcounter/StepCounter.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-17 06:09:20 +0000 (Fri, 17 Oct 2014) $$
 * @version $$Revision: 136576 $$
 * @brief 
 *  
 **/
class StepCounter implements IStepCounter
{

    function checkStatus()
    {
        StepCounterLogic::isStepCounterOpen();
        $uid = RPCContext::getInstance()->getUid();
        return StepCounterLogic::checkStatus($uid);
    }

    function recReward()
    {
        StepCounterLogic::isStepCounterOpen();
        $uid = RPCContext::getInstance()->getUid();
        $res = StepCounterLogic::recReward($uid);
        if(!empty($res["userModify"]) && $res["userModify"])
        {
            $user = EnUser::getUserObj($uid);
            $user->update();
        }
        if(!empty($res["bagModify"]) && $res["bagModify"])
        {
            $bag = BagManager::getInstance()->getBag($uid);
            $bag->update();
        }
        return 'ok';
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */