<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnTower.class.php 256516 2016-08-15 10:53:23Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/EnTower.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-15 10:53:23 +0000 (Mon, 15 Aug 2016) $
 * @version $Revision: 256516 $
 * @brief 
 *  
 **/
class EnTower
{
    public static function getTopActivityInfo()
    {
        $uid = RPCContext::getInstance()->getUid();
        $status = 'invalid';
        if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
        {
            return array('status'=>$status, 'extra'=>array('reset_num'=>0, 'can_fail_num'=>0));
        }
        $status = 'ok';
        $towerInst = MyTower::getInstance($uid);
        $num = $towerInst->getCanFailNum();
        return array(
                'status' => $status,
                'extra' => array(
                        'reset_num' => $towerInst->getResetNum(),
                        'can_fail_num' => $towerInst->getCanFailNum()
                        ));
    }
    
    public static function getHellTopActivityInfo()
    {
        $uid = RPCContext::getInstance()->getUid();
        $status = 'invalid';
        if(EnSwitch::isSwitchOpen(SwitchDef::TOWER,$uid) == FALSE)
        {
            return array('status'=>$status, 'extra'=>array('reset_num'=>0, 'can_fail_num'=>0));
        }
        if ( FALSE == TowerLogic::isHellTowerShopOpen($uid) )
        {
            return array('status'=>$status, 'extra'=>array('reset_num'=>0, 'can_fail_num'=>0));
        }
        $status = 'ok';
        $towerInst = MyTower::getInstance($uid);
        $num = $towerInst->getHellCanFailNum();
        return array(
            'status' => $status,
            'extra' => array(
                'reset_num' => $towerInst->getHellResetNum(),
                'can_fail_num' => $towerInst->getHellCanFailNum()
            ));
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */