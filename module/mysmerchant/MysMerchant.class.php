<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MysMerchant.class.php 119255 2014-07-08 10:44:29Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/MysMerchant.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-07-08 10:44:29 +0000 (Tue, 08 Jul 2014) $$
 * @version $$Revision: 119255 $$
 * @brief 
 *  
 **/

class MysMerchant implements IMysMerchant
{

    /**
     * 购买物品
     * @param int $goodsId
     * @return array
     * <code>
     * [
     *     ret:string            'ok'
     *     drop:array
     * ]
     * </code>
     */
    public function buyGoods($goodsId)
    {
        Logger::trace("MysMerchant.buyGoods start. params goodsid:%d.", $goodsId);

        if(empty($goodsId))
        {
            throw new FakeException('error params goodsId %s.',$goodsId);
        }

        $uid = RPCContext::getInstance()->getUid();
        $mysMerchant = new MyMysMerchant($uid);  //new 一个新的对象
        if(!$mysMerchant->checkMysMerchantState() && !$mysMerchant->checkIfForever())
        {
            throw new FakeException('mysmerchant is alredy closed.');
        }
        if($mysMerchant->canSysRfrGoodsList())
        {
            throw new FakeException('qianduan not player should refresh the goodslist.');
        }
        $goodsList = $mysMerchant->getGoodsList();
        if(in_array($goodsId, $goodsList) == FALSE)
        {
            throw new FakeException('now the goodslist is %s.can not buy goods %s.', $goodsList, $goodsId);
        }
        $ret = $mysMerchant->exchange($goodsId);  //购买
        $mysMerchant->update();
        Logger::trace('MysMerchant.buyGoods start.result %s.', $ret);
        return $ret;
    }

    /**
     * @return array
     * string "nomerchant" 神秘商人没有触发后者已过期
     * <code>
     * [
     *     goods_list:array
     *     [
     *         goodsId=>canBuyNum  可购数量
     *     ]
     *     refresh_cd:int            系统自动刷新时间CD
     *     refresh_num:int            玩家刷新次数
     *     merchant_end_time           神秘商人触发状态截止时间(消失时间)
     * ]
     * </code>
     */
    public function getShopInfo()
    {
        Logger::trace('MysMerchant.getShopInfo start');

        $uid = RPCContext::getInstance()->getUid();
        $mysMerchant = new MyMysMerchant($uid);
        if(!$mysMerchant->checkMysMerchantState() && !$mysMerchant->checkIfForever())
        {
            return array();
        }
        $mysMerchant->sysRfrGoodsList();
        $shopInfo = $mysMerchant->getShopInfo();
        $mysMerchant->update();
        return $shopInfo;
    }

    /**
     *
     * @param int $type 1.金币刷新  2.物品刷新
     * @return array
     */
    public function playerRfrGoodsList($type)
    {
        $bag = NULL;
        $userObj = NULL;
       
        $uid = RPCContext::getInstance()->getUid();
        $mysMerchant = new MyMysMerchant($uid);
        if(!$mysMerchant->checkMysMerchantState() && !$mysMerchant->checkIfForever()) //检测神秘商人是否已触发
        {
            throw new FakeException('mysmerchant is alredy closed.');
        }
        if($mysMerchant->canSysRfrGoodsList())  //检验是否应该自动刷新
        {
            throw new FakeException('qianduan not player should refresh the goodslist.');
        }
        if($type == MysMerchantDef::MYSMERCHANT_REFR_LIST_TYPE_GOLD)  //使用金币刷新
        {
            $userObj = EnUser::getUserObj();
            $refrNum = $mysMerchant->getPlayerRfrNum();  //当日玩家主动刷新次数
            $vip = $userObj->getVip();  //玩家vip等级
            if($refrNum >= btstore_get()->VIP[$vip]['copyShopTime'])  //副本神秘商人刷新次数限制
            {
                throw new FakeException('no refresh num.have refreshed %d times.has only %d times.',$refrNum,btstore_get()->VIP[$vip]['mysteryRfrTimes']);
            }
            $needGold = btstore_get()->MYSMERCHANT['refresh_gold_base'] +
                    $refrNum * btstore_get()->MYSMERCHANT['refresh_gold_inc'];  //刷新金币基础值 + 刷新金币地增值
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSMERCHANT_REFR) == FALSE)
            {
                throw new FakeException('playerRfrGoodsList.sub gold failed.');
            }
            $mysMerchant->playerRfrGoodsListByGold();
        }
        else if($type == MysMerchantDef::MYSMERCHANT_REFR_LIST_TYPE_ITEM)  //使用物品刷新
        {
            $bag = BagManager::getInstance()->getBag();
            if(isEmpty(btstore_get()->MYSMERCHANT['refresh_item'])) {
               throw new FakeException('@cehua, goods can not buy by item');
            }
            $itemTmplId = btstore_get()->MYSMERCHANT['refresh_item'][0];  //刷新需要的物品ID
            $itemTmplCount = btstore_get()->MYSMERCHANT['refresh_item'][1];  //刷新需要的物品数量
            //TODO:每次刷新都是消耗物品
            if($bag->deleteItembyTemplateID($itemTmplId, $itemTmplCount) == FALSE)
            {
                throw new FakeException('delete item from bag failed.');
            }
            $mysMerchant->refreshGoodsList();
        }
        else
        {
            throw new FakeException('invalide playerRfrGoodsList type %s.',$type);
        }
        $shopInfo = $mysMerchant->getShopInfo();
        $mysMerchant->update();
        if($userObj != NULL)
        {
            $userObj->update();
        }
        if($bag != NULL)
        {
            $bag->update();
        }
        return $shopInfo;
    }

    public function buyMerchantForever()
    {
        $uid = RPCContext::getInstance()->getUid();
        $mysMerchant = new MyMysMerchant($uid);

        if($mysMerchant->checkIfForever())
        {
            throw new FakeException(' mysmerchant is already forever ');
        }

        $userObj = EnUser::getUserObj($uid);
        $vip = $userObj->getVip();
        $openMysmerchant = btstore_get()->VIP[$vip]['openMysMerchant'];

        Logger::trace('openMysmerchant:%d, vip:%d', $openMysmerchant[0], $vip);

        if($openMysmerchant[0] == 0)
        {
            throw new FakeException(' buyMerchant forever not open ');
        }
        if($userObj->getLevel() < $openMysmerchant[0])
        {
            throw new FakeException('do not meet the open condition userlv:%d, viplv:%d', $userObj->getLevel(), $vip);
        }

        $needGold = $openMysmerchant[1];
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MYSMERCHANT_OPEN_FOREVER) == FALSE)
        {
            throw new FakeException('buyMerchantForever.sub gold failed.');
        }

        $mysMerchant->merchantForever();
        $mysMerchant->update();

        return true;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */