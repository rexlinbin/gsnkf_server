<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: ActExchange.class.php 252241 2016-07-18 11:02:04Z BaoguoMeng $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actexchange/ActExchange.class.php $$
 * @author $$Author: BaoguoMeng $$(hoping@babeltime.com)
 * @date $$Date: 2016-07-18 11:02:04 +0000 (Mon, 18 Jul 2016) $$
 * @version $$Revision: 252241 $$
 * @brief
 *
 **/

class ActExchange implements IActExchange
{
    public function getShopInfo()
    {
        Logger::trace('ActExchange getGoodsInfo start');
        $uid = RPCContext::getInstance()->getUid();

        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE)
        {
            throw new FakeException('now %d is not during act exchange time.', Util::getTime());
        }

        if(MyActExchange::isActExchangeOpen() == FALSE)
        {
            throw new FakeException('actexchange is not open for this user whose level is %d', EnUser::getUserObj()->getLevel());
        }
        $myActExchange = new MyActExchange($uid);
        //$myActExchange->sysRfrGoodsList(false);
        $shopInfo = $myActExchange->getShopInfo();
        $myActExchange->update();
        Logger::trace('ActExchange getGoodsInfo end');

        return $shopInfo;
    }

    //兑换物品
    public function buyGoods($id, $num = 1)
    {
        Logger::trace("ActExchange::buyGoods start. id:%d", $id);
        if(empty($id))
        {
            throw new FakeException('error params id:%s', $id);
        }
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE)
        {
            throw new FakeException('now %d is not during act exchange time.', Util::getTime());
        }
        if(MyActExchange::isActExchangeOpen() == FALSE)
        {
            throw new FakeException('actexchange is not open for this user whose level is %d', EnUser::getUserObj()->getLevel());
        }
        $uid = RPCContext::getInstance()->getUid();
        $myActExchange = new MyActExchange($uid);
        //检验是否应该自动刷新
        if($myActExchange->canSysRfrGoodList())
        {
            throw new FakeException('qianduan must sys refresh the goodslist');
        }
        if(!$myActExchange->canBuy($id, $num))
        {
            throw new FakeException('actexchange num has reached');
        }
        $goodsList = $myActExchange->getGoodslist();
        if(in_array($id, array_keys($goodsList)) == FALSE)
        {
            throw new FakeException('now the goodslist is %s. can not buy good:%s', array_keys($goodsList), $id);
        }
        $ret = $myActExchange->exchange($id, $num);
        $myActExchange->update();
        Logger::trace("ActExchange::buyGoods end. id:%d", $id);
        return $ret;
    }

    public function rfrGoodsList($id)
    {
        Logger::trace("ActExchange::rfrGoodsList start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE)
        {
            throw new FakeException('now %d is not during act exchange time.', Util::getTime());
        }
        if(MyActExchange::isActExchangeOpen() == FALSE)
        {
            throw new FakeException('actexchange is not open for this user whose level is %d', EnUser::getUserObj()->getLevel());
        }
        $userObj = NULL;
        $uid = RPCContext::getInstance()->getUid();
        $myActExchange = new MyActExchange($uid);
        //检验是否应该自动刷新
        if($myActExchange->canSysRfrGoodList())
        {
            throw new FakeException('qianduan must sys refresh the goodslist');
        }
        $goodsList = $myActExchange->getGoodslist();
        if(in_array($id, array_keys($goodsList)) == FALSE)
        {
            throw new FakeException('now the goodslist is %s. can not refresh this goodid:%s', array_keys($goodsList), $id);
        }
        //扣除金币 刷新商品列表
        $userObj = EnUser::getUserObj();

        //活动配置
        $conf = EnActivity::getConfByName(ActivityName::ACT_EXCHANGE);
        $confData = $conf['data'];
        //$confSpecific = $confData[ActExchangeDef::ACTEXCHANGE_GOODS_DEFAULT_ID];
        if(!isset($confData[$id]))
        {
            throw new ConfigException('this goodid:%d is not in config', $id);
        }
        $freeRfrNum = $myActExchange->getFreeRfrNum($id);
        if($freeRfrNum > 0)
        {
            $myActExchange->playerRfrGoodsListByFreeNum($id);
        }
        else
        {
            $needConf = $confData[$id];
            $refrNum = $myActExchange->getPlayerRfrNum($id);   //当日玩家主动刷新次数
            $needGold = $needConf[ActExchangeDef::ACTEXCHANGE_GOLD][0] + $refrNum * $needConf[ActExchangeDef::ACTEXCHANGE_GOLD][1];
            $needGold = $needGold < $needConf[ActExchangeDef::ACTEXCHANGE_GOLD_TOP]?$needGold:$needConf[ActExchangeDef::ACTEXCHANGE_GOLD_TOP];

            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_ACTEXCHANGE_REFR) == FALSE)
            {
                throw new FakeException('ActExchange::rfrGoodsList.subGold failed');
            }
            $myActExchange->playerRfrGoodsListByGold($id);
        }

        $goodInfo = $myActExchange->getInfoById($id);
        $myActExchange->update();
        if($userObj != NULL)
        {
            $userObj->update();
        }
        Logger::trace("ActExchange::rfrGoodsList end");
        return $goodInfo;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */