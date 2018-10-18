<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCarnivalFix.php 198245 2015-09-11 14:57:31Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldCarnivalFix.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 14:57:31 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198245 $
 * @brief 
 *  
 **/
 
class WorldCarnivalFix extends BaseScript
{
	protected function executeScript($arrOption)
	{
		// 是否提交
		$commit = FALSE;
		if (isset($arrOption[0]) && strtolower($arrOption[0]) == 'do') 
		{ 
			printf("commit mode\n");
			$commit = TRUE;
		}
		else 
		{
			printf("check mode\n");
		}
		
		// 获取配置
		RPCContext::getInstance()->getFramework()->setDb(WorldCarnivalUtil::getCrossDbName());
		$curVersion = ActivityConfLogic::getTrunkVersion();
		ActivityConfLogic::doRefreshConf($curVersion, TRUE, FALSE);
		$confObj = WorldCarnivalConfObj::getInstance(WorldCarnivalField::CROSS);
		$session = $confObj->getSession();
		if (empty($session))
		{
			Logger::info('not in any session, return');
			return ;
		}
		Logger::info('WorldCarnivalFix : cur session[%d]', $session);
		
		// 进度管理对象
		$procedureObj = WorldCarnivalProcedureObj::getInstance($session, $confObj->getActivityStartTime());
		$curRound = $procedureObj->getCurRound();
		Logger::info('WorldCarnivalFix : cur round[%d]', $curRound);
		$arrBattleRecord = $procedureObj->getBattleRecord($curRound);
		Logger::info('WorldCarnivalFix : cur record[%s]', $arrBattleRecord);
		
		// 从战报中获取每个玩家已经输了多少次
		$mapPos2LoseTimes = array();
		foreach ($arrBattleRecord as $subRound => $aBattleRecord)
		{
			$atkPos = $aBattleRecord['attacker_pos'];
			if (!isset($mapPos2LoseTimes[$atkPos]))
			{
				$mapPos2LoseTimes[$atkPos] = 0;
			}
			$defPos = $aBattleRecord['defender_pos'];
			if (!isset($mapPos2LoseTimes[$defPos]))
			{
				$mapPos2LoseTimes[$defPos] = 0;
			}
			$losePos = $aBattleRecord['result'] > 0 ? $defPos : $atkPos;
			++$mapPos2LoseTimes[$losePos];
		}
		Logger::info('WorldCarnivalFix : cur lose time info[%s]', $mapPos2LoseTimes);
				
		// 检测玩家数据是否正确
		foreach ($mapPos2LoseTimes as $aPos => $loseTimes)
		{
			$aFighterInfo = $confObj->getFighterByPos($aPos);
			$fighterObj = WorldCarnivalCrossUserObj::getInstance($aFighterInfo['server_id'], $aFighterInfo['pid'], $confObj->getActivityStartTime());
			
			// 检测失败次数是否一致
			if ($loseTimes != $fighterObj->getLoseTimes()) 
			{
				Logger::warning('WorldCarnivalFix : lose times diff for pos[%d], serverId[%d], pid[%d], record lose[%d], real lose[%d]', $aPos, $aFighterInfo['server_id'], $aFighterInfo['pid'], $loseTimes, $fighterObj->getLoseTimes());
				$fighterObj->setLoseTimesForTest($loseTimes);
			}
			else 
			{
				Logger::info('WorldCarnivalFix : lose times same for pos[%d], serverId[%d], pid[%d], lose[%d]', $aPos, $aFighterInfo['server_id'], $aFighterInfo['pid'], $loseTimes);
			}
			
			// 检测名次是否整数
			if ($procedureObj->getCurStatus() == WorldCarnivalProcedureStatus::FIGHTING 
				|| $loseTimes >= WorldCarnivalConf::$mapMaxLoseTimes[$curRound]) 
			{
				$correctRank = WorldCarnivalConf::$curRank[$curRound];
			}
			else 
			{
				$correctRank = WorldCarnivalConf::$winRank[$curRound];
			}
			if ($correctRank != $fighterObj->getRank()) 
			{
				$fighterObj->setRank($correctRank);
				Logger::warning('WorldCarnivalFix : rank diff for pos[%d], serverId[%d], pid[%d], correct rank[%d], real rank[%d]', $aPos, $aFighterInfo['server_id'], $aFighterInfo['pid'], $correctRank, $fighterObj->getRank());
			}
			else 
			{
				Logger::info('WorldCarnivalFix : rank same for pos[%d], serverId[%d], pid[%d], rank[%d]', $aPos, $aFighterInfo['server_id'], $aFighterInfo['pid'], $correctRank);
			}
			
			// 如果提交，则直接update
			if ($commit) 
			{
				$fighterObj->update();
			}
		}
		
		printf("ok, please check log!\n");
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */