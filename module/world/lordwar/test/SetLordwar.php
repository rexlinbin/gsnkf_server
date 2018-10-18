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
class SetLordwar extends BaseScript
{

	
	/**
	 * php ~/rpcfw/lib/ScriptRunner.php  -f /home/pirate/rpcfw/module/world/lordwar/test/SetLordwar.php   --op=setAll --sess=1  --startTime=0 --supportRewardDelay=60
	 */
	public function help()
	{
		printf("param:\n");
		printf("op:			操作类型: set, setAll\n");
		printf("sess:		如果设置，表示修改届数\n");
		printf("startTime:	开始时间\n");
	}
	protected function executeScript ($arrOption)
	{
		
		global $argc, $argv;
		
		$arrLongOp = array(
				'op:', 
				'sess::', 
				'startTime::', 
				'crossMachine::', 
				'supportRewardDelay::', 
				'promotionRewardDelay::',
				'crossPrepareDelay::',
				'crossMachine::',
				'round::',
				'status::',
				'teamId::',
				);
		$arrRet = getopt('f:c:s:g:d:h:', $arrLongOp);

		$op = '';
		$sess = -1;
		$startTime = -1;
		$supportRewardDelay = 60;
		$promotionRewardDelay = 60;
		$crossPrepareDelay = 60;
		$crossMachine = '192.168.1.122';
		$round = LordwarRound::OUT_RANGE;
		$status = LordwarStatus::NO;
		$teamId = 0;
		if( count($arrOption) < 1 )
		{
			$this->help();
			return;
		}
		
		foreach( $arrRet as $key => $value )
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
					//设定延迟时间
					$startTime = $value;
					if( is_numeric($startTime) )
					{
						$startTime = ceil($now/60.0)*60 + intval($startTime*60);
					}
					else if( is_string($startTime) )
					{
						$startTime = strtotime( sprintf('%s %s:00', date('Y-m-d'), $startTime) );
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
		
		
		
		switch ($op)
		{
			case 'setAll':			
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
				
				self::cleanData(LordwarUtil::getCrossDbName(), $sess, false);
				self::cleanAllDbData($arrDb, $sess);
				$ret = self::setStartTime(LordwarUtil::getCrossDbName(), $startTime, $sess);
				$ret = self::setAllDbStartTime($arrDb, $startTime, $sess);
			

				if( empty($ret) )
				{
					return;
				}
				
				$arrRoundDelay = $ret[1]['startTimeArr'];
	
				$arrTaskInner = self::genArrTaskInner($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay);
				$arrTaskCross = self::genArrTaskCross($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay, $crossPrepareDelay);
				
				$arrCronInner = self::genArrCron(LordwarField::INNER, $arrTaskInner);
				$arrCronCross = self::genArrCron(LordwarField::CROSS, $arrTaskCross);
				
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

				$procedure = LordwarProcedure::getInstance($sess, LordwarField::INNER);
				
				$teamObj = $procedure->getTeamObj($teamId);
				
				$curRound = $teamObj->getCurRound();
				$curStatus = $teamObj->getCurStatus();
				
				$winTeamObj = $teamObj->getTeamRound($curRound, LordwarTeamType::WIN);
				$loseTeamObj = $teamObj->getTeamRound($curRound, LordwarTeamType::LOSE);
				$subRound = max(  $winTeamObj->getSubRound(), $loseTeamObj->getSubRound());
				
				$msg = sprintf('curRound:%d, curStatus:%d, subRound:%d', $curRound, $curStatus, $subRound);
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				
				if( $subRound == 0 )
				{
					$curRound = LordwarUtil::getPreRound($curRound);
					$msg = sprintf('subRound:%d, set preRound:%d', $subRound, $curRound);
					printf("%s\n", $msg);
					Logger::info('%s', $msg);
				}
				
				$winTeamObj = $teamObj->getTeamRound($curRound, LordwarTeamType::WIN);
				$loseTeamObj = $teamObj->getTeamRound($curRound, LordwarTeamType::LOSE);
				
				$winTeamObj->setStatus($status);
				$winTeamObj->update();
				
				$loseTeamObj->setStatus($status);
				$loseTeamObj->update();
				
				
				break;
			case 'reset':
				break;
			default:
				printf("invalid op:%s\n", $op);
				return;
		}

		printf("done\n");
	}
	
	public static function getAllHostDbInfo($sess)
	{
		if( $sess < 0 )
		{
			$confMgr = LordwarConfMgr::getInstance();
			$sess = $confMgr->getSess();
		}
		
		
		$teamMgr = TeamManager::getInstance(WolrdActivityName::LORDWAR, $sess);
		
		$allTeamInfo = $teamMgr->getAllTeam();
		
		
		$zk = new Zookeeper ( ScriptConf::ZK_HOSTS );
		
		$arrHost = array();
		$arrDb = array();
		printf("\n===========================>all team server Info:\n");
		foreach( $allTeamInfo as $teamId => $arrServerId )
		{
			foreach( $arrServerId as $serverId )
			{
				$path = sprintf('/card/lcserver/lcserver#game%03d', $serverId);
				if(  $zk->exists($path) == false )
				{
					$msg = sprintf('not found game:%s, teamId:%d, path:%s', $serverId, $teamId, $path);
					printf("%s\n", $msg);
					Logger::info('%s', $msg);
					return;
				}
				$ret = $zk->get($path);
				$serverInfo = Util::amfDecode($ret);
				$host = $serverInfo['host'];
				if( isset($arrHost[$host]) )
				{
					$arrHost[$host][] = $serverId;
				}
				else
				{
					$arrHost[$host] = array( $serverId );
				}

				$arrDb[] = $serverInfo['db'];
				printf("team:%d, serverId:%d, host:%s, db:%s\n", $teamId, $serverId, $host, $serverInfo['db']);
			}
		}
		
		Logger::info('allTeam:%s', $allTeamInfo);
		Logger::info('arrHost:%s, arrDb:%s', $arrHost, $arrDb);
		return array(
			'arrHost' => $arrHost,
			'arrDb' => $arrDb,
		);
	}
	
	public static function setCronToAllMachine($arrCronInner, $arrCronCross, $arrInnerHost, $crossMachine)
	{
	
		printf("\nset crontab to all machine:\n");
		foreach( $arrInnerHost as $host => $arrServerId )
		{
			$msg = sprintf('set inner crontab to host:%s, arrServerId:%s', $host, implode(',', $arrServerId));
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			
			self::setCronToMachine($arrCronInner, $host, LordwarField::INNER);
		}
		
		self::setCronToMachine($arrCronCross, $crossMachine, LordwarField::CROSS, !isset($arrInnerHost[$crossMachine]));
		
	}
	
	
	public static function genArrCron($field, $arrTask)
	{
		$arrCron = array();
		foreach( $arrTask as $op => $arr)
		{
			$arrTimeRound = array();
			foreach($arr as $value)
			{
				$t = date('i H', $value['time']);
				if( isset( $arrTimeRound[$t] ) )
				{
					$arrTimeRound[$t]['round'][] = $value['round'];
				}
				else
				{
					$arrTimeRound[$t] = array(
						'round' => array($value['round'])
					);
				}
			}
			foreach( $arrTimeRound as $t => $value )
			{
				if( $field == LordwarField::CROSS )
				{
					$arrTimeRound[$t]['cron'] = sprintf('%s * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/LordwarScript.php %s %s', $t, $field, $op);
				}
				else
				{
					$arrTimeRound[$t]['cron'] = sprintf('%s * * * $BTSCRIPT $SCRIPT_ROOT/LordwarScript.php %s %s', $t, $field, $op);
				}
				
			}
			
			$arrCron[$op] = $arrTimeRound;
		}
		
		return $arrCron;
	}
	
	
	
	public static function genArrTaskInner($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay)
	{
		$arrCron = array();
		foreach( LordwarRound::$INNER_ROUND as $round )
		{
			if( $round == LordwarRound::REGISTER )
			{
				continue;
			}
			$roundStartTime = $startTime + $arrRoundDelay[$round][0]*SECONDS_OF_DAY + $arrRoundDelay[$round][1];
			$arrCron['runRound'][] = array( 'round' => $round, 'time' => $roundStartTime);
			
			$lastRoundStartTime = $roundStartTime;
		}
		//$innerDelt = $arrRoundDelay[LordwarRound::INNER_2TO1] - $arrRoundDelay[LordwarRound::INNER_4TO2];
		$arrCron['rewardPromotion'][] = array( 'round' => LordwarRound::INNER_2TO1 ,'time' => $lastRoundStartTime + $supportRewardDelay  );
		
		foreach( LordwarRound::$CROSS_PROMO as $round )
		{
			$roundStartTime = $startTime + $arrRoundDelay[$round][0]*SECONDS_OF_DAY + $arrRoundDelay[$round][1] + $supportRewardDelay;
			$arrCron['rewardSupport'][] = array( 'round' => $round, 'time' => $roundStartTime);
		}
		
		return $arrCron;
	}
	
	public static function genArrTaskCross($startTime, $arrRoundDelay, $supportRewardDelay, $promotionRewardDelay, $crossPrepareDelay)
	{
		$arrCron = array();
		
		//$innerDelt = $arrRoundDelay[LordwarRound::INNER_2TO1] - $arrRoundDelay[LordwarRound::INNER_4TO2];
		$prepareCrossTime = $startTime 
					+ $arrRoundDelay[LordwarRound::INNER_2TO1][0]*SECONDS_OF_DAY
					+ $arrRoundDelay[LordwarRound::INNER_2TO1][1]
					//+ $innerDelt
					+ $supportRewardDelay
					+ $crossPrepareDelay ;
		$arrCron['prepareCross'] = array(
				array('round' => 0, 'time' => $prepareCrossTime)
		);
		foreach( LordwarRound::$CROSS_ROUND as $round )
		{
			$roundStartTime = $startTime + $arrRoundDelay[$round][0]*SECONDS_OF_DAY + $arrRoundDelay[$round][1];
			$arrCron['runRound'][] = array( 'round' => $round, 'time' => $roundStartTime);
			
			if( $round != LordwarRound::CROSS_AUDITION )
			{
				$arrCron['checkReward'][] = array( 'round' => $round, 'time' => $roundStartTime + $supportRewardDelay + $crossPrepareDelay);
			}
			
			$lastRoundStartTime = $roundStartTime;
		}
		
		$arrCron['rewardPromotion'][] = array( 'round' => LordwarRound::CROSS_2TO1 ,'time' => $lastRoundStartTime + $supportRewardDelay + $crossPrepareDelay + $promotionRewardDelay );
		
		return $arrCron;
	}
	
	
	public static function setAllDbStartTime($arrDb, $startTime, $sess)
	{
		if( empty($arrDb) )
		{
			return array();
		}

		foreach( $arrDb as $dbName )
		{
			$ret = self::setStartTime($dbName, $startTime, $sess);
			if( empty($ret) )
			{
				return array();
			}
		}
		return $ret;
	}
	
	
	
	public static function setStartTime($dbName, $startTime, $sess)
	{
		try
		{
			$data = new CData();
			
			if( !empty($dbName) )
			{
				$data->useDb($dbName);
			}
			
			$ret = $data->select(ActivityDef::$ARR_CONF_FIELD)->from('t_activity_conf')
							->where('name', '==', ActivityName::LORDWAR)
							->orderBy('version', false)->limit(0, 1)->query();
			if( empty($ret) )
			{
				$msg = sprintf("WARN: no lordwar conf in db, please config it first");
				printf("%s\n", $msg);
				Logger::info('%s', $msg);
				return array();
			}
			$ret = $ret[0];
		
			if( $sess >= 0 )
			{
				$ret['va_data'][1]['sess'] = $sess;
			}
			$version = Util::getTime();
			$conf = array(
					'name' => $ret['name'],
					'version' => $version,
					'start_time' => $startTime,
					'end_time' => $ret['end_time'],
					'need_open_time' => $ret['need_open_time'],
					'str_data' => $ret['str_data'],
					'va_data' => $ret['va_data'],
			);
			if( !empty($dbName) )
			{
				$data->useDb($dbName);
			}
			$data->insertOrUpdate('t_activity_conf')->values($conf)->query();
		
			if( empty($dbName) )
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
				McClient::del( ActivityConfLogic::genMcKey(ActivityName::LORDWAR)  );
			}
			
		
			$msg = sprintf('set lordwar startTime:%s, dbName:%s',date( 'Ymd H:i:s ',$startTime), $dbName);
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
	
	public static function cleanAllDbData($arrDb, $sess, $cleanTempleData = true)
	{
		foreach( $arrDb as $dbName )
		{
			$ret = self::cleanData($dbName, $sess, $cleanTempleData);
		}
	}
	
	
	public static function cleanData($dbName, $sess, $cleanTempleData = true)
	{
		$data = new CData();
			
		if( !empty($dbName) )
		{
			$data->useDb($dbName);
		}
		$arrValue = array('sess' => 0);
		$ret = $data->update( 't_lordwar_procedure' )
					->set($arrValue)
					->where('sess', '=', $sess)
					->query();
	
		if( $cleanTempleData )
		{
			if( !empty($dbName) )
			{
				$data->useDb($dbName);
			}
			$arrValue = array('sess' => time() );
			$ret = $data->update( 't_lordwar_temple' )
					->set($arrValue)
					->where('sess', '=', $sess)
					->query();
		}
		
	}
	

	public static function setCronToMachine($arrCron, $host, $field, $delOld = true)
	{
		printf("set %s crontab to host:%s\n", $field, $host);
		
		$tmpFile = '/tmp/crontab.lordwar';
		$bakFile = sprintf('/tmp/crontab.%s', date('Ymd-H-i-s'));

		if( empty($host) )
		{
			exec( sprintf(' crontab -l > %s && cat %s ', $bakFile, $bakFile ), $arrRet );
		}
		else 
		{
			exec( sprintf('ssh %s "crontab -l > %s && cat %s "', $host, $bakFile, $bakFile ), $arrRet );
		}

		$arrBefore = array();
		$arrAfter = array();
		$foundTag = false;
		foreach( $arrRet as $key => $cronLine)
		{
			// 处理当跨服和服内处于同一个机器上的情况
			if( $delOld &&  preg_match(sprintf('/LordwarScript.php %s/', $field),  $cronLine) )
			{
				continue;
			}
			if( $foundTag == false )
			{
				$arrBefore[] = $cronLine;
			}
			else
			{
				$arrAfter[] = $cronLine;
			}
				
			if( $cronLine == sprintf('#lordwar %s',$field) )
			{
				$foundTag = true;
			}
		}
		
		if( $foundTag == false )
		{
			$arrBefore[] = '';
			$arrBefore[] = sprintf('#lordwar %s',$field);
		}
		
		foreach( $arrCron as $op => $arr )
		{
			foreach( $arr as $value )
			{
				//$arrBefore[] = sprintf('%s #round:%s', $value['cron'], implode(',', $value['round']));
				$arrBefore[] = $value['cron'];
			}
		}
		$allCron = array_merge($arrBefore, $arrAfter);
		if( !empty($allCron[ count($allCron)-1 ]) )
		{
			$allCron[] = "";
		}

		$allCronStr = implode("\n", $allCron);
		//printf("allCronStr:\n%s\n", $allCronStr );

		file_put_contents($tmpFile, $allCronStr);

		if( empty($host) )
		{
			exec( sprintf('crontab %s',  $tmpFile) );
		}
		else 
		{
			exec( sprintf('scp %s %s:%s && ssh %s "crontab %s"',  $tmpFile, $host, $tmpFile, $host, $tmpFile) );
		}
	}
}

	
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */