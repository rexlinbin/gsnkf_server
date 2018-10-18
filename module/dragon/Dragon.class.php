<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: Dragon.class.php 160587 2015-03-09 06:36:25Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/Dragon.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-03-09 06:36:25 +0000 (Mon, 09 Mar 2015) $$
 * @version $$Revision: 160587 $$
 * @brief 
 *  
 **/
class Dragon implements IDragon
{

    function getMap()
    {
        Logger::trace('Dragon::getMap start.');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::getMap end.');
        return DragonLogic::getMap($uid);
    }

    function getUserBf()
    {
        Logger::trace('Dragon::getUserBf start.');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::getUserBf end.');
        return DragonLogic::getUserBf($uid);
    }

    function move($posid)
    {
        Logger::trace('Dragon::move start, $posid:%d.', $posid);
        $uid = RPCContext::getInstance()->getUid();
        EnActive::addTask(ActiveDef::DRAGON);
        Logger::trace('Dragon::move end, $posid:%d. ', $posid);
        return DragonLogic::move($uid, $posid);
    }

    function doublePrize($eventId)
    {
        Logger::trace('Dragon::doublePrize start, $eventId:%d.', $eventId);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::doublePrize end, $eventId:%d. ', $eventId);
        return DragonLogic::doublePrize($uid, $eventId);
    }

    function onekey($eventId)
    {
        Logger::trace('Dragon::onekey start, $eventId:%d', $eventId);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::onekey end, $eventId:%d', $eventId);
        return DragonLogic::oneKey($uid, $eventId);
    }

    function buyHp($index)
    {
        Logger::trace('Dragon::buyHp start');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::buyHp end');
        return DragonLogic::buyHp($uid, $index);
    }

    function buyAct($index, $num)
    {
        Logger::trace('Dragon::buyAct start.');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::buyAct end.');
        return DragonLogic::buyAct($uid, $index, $num);
    }

    function bribe($eventId)
    {
        Logger::trace('Dragon::bribe start, $eventId:%d', $eventId);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::bribe end, $eventId:%d', $eventId);
        return DragonLogic::bribe($uid, $eventId);
    }

    function answer($eventId, $answer)
    {
        Logger::trace('Dragon::answer start, eventId:%d, $answer:%s', $eventId, $answer);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::answer end, eventId:%d, $answer:%s', $eventId, $answer);
        return DragonLogic::answer($uid, $eventId, $answer);
    }

    function goon($posid)
    {

    }

    function fight($eventId)
    {
        Logger::trace('Dragon::fight start, eventId:%d', $eventId);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::fight end, eventId:%d', $eventId);
        return DragonLogic::fight($uid, $eventId);
    }

    function skip($posid)
    {
        Logger::trace('Dragon::skip start, eventId:%d', $posid);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::skip end, eventId:%d', $posid);
        return DragonLogic::skip($uid, $posid);
    }

    function reset()
    {
        Logger::trace('Dragon::reset start.');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::reset end.');
        return DragonLogic::reset($uid);
    }

    function autoMove($arrPosid)
    {
        Logger::trace('Dragon::autoMove start, arrPosid:%s', $arrPosid);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::autoMove end, arrPosid:%s', $arrPosid);
        return DragonLogic::autoMove($uid, $arrPosid);
    }

    function aiDo($floor, $actIndex)
    {
        Logger::trace('Dragon::aiDo start, floor:%d, $actIndex:%d', $floor, $actIndex);
        $uid = RPCContext::getInstance()->getUid();
        EnActive::addTask(ActiveDef::DRAGON);
        Logger::trace('Dragon::aiDo end, floor:%d, $actIndex:%d', $floor, $actIndex);
        return DragonLogic::aiDo($uid, $floor, $actIndex);
    }

    function trial()
    {
        Logger::trace('Dragon::trial start.');
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::trial end.');
        return DragonLogic::trial($uid);
    }

    function buyGood($eventId, $goodIndex)
    {
        Logger::trace('Dragon::buyGood start, eventId:%d, goodIndex:%d.', $eventId, $goodIndex);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::buyGood end, eventId:%d, goodIndex:%d.', $eventId, $goodIndex);
        return DragonLogic::buyGood($uid, $eventId, $goodIndex);
    }

    function contribute($eventId, $goodId)
    {
        Logger::trace('Dragon::contribute start. eventId:%d goodId:%d', $eventId, $goodId);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::contribute end. eventId:%d goodId:%d', $eventId, $goodId);
        return DragonLogic::contribute($uid, $eventId, $goodId);
    }

    function fightBoss($eventId, $armyIndex)
    {
        Logger::trace('Dragon::fightBoss start. eventId:%d armyIndex:%d', $eventId, $armyIndex);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::fightBoss end. eventId:%d armyIndex:%d', $eventId, $armyIndex);
        return DragonLogic::fightBoss($uid, $eventId, $armyIndex);
    }

    function bossDirectWin($eventId, $armyIndex)
    {
        Logger::trace('Dragon::bossDirectWin start. eventId:%d armyIndex:%d', $eventId, $armyIndex);
        $uid = RPCContext::getInstance()->getUid();
        Logger::trace('Dragon::bossDirectWin end. eventId:%d armyIndex:%d', $eventId, $armyIndex);
        return DragonLogic::bossDirectWin($uid, $eventId, $armyIndex);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */