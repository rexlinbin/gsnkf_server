<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroShop.class.php 90044 2014-02-14 10:41:45Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/HeroShop.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-02-14 10:41:45 +0000 (Fri, 14 Feb 2014) $
 * @version $Revision: 90044 $
 * @brief 
 *  
 **/
class HeroShop implements IHeroShop
{
    public function getMyShopInfo ()
    {
        Logger::trace('HeroShop.getMyShopInfo start');
        $ret = HeroShopLogic::getMyShopInfo();
        RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::HERO_SHOP);
        Logger::trace('HeroShop.getMyShopInfo end');
        return $ret;
    }

    public function buyHero ($type)
    {
        Logger::trace('HeroShop.buyHero start.params type:%d.',$type);
        $ret = HeroShopLogic::buyHero($type);
        Logger::trace('HeroShop.buyHero end.');
        return $ret;
    }
    
    public function leaveShop()
    {
        if(HeroShopLogic::isHeroShopOpen() == FALSE)
        {
            throw new FakeException('heroshop is not open for this user whose level is %d.',EnUser::getUserObj()->getLevel());
        }
        RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
    }

    public function refreshRank($score=0)
    {
        Logger::trace('HeroShop.refreshRank start.params socre:%d.',$score);
        HeroShopLogic::refreshRank($score);
        Logger::trace('HeroShop.refreshRank end.');
    }
    
    public function rewardUserOnActClose()
    {
        RPCContext::getInstance()->sendFilterMessage('arena', SPECIAL_ARENA_ID::HERO_SHOP,
                    PushInterfaceDef::HEROSHOP_ACT_END, array());
        RPCContext::getInstance()->asyncExecuteTask('heroshop.rewardUser', array());   
    }
    
    public function rewardUser()
    {
        Logger::info('HeroShop.rewardUser start.');
        HeroShopLogic::rewardUser();
        Logger::info('HeroShop.rewardUser end.');
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */