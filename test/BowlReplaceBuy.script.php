<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BowlReplaceBuy.script.php 210362 2015-11-18 02:12:39Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/BowlReplaceBuy.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-11-18 02:12:39 +0000 (Wed, 18 Nov 2015) $
 * @version $Revision: 210362 $
 * @brief 
 *  
 **/
class BowlReplaceBuy extends BaseScript
{
	/**
	 * 聚宝盆前后端计算时间方式不一致导致3天后无法购买，此为补买脚本
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if( count( $arrOption ) < 2)
		{
			echo "invalid param\n";
			return;
		}
		
		if ( FALSE == EnActivity::isOpen(ActivityName::BOWL) )
		{
			printf("Activity Bowl is not Open.\n");
			return;
		}
		
		$uid = intval($arrOption[0]);
		$type = intval($arrOption[1]);
		
		if ( $type <= 0 || $type > BowlConf::BOWL_TYPE_NUM )
		{
			printf("param err. type: %d.\n", $type);
			return;
		}
		
		
		
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		
		$chargeStartTime = strtotime('2015-01-26 00:00:00');
		$chargeEndTime = strtotime('2015-01-29 12:00:00');
		
		$recharge = EnUser::getRechargeGoldByTime($chargeStartTime, $chargeEndTime, $uid);
		
		$need = intval($conf['data'][$type][BowlDef::BOWL_BUY_NEED]);
		if ($recharge < $need)
		{
			printf("user %d recharge not enough, recharge %d , need %d.\n",$uid,$recharge,$need);
			return;
		}
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		
		$bowlObj = BowlObj::getInstance($uid);
		if (TRUE == $bowlObj->hasBuy($type))
		{
			printf("user %d has bought bowl %d.\n",$uid,$type);
			return;
		}
		
		$ret = UserDao::getUserByUid($uid, array('uid', 'pid','uname', 'level', 'vip') );
		if ( empty($ret) )
		{
			printf("not found uid:%d\n", $uid);
			return;
		}
		
		printf("pid:%d, uid:%d, uname:%s, vip:%d, level:%d, type:%d (y|n)\n", 
			$ret['pid'], $ret['uid'], $ret['uname'], $ret['vip'], $ret['level'], $type);
		$cin = trim(fgets(STDIN));
		if( $cin != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		Util::kickOffUser($uid);
		
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_PID, $ret['pid']);
		
		$userObj = EnUser::getUserObj($uid);
		
		$cost = intval($conf['data'][$type][BowlDef::BOWL_BUY_COST]);
		if (FALSE == $userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_BOWL_COST))
		{
			printf("user %d has no enough gold to buy bowl %d, need %d.\n",$uid,$type,$cost);
			return;
		}
		
		Logger::info('uid:%d buy bowl. type:%d', $uid, $type);
		
		$userObj->update();
		
		$bowlObj->buy($type);
		$bowlObj->update();
		
		printf("User %d, Type %d:bowl replace buy ok.\n",$uid,$type);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */