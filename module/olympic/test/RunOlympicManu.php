<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

/*
	阶段		该阶段工作				如果出错，如何处理							
	0		清数据					重新跑一遍
	1		就改个状态				重新跑一遍
	2		分组，产生32强；16强准备	重新跑一遍
	3		16强；8强准备				将rank＝16的改成32，重跑
	4		8强；4强准备
	5		4强；半决赛准备
	6		2强；决赛准备
	7		决赛；发奖准备
	8		发奖
	
	
 */
class RunOlympicManu extends BaseScript
{
	

	
	protected function executeScript($arrOption)
	{
		$todayStatus = OlympicLogic::getTodayStatus();
		if($todayStatus == OlympicWeekStatus::NOTHING_DAY)
		{
			printf("today no olympic\n");
			return;
		}
		
		$logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
		$stageInfo = $logInst->getCurStageInfo();
		
		$curStageId = -1;
		$curStageStatus = OlympicStageStatus::END;
		if(!empty($stageInfo))
		{
			$curStageId = $logInst->getCurStage();
			$curStageStatus = $logInst->getCurStageStatus();
		}
		
		$now = Util::getTime();
		
		printf("time:%s, stageId:%d, stageStatus:%d, shouldStartTime:%s\n", 
				date('Y-m-d H:i:s', $now), 
				$curStageId, $curStageStatus, 
				date('Y-m-d H:i:s', self::getStageShouldTime($curStageId)) );
		
	
		if( $now < self::getStageShouldTime(OlympicStage::AFTER_OLYMPIC) + 60  )
		{
			printf("in normal olympic time, cant run now\n");
			return;
		}
		
		printf("run?(y|n)\n");
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("not run\n");
			return;
		}
		
		$startStageId = $curStageStatus == OlympicStageStatus::END ?  $curStageId + 1 : $curStageId;
		
		self::fixStage($startStageId, $curStageStatus);
		
		for($stageId = $startStageId; $stageId <= OlympicStage::AFTER_OLYMPIC; $stageId++)
		{
			self::runStage($stageId);
		}
		
		printf("done\n");
	}
	
	
	public static function fixStage($stageId, $stageStatus)
	{
		if( $stageStatus != OlympicStageStatus::PREPARE  )
		{
			$msg = sprintf('fix status. stageId:%d, status:%d', $stageId, $stageStatus);
			printf('%s\n', $msg);
			Logger::info('%s', $msg);
			$logInst = OlympicLog::getInstance(OlympicLogType::BATTLE_PROGRESS);
			$logInst->updStageStatus($stageId, OlympicStageStatus::PREPARE);
			
			if ($stageId == OlympicStage::OLYMPIC_GROUP) 
			{
				$logInst->unsetOneStageForRepair(OlympicStage::OLYMPIC_GROUP);
			}
		}
				
		if( $stageId >= OlympicStage::SIXTEEN_FINAL && $stageId <= OlympicStage::FINAL_MATCH  )
		{
		    $dateYmd = OlympicLogic::getCurRoundStartTime();
		    $vaLogInfo = array(OlympicLogDef::VA_INFO_ATKRES=>array());
		    OlympicDao::updateOlympicLog($dateYmd, $stageId,
		            $vaLogInfo);
		    
		    
			//处理：打了一半，有的人排名已经修改的问题
			$nextRank = OlympicDef::$next[$stageId];
			$curRank = $nextRank * 2;
			
			$arrSignInfo = OlympicDao::getAllSignUpUser();
			$arrWrongUid = array();
			foreach( $arrSignInfo as $value )
			{
				if ( $value[OlympicRankDef::FIELD_FINAL_RANK] == $nextRank )
				{
					$arrWrongUid[] = $value[OlympicRankDef::FIELD_UID];
				}
			}
			
			$msg = sprintf('there are %d user rank:%d', count($arrWrongUid), $nextRank );
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			
			if (  !empty($arrWrongUid) )
			{
				$msg = sprintf('set all rank:%d to rank:%d', $nextRank, $curRank);
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				
				$arrValue = array(
						OlympicRankDef::FIELD_FINAL_RANK => $curRank,
				);
				$data = new CData();
				$data->update('t_olympic_rank')
						->set($arrValue)
						->where( OlympicRankDef::FIELD_FINAL_RANK, '=', $nextRank)
						->query();
			}
			
			
		}
		
	}
	
	public static function runStage($stageId)
	{
		$msg = sprintf('run stage:%d', $stageId);
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
		if( $stageId == OlympicStage::PRE_OLYMPIC )
		{
			return OlympicLogic::startPreOlympicStage();
		}
		else if($stageId == OlympicStage::PRELIMINARY_MATCH )
		{
			return OlympicLogic::startPreliminary();
		}
		else if( $stageId == OlympicStage::OLYMPIC_GROUP )
		{
			return OlympicLogic::startGroup();
		}
		else if( $stageId >= OlympicStage::SIXTEEN_FINAL && $stageId <= OlympicStage::FINAL_MATCH)
		{
			return OlympicLogic::startFinal( $stageId );
		}
		else if( $stageId == OlympicStage::AFTER_OLYMPIC )
		{
			return OlympicLogic::startAfterOlympic();
		}
		else
		{
			throw new InterException('invalid stage:%d', $stageId);
		}
	}
	
	public static function getStageShouldTime($stageId)
	{
		if( $stageId == OlympicStage::PRE_OLYMPIC )
		{
			return OlympicLogic::getPreOlympicStartTime();
		}
		else if($stageId == OlympicStage::PRELIMINARY_MATCH )
		{
			return OlympicLogic::getPreLiminaryMatchStartTIme();
		}
		else if( $stageId == OlympicStage::OLYMPIC_GROUP )
		{
			return OlympicLogic::getPreLiminaryMatchStartTIme() + OlympicStage::PRELIMINARY_MATCH_TIME;
		}
		else if( $stageId >= OlympicStage::SIXTEEN_FINAL && $stageId <= OlympicStage::AFTER_OLYMPIC)
		{
			$startTime = OlympicLogic::getPreLiminaryMatchStartTIme() 
					+ OlympicStage::PRELIMINARY_MATCH_TIME
					+ OlympicStage::PRELIMINARY_FIGHT_GAP;
			$i = OlympicStage::SIXTEEN_FINAL;
			while($i < $stageId)
			{
				$startTime += OlympicStage::$ARR_FIGHT_DURATION[$i];
				$i++;
			}
		
			return $startTime;
		}
		else
		{
			throw new InterException('invalid stage:%d', $stageId);
		}
	} 
	
	
	
	

	
	
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */