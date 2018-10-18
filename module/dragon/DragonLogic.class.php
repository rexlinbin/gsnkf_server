<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DragonLogic.class.php 160587 2015-03-09 06:36:25Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/DragonLogic.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-03-09 06:36:25 +0000 (Mon, 09 Mar 2015) $$
 * @version $$Revision: 160587 $$
 * @brief 
 *  
 **/
class DragonLogic
{
    public static function getMap($uid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->getMap();
        $dragon->save();
        return $ret;
    }

    public static function getUserBf($uid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->getVaBf();
        $dragon->save();
        return $ret;
    }

    public static function move($uid, $posid)
    {
        $dragon = DragonManager::getInstance($uid);
        if(!$dragon->canMove($posid))
        {
            return;
        }
        $ret = $dragon->move($posid);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        return $ret;
    }

    public static function doublePrize($uid, $eventId)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->doublePrize($eventId);
        EnUser::getUserObj($uid)->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        return $ret;
    }

    public static function fight($uid, $eventId)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->fight($eventId);
        $dragon->save();
        return $ret;
    }

    public static function skip($uid, $posid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->skip($posid);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function reset($uid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->reset($uid);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function answer($uid, $eventId, $answer)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->answer($eventId, $answer);
        $dragon->save();
        return $ret;
    }

    public static function bribe($uid, $eventId)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->bribe($eventId);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function oneKey($uid, $eventId)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->oneKey($eventId);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function buyAct($uid, $index, $num)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->buyAct($index, $num);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function buyHp($uid, $index)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->buyHp($index);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        return $ret;
    }

    public static function autoMove($uid, $arrPosid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->autoMove($arrPosid);
        $dragon->save();
        return $ret;
    }

    public static function aiDo($uid, $floor, $actIndex)
    {
        $dragon = DragonManager::getInstance($uid);
        if(!$dragon->canAiDo($floor, $actIndex))
        {
            return;
        }
        $ret = $dragon->aiDo($floor, $actIndex);
        $dragon->save();
        EnUser::getUserObj($uid)->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        return $ret;
    }

    public static function trial($uid)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->resetTrial($uid);
        $dragon->save();
        return $ret;
    }

    public static function buyGood($uid, $eventId, $goodIndex)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->buyGood($eventId, $goodIndex);
        EnUser::getUserObj($uid)->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        $dragon->save();
        return $ret;
    }

    public static function contribute($uid, $eventId, $goodId)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->contribute($eventId, $goodId);
        EnUser::getUserObj($uid)->update();
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        $dragon->save();
        return $ret;
    }

    public static function fightBoss($uid, $eventId, $armyIndex)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->fightBoss($eventId, $armyIndex);
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        $dragon->save();
        return $ret;
    }

    public static function bossDirectWin($uid, $eventId, $armyIndex)
    {
        $dragon = DragonManager::getInstance($uid);
        $ret = $dragon->fightBoss($eventId, $armyIndex, true);
        $bag = BagManager::getInstance()->getBag();
        $bag->update();
        EnUser::getUserObj($uid)->update();
        $dragon->save();
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */