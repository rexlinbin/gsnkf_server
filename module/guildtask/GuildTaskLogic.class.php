<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTaskLogic.class.php 145559 2014-12-11 10:50:56Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildtask/GuildTaskLogic.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-11 10:50:56 +0000 (Thu, 11 Dec 2014) $
 * @version $Revision: 145559 $
 * @brief 
 *  
 **/
class GuildTaskLogic
{
	
	public static function getTaskInfo($uid)
	{
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		
		return $guildTaskInst->getTaskInfo();
	}
	
	public static function refTask($uid)
	{
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		$taskBak = $guildTaskInst->getTaskDetail();
		$refNum = $guildTaskInst->getRefNum();
		
		$allGuildTask = btstore_get()->GUILD_TASK;
		$guildTasKLimit = btstore_get()->GUILD_TASK_LIMIT;
		
		$aaa=intval($guildTasKLimit[GuildTaskDef::BTS_REF_GOLD] + $refNum* $guildTasKLimit[GuildTaskDef::BTS_INCREF_GOLD][0] );
		$needGold = $aaa > $guildTasKLimit[GuildTaskDef::BTS_INCREF_GOLD][1]? $guildTasKLimit[GuildTaskDef::BTS_INCREF_GOLD][1]:$aaa;
		if ($needGold < 0)
		{
			throw new ConfigException( 'sub minus gold' );
		}
		
		Logger::debug('needGold is: %d', $needGold);
		$user = EnUser::getUserObj($uid);
		if (!$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_TASK_REF))
		{
			throw new FakeException( 'lack gold' );
		}
		
		$guildTaskInst->addRefNum(1);
		
		$needRandomPos = array();
		foreach ($taskBak as $pos => $oneTaskDetail)
		{
			$taskConf = $allGuildTask[$oneTaskDetail['id']];
			if (//$oneTaskDetail['num'] <= $taskConf[GuildTaskDef::BTS_FINISH_COND][2]
			$oneTaskDetail['status'] == GuildTaskDef::NOT_ACCEPT )
			{
				$needRandomPos[] = $pos;
			}
		}
		
		if ( empty( $needRandomPos ) )
		{
			throw new FakeException( 'no pos need to refresh: %s', $taskBak);
		}
		
		$randomResult = $guildTaskInst->randomTask($needRandomPos);
		$newTaskArr = $taskBak ;//+ $randomResult;
		
		foreach ( $newTaskArr as $newPos => $newTask )
		{
			if ( isset( $randomResult[$newPos] ) )
			{
				$newTaskArr[$newPos] = $randomResult[$newPos];
			}
		}
		Logger::debug('taskbak arr: %s, taskrandom arr: %s, newtask arr: %s',$taskBak,$randomResult, $newTaskArr);
		
		$user->update();
		$guildTaskInst->setTask($newTaskArr);
		$guildTaskInst->update();
		
		//又不发到奖励中心了$guildTaskInst->handleFinishTask($taskBak);

		return $newTaskArr;
	}
	
	public static function acceptTask($uid, $pos, $TTid)
	{
		$guildTaskLimit = btstore_get()->GUILD_TASK_LIMIT;
		
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		$task = $guildTaskInst->getTaskDetail();
		$forgiveTime = $guildTaskInst->getForgiveTime();
		$taskNum = $guildTaskInst->getTaskNum();
		
		foreach ($task as $one => $info)
		{
			if ($info['status'] == GuildTaskDef::ACCEPT)
			{
				throw new FakeException( 'already accept one: %s', $task );
			}
		}
		
		if ( $taskNum >= $guildTaskLimit[GuildTaskDef::BTS_MAXNUM] )
		{
			throw new FakeException( 'already do max num: %d tasks', $taskNum );
		}
		
		if ( Util::getTime()<= $forgiveTime + $guildTaskLimit[GuildTaskDef::BTS_FORGIVE_CD] )
		{
			throw new FakeException( 'in cd, for give time: %d, cd time:%d', $forgiveTime,$guildTaskLimit[GuildTaskDef::BTS_FORGIVE_CD] );
		}
		
		if ( !isset( $task[$pos] )|| $task[$pos]['id'] != $TTid || $task[$pos]['status'] != GuildTaskDef::NOT_ACCEPT )
		{
			throw new FakeException( 'canot accept task pos: %d, ttid: %d, all tar task: %s',$pos, $TTid,$task );
		}
		
		$task[$pos]['status'] = GuildTaskDef::ACCEPT;
		$guildTaskInst->setTask($task);
		
		$guildTaskInst->update();
	}
	
	public static function forgiveTask($uid, $pos, $TTid)
	{
		$guildTaskLimit = btstore_get()->GUILD_TASK_LIMIT;
		
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		$task = $guildTaskInst->getTaskDetail();

		if ( !isset( $task[$pos] )|| $task[$pos]['id'] != $TTid || $task[$pos]['status'] == GuildTaskDef::NOT_ACCEPT )
		{
			throw new FakeException( 'canot forgive task pos: %d, ttid: %d, all tar task: %s',$pos, $TTid,$task );
		}
		
		$task[$pos]['status'] = GuildTaskDef::NOT_ACCEPT;
		$task[$pos]['num'] = 0;
		$guildTaskInst->setTask($task);
		$guildTaskInst->setForgiveTime();
		
		$guildTaskInst->update();
	}
	
	public static function doneTask($uid, $pos, $TTid, $useGold)
	{
		$guildTaskLimit = btstore_get()->GUILD_TASK_LIMIT;
		$allGuildTask = btstore_get()->GUILD_TASK;
		
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		$task = $guildTaskInst->getTaskDetail();
		
		$taskNum = $guildTaskInst->getTaskNum();
		if ( $taskNum >= $guildTaskLimit[GuildTaskDef::BTS_MAXNUM] )
		{
			throw new FakeException( 'already do max num: %d tasks', $taskNum );
		}
		
		if ( !isset( $task[$pos] )|| $task[$pos]['id'] != $TTid || $task[$pos]['status'] != GuildTaskDef::ACCEPT )
		{
			throw new FakeException( 'canot done task pos: %d, ttid: %d, all tar task: %s',$pos, $TTid,$task );
		}
		
		if ( $task[$pos]['num'] < $allGuildTask[$TTid][GuildTaskDef::BTS_FINISH_COND][2]&&$useGold ==0 )
		{
			throw new FakeException( 'num: %d, not reach condition; %s', $task[$pos]['num'], $allGuildTask[$TTid][GuildTaskDef::BTS_FINISH_COND] );
		}
		
		
		$user = EnUser::getUserObj($uid);
		if ( $useGold == 1 )
		{
			$needGold = $allGuildTask[$TTid][GuildTaskDef::BTS_RIT_FINISH_GOLD];
			if ($needGold <= 0)
			{
				throw new ConfigException( 'rit finish task need gold: %d', $needGold );
			}
			if (!$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_TASK_RITNOW))
			{
				throw new FakeException( 'lack gold' );
			}
		}
		
		$newPosTask = $guildTaskInst->randomTask(array($pos));
		$task[$pos] = $newPosTask[$pos];
		$guildTaskInst->addTaskNum(1);
		$guildTaskInst->setTask($task);
		
		$reward = $allGuildTask[$TTid][GuildTaskDef::BTS_REWARD];
		Logger::debug('rewardArr before: %s',$reward);
		RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_GUILD_TASK);
		
		$guildTaskInst->update();
		BagManager::getInstance()->getBag($uid)->update();
		$user->update();
		
		return $task;
	}
	
	public static function handIn($uid,$pos,$TTid,$itemIdArr)
	{
		
		$guildtaskLimit = btstore_get()->GUILD_TASK_LIMIT;
		$itemIdNumArr = array();
		
		$itemMgr = ItemManager::getInstance();
		$itemArr = $itemMgr->getItems($itemIdArr);
		$guildtaskInst = GuildTaskObj::getInstance($uid);
		$finishTaskNum = $guildtaskInst->getTaskNum();
		if ( $finishTaskNum >= $guildtaskLimit[GuildTaskDef::BTS_MAXNUM] )
		{
			throw new FakeException( 'reach day max finish num' );
		}
		
		$taskDetail = $guildtaskInst->getTaskDetail();
		$taskid = $guildtaskInst->getIndoingTaskId();
		$guildtaskConf = btstore_get()->GUILD_TASK[$taskid];
		
		if ( !isset( $taskDetail[$pos])
		||$taskDetail[$pos]['id'] != $TTid
		||$taskDetail[$pos]['status'] != GuildTaskDef::ACCEPT
		)
		{
			throw new FakeException( 'invalid request,task detail: %s', $taskDetail );
		}
		
		$totalNum = 0;
		foreach ( $itemArr as $index => $oneItem )
		{
			if ( empty( $oneItem ) )
			{
				throw new FakeException( 'no such item index: %d ',$index );
			}
			
			$itemNum = 0;
			$oneItemId = $oneItem->getItemID();
			$itemType = $oneItem->getItemType();
			if (ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType] != GuildTaskType::$typeBagArr[$guildtaskConf[GuildTaskDef::BTS_TYPE]])
 			{
 				throw new FakeException( 'itemid: %d not the taskid: %s need',$oneItemId,$taskid );
 			}
 			if ( $guildtaskConf[GuildTaskDef::BTS_FINISH_COND][0]!= 0 )
 			{
 				$subtype = $oneItem->getType();
 				if ($subtype != $guildtaskConf[GuildTaskDef::BTS_FINISH_COND][0])
 				{
 					throw new FakeException( 'subtype err' );
 				}
 			}
 			$itemQulity = $oneItem->getItemQuality();
 			if ( $itemQulity != $guildtaskConf[GuildTaskDef::BTS_FINISH_COND][1] )
 			{
 				throw new FakeException( 'item qulity err need: %d, provide %d', $guildtaskConf[GuildTaskDef::BTS_FINISH_COND][1],$itemQulity);
 			}
 			
 			$itemNum = $oneItem->getItemNum();
 			$totalNum += $itemNum;
 			$itemIdNumArr[$oneItemId] = $itemNum;
 			unset($itemArr[$index]);
		}
		
		$alreadyDoNum = $guildtaskInst->getIndoingNum($pos);
		$leftNeedNum = $guildtaskConf[GuildTaskDef::BTS_FINISH_COND][2] - $alreadyDoNum;
		if ($leftNeedNum <= 0 )
		{
			throw new FakeException('no need to handin');
		}
		if ( $totalNum < $leftNeedNum )
		{
			throw new FakeException( 'lack num, need %d, provide: %d ',$leftNeedNum,$totalNum );
		}
		
		foreach ( $itemIdNumArr as $itemId => $itemhadNum )
		{
			$ret = self::doTaskOnce($uid, $guildtaskConf[GuildTaskDef::BTS_TYPE], $itemId, $itemhadNum);
		}
		
		
		$bag = BagManager::getInstance()->getBag($uid);
		$bag->update();
		$user = EnUser::getUserObj($uid);
		$user->update();
		
		$guildtaskInst->update();
		
	}

	
	public static function doTaskOnce($uid,$type,$id,$num)
	{
		Logger::debug('do task args are: %d,%d,%d,%d',$uid,$type,$id,$num);
		$guildTaskLimit = btstore_get()->GUILD_TASK_LIMIT;
		$allGuildTask = btstore_get()->GUILD_TASK;
		
		$guildTaskInst = GuildTaskObj::getInstance($uid);
		$task = $guildTaskInst->getTaskDetail();
		
		$inDoingPos = $guildTaskInst->getIndoingTaskPos();
		$inDoningTaskId = $guildTaskInst->getIndoingTaskId();
		if( empty( $inDoningTaskId ) )
		{
			Logger::debug('no task indoing');
			return ;
		}
		
		$inDoingTaskInfo = $allGuildTask[$inDoningTaskId];
		
		$inDoingType = $inDoingTaskInfo[GuildTaskDef::BTS_TYPE];
		if ( $type != $inDoingType )
		{
			Logger::debug('invalid type: %d', $type);
			return;
		}
		
		if ( $task[$inDoingPos]['num'] >= $inDoingTaskInfo[GuildTaskDef::BTS_FINISH_COND][2] )
		{
			Logger::debug('already do num: %d >= finish need num: %d ',$task[$inDoingPos]['num'] ,$inDoingTaskInfo[GuildTaskDef::BTS_FINISH_COND][2] );
			return;
		}
		
		$user = EnUser::getUserObj($uid);
		if ( $inDoingTaskInfo[GuildTaskDef::BTS_NEED_CITY] ==1)
		{
			$city = EnCityWar::getGuildCityId($uid);
			if 	( empty( $city ) )
			{
				Logger::debug('have no city');
				return;
			}	
		}
		
		$needNum = $inDoingTaskInfo[GuildTaskDef::BTS_FINISH_COND][2]-$task[$inDoingPos]['num'];
		$doneNum = $needNum<$num? $needNum:$num;
		$needExePerTime = $allGuildTask[$inDoningTaskId][GuildTaskDef::BTS_NEED_EXE];
		if ( $needExePerTime < 0 )
		{
			Logger::fatal('err config, consume exe: %d, taskid: %d',$needExePerTime,$inDoningTaskId);
			return;
		}
		
		//========专门给修复城池做的过滤，修复城池自己就扣了体力了
		if( $type != GuildTaskType::RUIN_CITY && $type != GuildTaskType::MEND_CITY )
		{
			if (!$user->subExecution( $doneNum*$needExePerTime ))
			{
				throw new FakeException('lack exe');
			}
		}
		
		$doResult = false;
		switch ($type)
		{
			case GuildTaskType::HANDIN_ARM:
			case GuildTaskType::HANDIN_PROP:
			case GuildTaskType::HANDIN_TREASURE:
				$doResult = self::_handInItem($uid,$inDoningTaskId,$id,$doneNum);
				break;
			case GuildTaskType::BASE:
				$doResult = self::_general($inDoningTaskId,$id);
				break;
			case GuildTaskType::RUIN_CITY:
			case GuildTaskType::MEND_CITY:
				$doResult = true;
				//$doResult = self::_city($uid,$inDoningTaskId,$doneNum);
				break;
			default:
				throw new FakeException( 'invalid type:%d',$type );
				
		}
		
		if (!$doResult)
		{
			Logger::debug('doresult is false,type:%d',$type);
			return;
		}
		
		$guildTaskInst->doTaskOnce($inDoingPos,$doneNum);
		
		return 'doOnce';
		//更新是在最初始的发起方法 有两个地方
	}
	
	public static function _handInItem($uid,$inDoningTaskId,$id,$num)
	{
// 		Logger::debug('item id handin is %d',$id);
// 		//上交物品的单独分离
 		$guildtask = btstore_get()->GUILD_TASK;
 		$finishCond = $guildtask[$inDoningTaskId][GuildTaskDef::BTS_FINISH_COND];
		
		
 		$item = ItemManager::getInstance()->getItem($id);
// 		$itemTplId = $item->getItemTemplateID();
		
// 		$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			
//  		if (ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType] != GuildTaskType::$typeBagArr[$guildtask[$inDoningTaskId][GuildTaskDef::BTS_TYPE]])
//  		{
//  			Logger::fatal( 'itemid: %d not the taskid: %s need',$id,$inDoningTaskId );
//  			return false;
//  			//throw new FakeException( 'itemid: %d not the taskid: %s need',$id,$inDoningTaskId );
//  		}
		
//  		if ( ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType] != BagDef::BAG_PROPS )
//  		{
//  			$itemSubType = $item->getType();
//  			if( $itemSubType != $finishCond[0] )
//  			{
//  				Logger::fatal( 'need subtype: %d, type from front: %d',$finishCond[0],$itemSubType );
//  				return false;
//  				//throw new FakeException( 'need subtype: %d, type from front: %d',$finishCond[0],$itemSubType );
//  			}
//  		}
 		
//  		$itemQuality = $item->getItemQuality();
//  		if ( $itemQuality != $finishCond[1] )
//  		{
//  			Logger::fatal( 'need qulity: %d, provide: %d',$finishCond[1],$itemQuality );
//  			return false;
//  			//throw new FakeException( 'need qulity: %d, provide: %d',$finishCond[1],$itemQuality );
//  		}
 		
		$bag = BagManager::getInstance()->getBag($uid);
		
		if ( !$bag->decreaseItem($id, $num) )
		{
			return false;
		}
		
		return true;
	}
	
	public static function _general($inDoningTaskId,$id)
	{
		//做这些任务的时候，只要跟我当前的任务的完成要求不相符就直接返回就可以
		
		$guildtask = btstore_get()->GUILD_TASK;
		
		if ($guildtask[$inDoningTaskId][GuildTaskDef::BTS_FINISH_COND][1] != $id )
		{
			Logger::debug('inDoningTaskId: %d,id: %d,taskinfo:%s',$inDoningTaskId,$id,$guildtask[$inDoningTaskId][GuildTaskDef::BTS_FINISH_COND]);
			return false;
		}
		
		return true;

	}
	
	
	public static function canGuildtask($uid)
	{
		$guildId = EnGuild::getGuildId($uid);
		if (empty( $guildId ) )
		{
			Logger::debug('user have no guidid');
			return false;
		}
		
		$guildLimit = btstore_get()->GUILD_TASK_LIMIT;
		
		$guildHallLv = EnGuild::getBuildLevel($uid,GuildDef::GUILD);
		$userLv = EnUser::getUserObj($uid)->getLevel();
		
		if($guildHallLv < $guildLimit[GuildTaskDef::BTS_GUILD_LV]
		||$userLv < $guildLimit[GuildTaskDef::BTS_USER_LV])
		{
			Logger::debug( 'userlv or guildhalllv not satisfy the cond %d, %d',$guildHallLv,$userLv );
			return false;
		}
		
		return true;
	}
	
//	
// 	public static function _city($inDoningTaskId,$id)
// 	{
// 		$guildtask = btstore_get()->GUILD_TASK;
		
// 		if ($guildtask[$inDoningTaskId][GuildTaskDef::BTS_FINISH_COND][1] <= $id )
// 		{
// 			return false;
// 		}
// 		return true;

// 	}

		
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */