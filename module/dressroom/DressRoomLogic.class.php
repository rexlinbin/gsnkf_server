<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoomLogic.class.php 140177 2014-11-15 09:50:42Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/DressRoomLogic.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-15 09:50:42 +0000 (Sat, 15 Nov 2014) $$
 * @version $$Revision: 140177 $$
 * @brief 
 *  
 **/
class DressRoomLogic
{
    public static function getDressRoomInfo()
    {
        $dressRoomManager = DressRoomManager::getInstance();
        $dressRoomManager->refresh();

        $ret = array();
        $ret['arr_dress'] = $dressRoomManager->getArrDress();
        $ret['cur_dress'] = $dressRoomManager->getCurDress();
        $dressRoomManager->update();
        return $ret;
    }

    public static function activeDress($itemTmpId)
    {
        $dressRoomManager = DressRoomManager::getInstance();
        if (DressRoomUtil::isItemTmpIdAviableDress($itemTmpId) == false)
        {
            throw new FakeException('invalid dressId:%d', $itemTmpId);
        }
        $dressRoomManager->updActiveStatusOfDress($itemTmpId);
        $dressRoomManager->update();
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return 'ok';
    }

    public static function changeDress($itemTmpId)
    {
        $uid = RPCContext::getInstance()->getUid();
        $dressRoomManager = DressRoomManager::getInstance($uid);
        if (DressRoomUtil::isItemTmpIdAviableDress($itemTmpId) == false)
        {
            throw new FakeException('invalid dressId:%d', $itemTmpId);
        }
        $dressRoomManager->updCurDress($itemTmpId);

        //改变user va_user里的玩家形象
        $userObj = EnUser::getUserObj($uid);
        $userObj->setDressInfo($itemTmpId, 1);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        $dressRoomManager->update();
        $userObj->update();
        return 'ok';
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */