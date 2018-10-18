<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SetGuildWar.php 157523 2015-02-06 10:01:49Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/test/SetGuildWar.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-06 10:01:49 +0000 (Fri, 06 Feb 2015) $
 * @version $Revision: 157523 $
 * @brief 
 *  
 **/
 
class SetGuildWar extends BaseScript
{
	/**
	 * php ~/rpcfw/lib/ScriptRunner.php  -f /home/pirate/rpcfw/module/world/guildwar/test/SetGuildWar.php   
	 * 
	 * --op=setAll,setStatus
	 * --sess=1  
	 * --startTime=0   5:延迟5分钟后开始，  18:00:今天18点开始
	 * --supportRewardDelay=60
	 * --promotionRewardDelay=60
	 * --crossPrepareDelay=60
	 * --crossMachine=192.168.1.122
	 * --round=1
	 * --status=2
	 * --teamId=1
	 * 
	 */
	
	public function help()
	{
		printf("param:\n");
		printf("op:			操作类型: setAll, setStatus\n");
		printf("sess:		如果设置，表示修改届数\n");
		printf("startTime:	开始时间\n");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{
		// 命令行格式
		global $argc, $argv;
		$arrLongOp = array
		(
				'op:',
				'sess::',
				'startTime::',
				'supportRewardDelay::',
				'promotionRewardDelay::',
				'crossPrepareDelay::',
				'crossMachine::',
				'round::',
				'status::',
				'teamId::',
		);
		$arrRet = getopt('f:c:s:g:d:h:', $arrLongOp);

		// 参数的默认值
		$op = '';
		$sess = -1;
		$startTime = -1;
		$supportRewardDelay = 60;
		$promotionRewardDelay = 60;
		$crossPrepareDelay = 60;
		$crossMachine = '192.168.1.122';
		$round = GuildWarRound::INVALID;
		$status = GuildWarStatus::NO;
		$teamId = 0;
		
		if (count($arrOption) < 1)
		{
			$this->help();
			return;
		}

		// 获得命令行参数
		foreach ($arrRet as $key => $value)
		{
			switch ($key)
			{
				case 'op':
					$op = $value;
					printf("set op:%s\n", $op);
					break;
				case 'sess':
					$sess = intval($value);
					printf("set sess:%s\n", $sess);
					break;
				case 'startTime':
					$now = time();
					$startTime = $value;
					if (is_numeric($startTime))
					{
						$startTime = ceil($now / 60.0) * 60 + intval($startTime * 60);
					}
					else if (is_string($startTime))
					{
						$startTime = strtotime(sprintf('%s %s:00', date('Y-m-d'), $startTime));
					}
					else
					{
						printf("invalid startTime:%s\n", $startTime);
						return;
					}
					printf("set startTime:%s\n", date('Y-m-d H:i:s', $startTime));
					break;
				case 'supportRewardDelay':
					$supportRewardDelay = intval($value);
					printf("set supportRewardDelay:%d\n", $supportRewardDelay);
					break;
				case 'promotionRewardDelay':
					$promotionRewardDelay = intval($value);
					printf("set promotionRewardDelay:%d\n", $promotionRewardDelay);
					break;
				case 'crossPrepareDelay':
					$crossPrepareDelay = intval($value);
					printf("set crossPrepareDelay:%d\n", $crossPrepareDelay);
					break;
				case 'crossMachine':
					$crossMachine = $value;
					printf("set crossMachine:%s\n", $crossMachine);
					break;
				case 'round':
					$round = intval($value);
					printf("set round:%d\n", $round);
					break;
				case 'status':
					$status = intval($value);
					printf("set status:%d\n", $status);
					break;
				case 'teamId':
					$teamId = intval($value);
					printf("set teamId:%d\n", $teamId);
					break;
				default:
					break;
			}
		}


		// 执行具体的操作
		switch ($op)
		{
			case 'setAll':
				
				// 获取所有服的host和db
				$arrInnerHost = array();
				$arrDb = array();
				$arrRet = self::getAllHostDbInfo($sess);
				if(empty($arrRet))
				{
					printf("getAllHostDbInfo failed\n");
					return;
				}
				$arrInnerHost = $arrRet['arrHost'];
				$arrDb = $arrRet['arrDb'];

				// 清除跨服机器上进度表的信息
				self::cleanProcedue(GuildWarUtil::getCrossDbName(), $sess);
				
				// 清除每个服上的膜拜表
				foreach ($arrDb as $aDb)
				{
					self::cleanTemple($aDb, $sess);
				}
				
				// 设置跨服机器上活动表的配置
				$ret = self::setStartTime(GuildWarUtil::getCrossDbName(), $startTime, $sess);
				
				// 设置所有其他服对应的db中的活动表配置
				$ret = self::setAllDbStartTime($arrDb, $startTime, $sess);
					
				if (empty($ret))
				{
					return;
				}

				$arrRoundDelay = $ret[1][GuildWarCsvTag::TIME_CONFIG];

				// 获得cron时间配置
				$arrTaskInner = self::genArrTaskInner($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay);
				$arrTaskCross = self::genArrTaskCross($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay, $crossPrepareDelay);

				// 获得cron字符串
				$arrCronInner = self::genArrCron(GuildWarField::INNER, $arrTaskInner);
				$arrCronCross = self::genArrCron(GuildWarField::CROSS, $arrTaskCross);

				printf("crontab for inner machine:\n");
				foreach($arrCronInner as  $arr)
				{
					foreach($arr as $t => $value)
					{
						printf("%s\n", $value['cron']);
					}
				}
				printf("crontab for cross machine:\n");
				foreach($arrCronCross as $arr)
				{
					foreach($arr as $t => $value)
					{
						printf("%s\n", $value['cron']);
					}
				}

				printf("\n===========================>set contab to machine:\n");
				self::setCronToAllMachine($arrCronInner, $arrCronCross, $arrInnerHost, $crossMachine);
				break;
				
			case 'setStatus':

				$procedure = GuildWarProcedureObj::getInstance($sess);
				$teamObj = $procedure->getTeamObj($teamId);

				$curRound = $teamObj->getCurRound();
				$curStatus = $teamObj->getCurStatus();
				$curSubRound = $teamObj->getCurSubRound();
				$curSubStatus = $teamObj->getCurSubStatus();

				$msg = sprintf('curRound:%d, curStatus:%d, subRound:%d', $curRound, $curStatus, $curSubRound);
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				
				$teamRoundObj = $teamObj->getTeamRound($curRound);
				$teamRoundObj->setStatus($status);

				break;
			case 'reset':
				break;
			default:
				printf("invalid op:%s\n", $op);
				return;
		}

		printf("done\n");
	}

	/**
	 * 从zookeeper获取所有服的host和db
	 * 
	 * @param int $sess
	 * @return array
	 */
	public static function getAllHostDbInfo($sess)
	{
		if ($sess < 0)
		{
			$confObj = GuildWarConfObj::getInstance(GuildWarField::CROSS);
			$sess = $confObj->getSession();
		}
		
		// 所有组的数据
		$allTeamInfo = GuildWarUtil::getAllTeamData($sess);
		
		$zk = new Zookeeper(ScriptConf::ZK_HOSTS);

		$arrHost = array();
		$arrDb = array();
		printf("\n===========================>all team server Info:\n");
		foreach ($allTeamInfo as $teamId => $arrServerId)
		{
			foreach ($arrServerId as $serverId)
			{
				$path = sprintf('/card/lcserver/lcserver#game%03d', $serverId);
				if ($zk->exists($path) == FALSE)
				{
					$msg = sprintf('not found game:%s, teamId:%d, path:%s', $serverId, $teamId, $path);
					printf("%s\n", $msg);
					Logger::info('%s', $msg);
					return;
				}
				$ret = $zk->get($path);
				$serverInfo = Util::amfDecode($ret);
				$host = $serverInfo['host'];
				if (isset($arrHost[$host]))
				{
					$arrHost[$host][] = $serverId;
				}
				else
				{
					$arrHost[$host] = array($serverId);
				}

				$arrDb[] = $serverInfo['db'];
				printf("team:%d, serverId:%d, host:%s, db:%s\n", $teamId, $serverId, $host, $serverInfo['db']);
			}
		}

		Logger::info('allTeam:%s', $allTeamInfo);
		Logger::info('arrHost:%s, arrDb:%s', $arrHost, $arrDb);
		return array
		(
				'arrHost' => $arrHost,
				'arrDb' => $arrDb,
		);
	}

	/**
	 * 设置cron到机器上
	 * 
	 * @param array $arrCronInner
	 * @param array $arrCronCross
	 * @param array $arrInnerHost
	 * @param array $crossMachine
	 */
	public static function setCronToAllMachine($arrCronInner, $arrCronCross, $arrInnerHost, $crossMachine)
	{
		printf("\nset crontab to all machine:\n");
		foreach ($arrInnerHost as $host => $arrServerId)
		{
			$msg = sprintf('set inner crontab to host:%s, arrServerId:%s', $host, implode(',', $arrServerId));
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			self::setCronToMachine($arrCronInner, $host, GuildWarField::INNER);
		}
		self::setCronToMachine($arrCronCross, $crossMachine, GuildWarField::CROSS, !isset($arrInnerHost[$crossMachine]));
	}

	/**
	 * 生成cron的字符串
	 * 
	 * @param string $field
	 * @param array $arrTask
	 * @return array
	 */
	public static function genArrCron($field, $arrTask)
	{
		$arrCron = array();
		foreach ($arrTask as $op => $arr)
		{
			$arrTimeRound = array();
			foreach ($arr as $value)
			{
				$t = date('i H', $value['time']);
				if (isset($arrTimeRound[$t]))
				{
					$arrTimeRound[$t]['round'][] = $value['round'];
				}
				else
				{
					$arrTimeRound[$t] = array('round' => array($value['round']));
				}
			}
			foreach ($arrTimeRound as $t => $value)
			{
				if ($field == GuildWarField::CROSS)
				{
					$arrTimeRound[$t]['cron'] = sprintf('%s * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/GuildWarEntry.php %s %s', $t, $field, $op);
				}
				else
				{
					$arrTimeRound[$t]['cron'] = sprintf('%s * * * $BTSCRIPT $SCRIPT_ROOT/GuildWarEntry.php %s %s', $t, $field, $op);
				}
			}
				
			$arrCron[$op] = $arrTimeRound;
		}

		return $arrCron;
	}
	
	/**
	 * 生成服内cron的时间配置
	 * 
	 * @param int $startTime
	 * @param array $arrRoundDelay
	 * @param array $supportRewardDelay
	 * @param array $promotionRewardDelay
	 * @return array
	 */
	public static function genArrTaskInner($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay)
	{
		$arrCron = array();
		foreach (GuildWarRound::$FinalsRound as $round)
		{
			$roundStartTime = $startTime + $arrRoundDelay[$round][0] + $supportRewardDelay;
			$arrCron['cheerReward'][] = array('round' => $round, 'time' => $roundStartTime);
		}
		return $arrCron;
	}
	
	/**
	 * 生成跨服cron的时间配置
	 * 
	 * @param int $startTime
	 * @param array $arrRoundDelay
	 * @param array $supportRewardDelay
	 * @param array $promotionRewardDelay
	 * @param array $crossPrepareDelay
	 */
	public static function genArrTaskCross($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay, $crossPrepareDelay)
	{
		$arrCron = array();
		foreach (GuildWarRound::$ValidRound as $round)
		{
			if ($round == GuildWarRound::SIGNUP) 
			{
				continue;
			}
			
			$roundStartTime = $startTime + $arrRoundDelay[$round][0];
			$arrCron['runRound'][] = array('round' => $round, 'time' => $roundStartTime);
			
			if ($round != GuildWarRound::AUDITION)
			{
				$arrCron['checkReward'][] = array('round' => $round, 'time' => $roundStartTime + $supportRewardDelay + $crossPrepareDelay);
			}
			
			$lastRoundStartTime = $roundStartTime;
		}
		
		$arrCron['fightReward'][] = array('round' => GuildWarRound::ADVANCED_2 ,'time' => $lastRoundStartTime + $supportRewardDelay + $crossPrepareDelay + $promotionRewardDelay);
		return $arrCron;
	}


	/**
	 * 设置一组db中的活动表t_activity_conf，并且更新memcache
	 * 
	 * @param array $arrDb
	 * @param int $startTime
	 * @param int $sess
	 * @return array
	 */
	public static function setAllDbStartTime($arrDb, $startTime, $sess)
	{
		if (empty($arrDb))
		{
			return array();
		}

		foreach ($arrDb as $dbName)
		{
			$ret = self::setStartTime($dbName, $startTime, $sess);
			if (empty($ret))
			{
				return array();
			}
		}
		
		return $ret;
	}

	/**
	 * 设置活动表t_activity_conf的开始时间并且更新memcache
	 * 
	 * @param string $dbName
	 * @param int $startTime
	 * @param int $sess
	 * @return array
	 */
	public static function setStartTime($dbName, $startTime, $sess)
	{
		try
		{
			$data = new CData();
			if (!empty($dbName))
			{
				$data->useDb($dbName);
			}
			
			// 获得活动表中最新的跨服战信息
			$ret = $data->select(ActivityDef::$ARR_CONF_FIELD)->from('t_activity_conf')
						->where('name', '==', ActivityName::GUILDWAR)
						->orderBy('version', FALSE)->limit(0, 1)->query();
			
			
			if (empty($ret))
			{
				$msg = sprintf("WARN: no guildwar conf in db, please config it first");
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				return array();
			}
			$ret = $ret[0];

			// 更新数据库
			if ($sess >= 0)
			{
				$ret['va_data'][1]['id'] = $sess;
			}
			$version = Util::getTime();
			$conf = array
			(
					'name' => $ret['name'],
					'version' => $version,
					'start_time' => $startTime,
					'end_time' => $ret['end_time'],
					'need_open_time' => $ret['need_open_time'],
					'str_data' => $ret['str_data'],
					'va_data' => $ret['va_data'],
			);
			if (!empty($dbName))
			{
				$data->useDb($dbName);
			}
			$data->insertOrUpdate('t_activity_conf')->values($conf)->query();

			// 更新mem
			if (empty($dbName))
			{
				$ret = ActivityConfLogic::updateMem();
			}
			else
			{
				$key = ActivityConfLogic::genMcKey4Front();
				McClient::setDb($dbName);
				McClient::get($key);

				McClient::setDb($dbName);
				McClient::del($key);

				McClient::setDb($dbName);
				McClient::del(ActivityConfLogic::genMcKey(ActivityName::GUILDWAR));
			}
				
			$msg = sprintf('set guildwar startTime:%s, dbName:%s', date('Ymd H:i:s',$startTime), $dbName);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			return $conf['va_data'];
		}
		catch (Exception $e)
		{
			$msg = sprintf('setStartTime faield. dbName:%s, msg:%s', $dbName, $e->getMessage());
			printf("%s\n", $msg);
		}
		return array();
	}

	/**
	 * 清除进度表数据
	 * 
	 * @param string $dbName
	 * @param int $sess
	 * @param boolean $cleanTempleData 是否清除膜拜表数据
	 */
	public static function cleanProcedue($dbName, $sess)
	{
		$data = new CData();			
		if (!empty($dbName))
		{
			$data->useDb($dbName);
		}
		
		$arrValue = array('session' => 0);
		$ret = $data->update( 't_guild_war_procedure' )
					->set($arrValue)
					->where('session', '=', $sess)
					->query();
	}
	
	/**
	 * 清除服内膜拜表数据
	 * 
	 * @param string $dbName
	 * @param int $sess
	 */
	public static function cleanTemple($dbName, $sess)
	{
		$data = new CData();
		if (!empty($dbName))
		{
			$data->useDb($dbName);
		}
		$arrValue = array('session' => time());
		$ret = $data->update('t_guild_war_inner_temple')
					->set($arrValue)
					->where('session', '=', $sess)
					->query();
	}

	/**
	 * 设置cron到一个Host上
	 * 
	 * @param array $arrCron
	 * @param string $host
	 * @param string $field
	 * @param bool $delOld
	 */
	public static function setCronToMachine($arrCron, $host, $field, $delOld = TRUE)
	{
		printf("set %s crontab to host:%s\n", $field, $host);
		$tmpFile = '/tmp/crontab.guildwar';
		$bakFile = sprintf('/tmp/crontab.%s', date('Ymd-H-i-s'));

		if (empty($host))
		{
			exec(sprintf('crontab -l > %s && cat %s ', $bakFile, $bakFile), $arrRet);
		}
		else
		{
			exec(sprintf('ssh %s "crontab -l > %s && cat %s "', $host, $bakFile, $bakFile), $arrRet);
		}

		$arrBefore = array();
		$arrAfter = array();
		$foundTag = false;
		foreach ($arrRet as $key => $cronLine)
		{
			if ($delOld && preg_match(sprintf('/GuildWarEntry.php/'), $cronLine))
			{
				continue;
			}
			if ($foundTag == false)
			{
				$arrBefore[] = $cronLine;
			}
			else
			{
				$arrAfter[] = $cronLine;
			}

			if ($cronLine == sprintf('#guildwar %s', $field))
			{
				$foundTag = true;
			}
		}

		if ($foundTag == false)
		{
			$arrBefore[] = '';
			$arrBefore[] = sprintf('#guildwar %s',$field);
		}

		foreach ($arrCron as $op => $arr)
		{
			foreach($arr as $value)
			{
				$arrBefore[] = $value['cron'];
			}
		}
		
		$allCron = array_merge($arrBefore, $arrAfter);
		if (!empty($allCron[count($allCron) - 1]))
		{
			$allCron[] = "";
		}

		$allCronStr = implode("\n", $allCron);
		file_put_contents($tmpFile, $allCronStr);

		if (empty($host))
		{
			exec(sprintf('crontab %s', $tmpFile));
		}
		else
		{
			exec(sprintf('scp %s %s:%s && ssh %s "crontab %s"', $tmpFile, $host, $tmpFile, $host, $tmpFile));
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */