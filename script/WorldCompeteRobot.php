<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteRobot.php 203218 2015-10-19 12:13:15Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/WorldCompeteRobot.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-19 12:13:15 +0000 (Mon, 19 Oct 2015) $
 * @version $Revision: 203218 $
 * @brief 
 *  
 **/
 
class WorldCompeteRobot extends BaseScript
{
	private function printUsage()
	{
		printf("Usage:\n");
		printf("btscript game001 WorldCompeteRobot team num 跨服比武加机器人\n");
	}
	
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 1) 
		{
			$this->printUsage();
			exit(0);
		}
		
		$teamId = intval($arrOption[0]);
		$num = intval($arrOption[1]);
		$arrServerId = WorldCompeteUtil::getArrServerIdByTeamId($teamId);
		$arrServer2Db = ServerInfoManager::getInstance()->getArrDbName($arrServerId);
		
		//计算每个服拉取的数量
		$arrServerId2Num = array();
		for ($i = 0; $i < $num; $i++)
		{
			foreach ($arrServerId as $serverId)
			{
				if (!isset($arrServerId2Num[$serverId])) 
				{
					$arrServerId2Num[$serverId] = 0;
				}
				$arrServerId2Num[$serverId]++;
				$i++;
			}
		}
		
		//遍历各个服拉取N个用户,插入跨服数据库
		$honor = 0;
		$level = 80;
		$data = new CData();
		foreach ($arrServerId2Num as $serverId => $aNum)
		{
			$arrField = array('uid', 'pid', 'uname', 'vip', 'level', 'htid', 'fight_force', 'dress');
			$arrUser = EnUser::getArrUserBasicInfoWithLowerEqualLevel($aNum, $level, $arrField, 0, array(), $arrServer2Db[$serverId]);
			foreach ($arrUser as $key => $value)
			{
				$honor ++;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_SERVER_ID] = $serverId;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_TEAM_ID] = $teamId;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_MAX_HONOR] = $honor;
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_UPDATE_TIME] = Util::getTime();
				$arrUser[$key][WorldCompeteCrossUserField::FIELD_VA_EXTRA][WorldCompeteCrossUserField::DRESS] = $value['dress'];
				unset($arrUser[$key]['dress']);
				$data->insertOrUpdate(WorldCompeteDao::getCrossTable($teamId))->values($arrUser[$key])->useDb(WorldCompeteUtil::getCrossDbName())->query();
				$initInfo = array
				(
						WorldCompeteInnerUserField::FIELD_PID => $value['pid'],
						WorldCompeteInnerUserField::FIELD_SERVER_ID => $serverId,
						WorldCompeteInnerUserField::FIELD_UID => $value['uid'],
						WorldCompeteInnerUserField::FIELD_ATK_NUM => 0,
						WorldCompeteInnerUserField::FIELD_SUC_NUM => 0,
						WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM => 0,
						WorldCompeteInnerUserField::FIELD_REFRESH_NUM => 0,
						WorldCompeteInnerUserField::FIELD_WORSHIP_NUM => 0,
						WorldCompeteInnerUserField::FIELD_MAX_HONOR => $honor,
						WorldCompeteInnerUserField::FIELD_CROSS_HONOR => $honor,
						WorldCompeteInnerUserField::FIELD_HONOR_TIME => Util::getTime(),
						WorldCompeteInnerUserField::FIELD_UPDATE_TIME => Util::getTime(),
						WorldCompeteInnerUserField::FIELD_REWARD_TIME => 0,
						WorldCompeteInnerUserField::FIELD_VA_EXTRA => array(),
				);
				$data->insertOrUpdate('t_world_compete_inner_user')->values($initInfo)->useDb($arrServer2Db[$serverId])->query();
			}
		}
		
		echo "done\n";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */