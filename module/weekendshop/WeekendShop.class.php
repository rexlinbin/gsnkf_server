<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: WeekendShop.class.php 136135 2014-10-14 10:06:02Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/weekendshop/WeekendShop.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-14 10:06:02 +0000 (Tue, 14 Oct 2014) $$
 * @version $$Revision: 136135 $$
 * @brief 
 *  
 **/
class WeekendShop implements IWeekendShop
{

    public function getInfo()
    {
        Logger::trace("WeekendShop::getInfo start.");
        $res = WeekendShopLogic::getInfo();
        Logger::trace("WeekendShop::getInfo end.");
        return $res;
    }

    public function rfrGoodList($type, $extra=NULL)
    {
        Logger::trace("WeekendShop::rfrGoodList start, type:%d.", $type);
        if (empty($type))
        {
            throw new FakeException('type is empty');
        }
        if (!in_array($type, WeekendShopDef::$arrRfrType))
        {
            throw new FakeException('invalid rfr type:$tpye', $type);
        }
        $res = WeekendShopLogic::rfrGoodList($type, $extra);
        Logger::trace("WeekendShop::rfrGoodList end, type:%d.", $type);
        return $res;
    }

    public function buyGood($goodId)
    {
        Logger::trace("WeekendShop::buyGood start, goodId:%d.", $goodId);
        if (empty($goodId))
        {
            throw new FakeException('goodId is empty');
        }
        $res = WeekendShopLogic::buyGood($goodId);
        Logger::trace("WeekendShop::buyGood end, goodId:%d.", $goodId);
        return $res;
    }

    public function getShopNum()
    {
        Logger::trace('WeekendShop::getShopNum start.');
        $res = WeekendShopLogic::getShopNum();
        Logger::trace('WeekendShop::getShopNum end.');
        return $res;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */