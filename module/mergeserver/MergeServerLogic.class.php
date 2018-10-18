<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServerLogic.class.php 138235 2014-10-31 09:35:44Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/MergeServerLogic.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-31 09:35:44 +0000 (Fri, 31 Oct 2014) $
 * @version $Revision: 138235 $
 * @brief 
 *  
 **/
 
 /**********************************************************************************************************************
 * Class       : MergeServerLogic
 * Description : 合服活动逻辑实现类
 * Inherit     : 
 **********************************************************************************************************************/
class MergeServerLogic
{
	/**
	 * getRewardInfo 获得合服活动奖励信息
	 * 
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return array 合服活动奖励信息，详见IMergeServer
	 */
	public static function getRewardInfo($uid)
	{
		Logger::trace('getRewardInfo uid:%d begin...', $uid);

		$ret = array();
		
		$ret["login"] = self::getRewardInfoByType($uid, MergeServerDef::MSERVER_TYPE_LOGIN);
		$ret["recharge"] = self::getRewardInfoByType($uid, MergeServerDef::MSERVER_TYPE_RECHARGE);
	
		Logger::trace('getRewardInfo uid:%d end...', $uid);
		return $ret;
	}
	
	/**
	 * receiveLoginReward 领取累积登陆奖励 
	 * 
	 * @param int $uid 用户id
	 * @param int $day 天数
	 * @static
	 * @access public
	 * @return string ok
	 */
	public static function  receiveLoginReward($uid, $day)
	{
		Logger::trace('receiveLoginReward uid:%d day:%d begin...', $uid, $day);

		if (FALSE === MergeServerUtil::checkEffect(MergeServerDef::MSERVER_TYPE_LOGIN))
		{
			throw new FakeException('receiveLoginReward failed because of merge server activity %s uid[%d] day[%d] is over.',
						   			 MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_LOGIN), $uid, $day);
		}
		
		// 获取用户合服信息
		$userMergeServerObj = MergeServerObj::getInstance($uid);
		
		$loginCount = $userMergeServerObj->getLoginCount();
		$arrGot = $userMergeServerObj->getLoginGotGroup();
		$arrCan = $userMergeServerObj->getLoginCanGroup($loginCount, $arrGot);
		
		if (in_array($day, $arrGot))
		{
			throw new FakeException('receiveLoginReward failed because of the day[%d] of user[%d] is in the arrGot range %s.', $day, $uid, $arrGot);
		}
		
		if (!in_array($day, $arrCan))
		{
			throw new FakeException('receiveLoginReward failed because of the day[%d] of user[%d] is not in the arrCan range %s.', $day, $uid, $arrCan);
		}
		
		$loginRewardConfig = MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_LOGIN);
		$currRewardConfig = $loginRewardConfig[$day];
		if (empty($currRewardConfig))
		{
			throw new FakeException('receiveLoginReward failed because of the config is empty day[%d], user[%d].', $day, $uid);
		}
		
		Logger::debug('receiveLoginReward uid:%d day:%d loginReward:%s.', $uid, $day, $currRewardConfig);
		
		RewardUtil::reward($uid, $currRewardConfig, StatisticsDef::ST_FUNCKEY_MERGE_SERVER_LOGIN_PRIZE);
		$userMergeServerObj->addLoginGotGroup($day);
		
		$userMergeServerObj->update();
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		Logger::trace('receiveLoginReward uid:%d day:%d end...', $uid, $day);
		return 'ok';
	}
	
	/**
	 * receiveRechargeReward 领取累积充值奖励 
	 * 
	 * @param int $uid 用户id
	 * @param int $num 档位
	 * @static
	 * @access public
	 * @return string ok
	 */
	public static function receiveRechargeReward($uid, $num)
	{
		Logger::trace('receiveRechargeReward uid:%d num:%d begin...', $uid, $num);

		if (FALSE === MergeServerUtil::checkEffect(MergeServerDef::MSERVER_TYPE_RECHARGE))
		{
			throw new FakeException('receiveRechargeReward failed because of merge server activity %s uid[%d] num[%d] is over.',
									MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_RECHARGE), $uid, $num);
		}

		// 获取用户合服信息
		$userMergeServerObj = MergeServerObj::getInstance($uid);
		
		$rechargeNum = $userMergeServerObj->getRechargeNum();
		$arrGot = $userMergeServerObj->getRechargeGotGroup();
		$arrCan = $userMergeServerObj->getRechargeCanGroup($rechargeNum, $arrGot);
		
		if (in_array($num, $arrGot))
		{
			throw new FakeException('receiveRechargeReward failed because of the num[%d] of user[%d] is in the arrGot range %s.', $num, $uid, $arrGot);
		}
		
		if (!in_array($num, $arrCan))
		{
			throw new FakeException('receiveRechargeReward failed because of the num[%d] of user[%d] is not in the arrCan range %s.', $num, $uid, $arrCan);
		}
		
		$rechargeRewardConfig = MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_RECHARGE);
		$currRewardConfig = $rechargeRewardConfig[$num]['reward'];
		if (empty($currRewardConfig)) 
		{
			throw new FakeException('receiveRechargeReward failed because of the config is empty num[%d], user[%d].', $num, $uid);
		}
		
		Logger::debug('receiveRechargeReward uid:%d num:%d rechargeReward:%s.', $uid, $num, $currRewardConfig);
		
		RewardUtil::reward($uid, $currRewardConfig, StatisticsDef::ST_FUNCKEY_MERGE_SERVER_RECHARGE_PRIZE);
		$userMergeServerObj->addRechargeGotGroup($num);
		
		$userMergeServerObj->update();
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		Logger::trace('receiveRechargeReward uid:%d num:%d end...', $uid, $num);
		return 'ok';
	}
	
	/**
	 * getRewardInfoByType 根据奖励类型，获得奖励信息
	 *
	 * @param int $uid 用户id
	 * @param int $rewardType 奖励类型
	 * @static
	 * @access private
	 * @throws
	 * @return array 奖励信息
	 */
	private static function getRewardInfoByType($uid, $rewardType)
	{
		$arrRet = array();
		
		if (FALSE === MergeServerUtil::checkEffect($rewardType))
		{
			Logger::debug('merge server activity %s uid[%d] is over.', MergeServerUtil::getStringDesc($rewardType), $uid);
			$arrRet['ret'] = 'over';
		}
		else 
		{
			Logger::debug('merge server activity %s uid[%d] is ok.', MergeServerUtil::getStringDesc($rewardType), $uid);
			$arrRet['ret'] = 'ok';
		}
		
		$userMergeServerObj = MergeServerObj::getInstance($uid);
		if (MergeServerDef::MSERVER_TYPE_LOGIN == $rewardType)
        {
        	$loginCount = $userMergeServerObj->getLoginCount();
        	$arrGot = $userMergeServerObj->getLoginGotGroup();
        	$arrCan = $userMergeServerObj->getLoginCanGroup($loginCount, $arrGot);
        	
            $arrRet['res'] = array( 'login' => $loginCount,
                                    'got' => $arrGot,
                                    'can' => $arrCan);
        }   
        else if (MergeServerDef::MSERVER_TYPE_RECHARGE == $rewardType)
        { 
        	$rechargeNum = $userMergeServerObj->getRechargeNum();
        	$arrGot = $userMergeServerObj->getRechargeGotGroup();
        	$arrCan = $userMergeServerObj->getRechargeCanGroup($rechargeNum, $arrGot);
        	
            $arrRet['res'] = array( 'recharge' => $rechargeNum,
                                    'got' => $arrGot,
                                    'can' => $arrCan);
        }   
		else
		{
			throw new FakeException('getRewardInfo unknown reward type[%d] uid[%d]', $rewardType, $uid);
		}
	
		return $arrRet;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */