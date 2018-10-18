<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RfrVipLevel.php 128990 2014-08-25 10:45:44Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/RfrVipLevel.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-25 10:45:44 +0000 (Mon, 25 Aug 2014) $
 * @version $Revision: 128990 $
 * @brief 
 *  
 **/
class RfrVipLevel extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $newMinVipLevel = 11;
        $uid = intval($arrOption[0]);
        if(empty($uid))
        {
            echo "empty uid\n";
            return;
        }
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $needMinGold = btstore_get()->VIP[$newMinVipLevel]['totalRecharge'];
        $chargeGoldNum = UserLogic::getSumGoldByUid($uid);
        $vip = EnUser::getUserObj($uid)->getVip();
        echo "now vip $vip vip $newMinVipLevel need mingold $needMinGold chargegold $chargeGoldNum\n";
        if($vip >= $newMinVipLevel)
        {
            return;
        }
        if($chargeGoldNum < $needMinGold)
        {
            return;
        }
        $orderId = "TEST_" . time() . "$uid";
        $gold = 1;
        RPCContext::getInstance()->executeTask($uid, 'user.addGold4BBpay', array($uid, $orderId, $gold));
        Logger::info('add order:%s.add gold %d for user %d', $orderId,$gold,$uid);
        echo "user $uid add gold done\n";
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */