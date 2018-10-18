<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendWholeGroupReward.php 239674 2016-04-21 12:20:13Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendWholeGroupReward.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2016-04-21 12:20:13 +0000 (Thu, 21 Apr 2016) $
 * @version $Revision: 239674 $
 * @brief 
 *  
 **/

/**
 * 给全服玩家发奖励
 * 最初为开服活动"每日登陆回馈"用
 * 
 * @author wuqilin
 *
 */
class SendWholeGroupReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		//300金币 体力丹*2 耐力丹*2
		$arrReward = array(
				RewardType::GOLD => 200,
		        RewardType::ARR_ITEM_TPL => array( 20043 => 1),
		    
				PayBackDef::PAYBACK_TYPE => PayBackType::SYSTEM,
		);
		
		$timeStart = strtotime("2016-03-21 13:50:00");
		$timeEnd = strtotime("2016-03-22 23:59:59");
		
		$needOpenTime = strtotime("2016-03-20 23:59:59");
		
		if( strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME) > $needOpenTime)
		{
			Logger::info('server open:%s, ignore',  GameConf::SERVER_OPEN_YMD);
		}
		else 
		{
			$this->sendReward($arrReward, $timeStart, $timeEnd);
		}

		printf("send reward ok, group:%s\n", RPCContext::getInstance()->getFramework()->getGroup());
	}
	
	public function sendReward($arrReward, $timeStart, $timeEnd)
	{	
		
		$now = Util::getTime();
		
		$ret = PaybackLogic::getPayBackInfoByTime($timeStart, $timeEnd);
		if(empty($ret))
		{
			Logger::info('send payback');
			
			PayBackLogic::insertPayBackInfo($timeStart, $timeEnd, $arrReward, 1);
		
			
			if( $now >= $timeStart && $now <= $timeEnd)
			{
				RPCContext::getInstance ()->sendMsg ( array (0), PushInterfaceDef::REWARD_NEW, array() );
			}
			
		}
		else
		{
			Logger::info('already send payback');
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */