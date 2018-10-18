<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DoneTaskOnTrustDevice.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/DoneTaskOnTrustDevice.hook.php $
 * @author $Author: ShiyuZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief 
 *  
 **/
class DoneTaskOnTrustDevice
{
    function execute ($arrResponse)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrResponse;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ($uid < FrameworkConfig::MIN_UID)
		{
			return $arrResponse;
		}
		$method = RPCContext::getInstance()->getFramework()->getMethod();
        switch($method)
        {
            case 'divine.divi':
                TrustDevice::doneTask($uid, TrustDevice::TASK_DIVINE);
                break;
            case 'acopy.atkGoldTree':
            case 'acopy.atkGoldTreeByGold':
                TrustDevice::doneTask($uid, TrustDevice::TASK_ATK_GOLDTREE);
                break;
            case 'acopy.doBattle'://经验宝物副本、熊猫副本
                TrustDevice::doneTask($uid, TrustDevice::TASK_ATK_EXPTREA);
                break;
            case 'reward.receiveByRidArr':
            case 'reward.receiveReward':
                TrustDevice::doneTask($uid, TrustDevice::TASK_RECEIVE_REWARD);
                break;
            case 'guild.reward':
                TrustDevice::doneTask($uid, TrustDevice::TASK_GUILD_REWARD);
                break;
            case 'friend.loveFriend':
                TrustDevice::doneTask($uid, TrustDevice::TASK_LOVE_FRIEND);
                break;
            case 'sign.getNormalInfo':
                TrustDevice::doneTask($uid, TrustDevice::TASK_DAILY_SIGN);
                break;
            case 'ncopy.doBattle':
            case 'ncopy.sweep':
            case 'mineral.delayPitDueTime':
            case 'tower.defeatSpecialTower':
            case 'mineral.capturePit':
            case 'mineral.grabPitByGold':
            case 'mineral.grabPit':
            case 'mineral.robGuards':
//             case 'copyteam.doneTeamBattle'://军团组队 协助次数消耗体力  
            case 'citywar.mendCity':
            case 'citywar.ruinCity':
                TrustDevice::doneTask($uid, TrustDevice::TASK_CONSUME_EXEC);
                break;
            case 'arena.challenge':
            case 'compete.contest':
            case 'fragseize.seizeRicher':
            case 'fragseize.quickSeize':
                TrustDevice::doneTask($uid, TrustDevice::TASK_CONSUME_STAM);
                break;
        }
		return $arrResponse;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */