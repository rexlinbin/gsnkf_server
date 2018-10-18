<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: WeekendShopLogic.class.php 137238 2014-10-23 02:53:13Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/weekendshop/WeekendShopLogic.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-23 02:53:13 +0000 (Thu, 23 Oct 2014) $$
 * @version $$Revision: 137238 $$
 * @brief 
 *  
 **/
class WeekendShopLogic
{
    public static function getInfo()
    {
        self::checkState();
        $weekendShopManager = new WeekendShopManager();
        $shopInfo = array();
        $shopInfo[WeekendShopDef::WEEKENDSHOP_GOODSLIST] = $weekendShopManager->getGoodList();
        $shopInfo[WeekendShopDef::WEEKENDSHOP_NUM] = $weekendShopManager->getWeekendShopNum();
        $shopInfo[WeekendShopDef::HAS_BUY_NUM] = $weekendShopManager->getHasBuyNum();
        $shopInfo[WeekendShopDef::RFR_NUM_BY_PLAYER] = $weekendShopManager->getRfrNumByPlayer();

        $weekendShopManager->update();
        return $shopInfo;
    }

    public static function rfrGoodList($type, $extra = NULL)
    {
        self::checkState();
        $weekendShopManager = new WeekendShopManager();
        $idOfWeek = $weekendShopManager->calIdOfWeek();
        $confDataOfShop = WeekendShopUtil::getWeekendShopConf($idOfWeek);
        $userObj = EnUser::getUserObj();
        $bag = BagManager::getInstance()->getBag();

        if (($type == WeekendShopDef::RFR_TYPE_GOLD || $type == WeekendShopDef::RFR_TYPE_ITEM)
            && ($weekendShopManager->ifCanSysRfrGoodList() == true))
        {
            throw new FakeException('should sysRfrGoodList.');
        }
        switch ($type)
        {
            case WeekendShopDef::RFR_TYPE_GOLD:
                $rfrNumByPlayer = $weekendShopManager->getRfrNumByPlayer();
                $goldBase = $confDataOfShop[WeekendShopCsvDef::GOLD_BASE];
                $goldGrow = $confDataOfShop[WeekendShopCsvDef::GOLD_GROW];
                $goldMax = $confDataOfShop[WeekendShopCsvDef::GOLD_MAX];

                $goldNeed = $goldBase + $rfrNumByPlayer * $goldGrow;
                $goldNeed = $goldNeed - $goldMax > 0 ? $goldMax : $goldNeed;
                if ($goldNeed <= 0)
                {
                    throw new FakeException('goldNeed is zero');
                }
                if ($userObj->subGold($goldNeed, StatisticsDef::ST_FUNCKEY_WEEKENDSHOP_RFR_GOODLIST) == FALSE)
                {
                    throw new FakeException('weekendShop rfrGoodList sub gold failed.');
                }
                //只有金币刷新 增加次数
                $weekendShopManager->updRfrNumByPlayer();
                $weekendShopManager->rfrGoodList();
                break;
            case WeekendShopDef::RFR_TYPE_ITEM:
                $arrCostItem = $confDataOfShop[WeekendShopCsvDef::COST_ITEM]->toArray();
                $flag = false;
                if(empty($extra))
                {
                    throw new FakeException('itemTmplId is empty.');
                }
                foreach ($arrCostItem as $arrItemInfo)
                {
                    $itemTmplId = $arrItemInfo[0];
                    $itemTmplNum = $arrItemInfo[1];
                    if ($extra != $itemTmplId)
                    {
                        continue;
                    }
                    if ($bag->getItemNumByTemplateID($itemTmplId) < $itemTmplNum)
                    {
                        throw new FakeException('have not enough item, itemTmplId:%d, $itemTmplNum need:%d, you have:%d',
                            $itemTmplId, $itemTmplNum, $bag->getItemNumByTemplateID($itemTmplId));
                    }
                    if ($bag->deleteItembyTemplateID($itemTmplId, $itemTmplNum) == FALSE)
                    {
                        throw new FakeException("delete item from bag failed.");
                    }

                    $flag = true;
                    break;
                }
                if (false == $flag)
                {
                    throw new FakeException('itemTmplId is wrong, not fit cehua csv.');
                }
                $weekendShopManager->rfrGoodList();
                break;
            case WeekendShopDef::RFR_TYPE_SYS:
                if ($weekendShopManager->ifCanSysRfrGoodList() == false)
                {
                    throw new FakeException('cannot sysRfrGoodList because you have sysRfr today.');
                }
                $weekendShopManager->sysRfrGoodList();
                break;
            default:
                break;
        }

        $shopInfo = array();
        $shopInfo[WeekendShopDef::WEEKENDSHOP_GOODSLIST] = $weekendShopManager->getGoodList();
        $shopInfo[WeekendShopDef::WEEKENDSHOP_NUM] = $weekendShopManager->getWeekendShopNum();
        $shopInfo[WeekendShopDef::HAS_BUY_NUM] = $weekendShopManager->getHasBuyNum();
        $shopInfo[WeekendShopDef::RFR_NUM_BY_PLAYER] = $weekendShopManager->getRfrNumByPlayer();

        $weekendShopManager->update();
        $userObj->update();
        $bag->update();
        return $shopInfo;
    }

    public static function buyGood($goodId)
    {
        self::checkState();
        $weekendShopManager = new WeekendShopManager();
        $uid = RPCContext::getInstance()->getUid();
        $userObj = EnUser::getUserObj($uid);
        //总购买次数限制
        $weekendShopLimit = btstore_get()->VIP[$userObj->getVip()]['weekendShopLimit'];
        $hasBuyNum = $weekendShopManager->getHasBuyNum();
        if($hasBuyNum >= $weekendShopLimit)
        {
            throw new FakeException('hasBuyNum:%s is greater than weekendShopLimit:%d', $hasBuyNum, $weekendShopLimit);
        }

        $goodList = $weekendShopManager->getGoodList();
        if(!in_array($goodId, array_keys($goodList)))
        {
            throw new FakeException('goodId:%s not in goodList:%s', $goodId, $goodList);
        }
        $weekendShopManager->updHasBuyNum();
        $ret = $weekendShopManager->exchange($goodId);

        $weekendShopManager->update();
        return $ret;
    }

    public static function getShopNum()
    {
        $weekendShopManager = new WeekendShopManager();
        $shopNum = $weekendShopManager->getWeekendShopNum();
        $weekendShopManager->update();
        return $shopNum;
    }

    private static function checkState()
    {
        if(WeekendShopUtil::isWeekendShopOpen() == false)
        {
            throw new FakeException('weekendShop of this week is not open.');
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */