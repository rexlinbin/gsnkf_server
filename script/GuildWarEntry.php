<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarEntry.php 158009 2015-02-09 11:46:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GuildWarEntry.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-09 11:46:26 +0000 (Mon, 09 Feb 2015) $
 * @version $Revision: 158009 $
 * @brief 
 *  
 **/

class GuildWarEntry extends BaseScript
{
	/**
	 * 有效的field取值
	 * @var array
	 */
	public static $validField = array
	(
			'inner' => '服内',
			'cross' => '跨服',
	);
	
	/**
	 * 有效的type取值
	 * @var array
	 */
	public static $validType = array
	(
			'runRound' => '比赛',
			'cheerReward' => '发助威奖励',
			'checkReward' => '检查助威奖励',
			'fightReward' => '发比赛奖励',
	);
	
	/**
	 * 帮助函数
	 */
	private function usage()
	{
		print("=================================================\n");
		print("usage:	btscipt game001 GuildWarEntry.php field type\n");
		print("field取如下值：\n");
		foreach (self::$validField as $value => $meaning)
		{
			printf("%10s		%10s\n", $value, $meaning);
		}
		print("type取如下值：\n");
		foreach (self::$validType as $value => $meaning)
		{
			printf("%10s		%10s\n", $value, $meaning);
		}
		print("=================================================\n");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		// 参数个数是否够
		if (count($arrOption) < 2)
		{
			Logger::fatal('param not enough, %s', $arrOption);
			return;
		}
		
		// field是否有效
		$field = $arrOption[0];
		if (!key_exists($field, self::$validField)) 
		{
			Logger::fatal('not valid field[%s], valid field[%s]', $field, self::$validField);
			return;
		}
		
		// 操作类型是否有效
		$op = $arrOption[1];
		if (!key_exists($op, self::$validType))
		{
			Logger::fatal('not valid type[%s], valid type[%s]', $op, self::$validType);
			return;
		}
		
		// 获取要锁文件
		$group = RPCContext::getInstance()->getFramework()->getGroup();
		if (empty($group))
		{
			$lockPath = '/tmp/GUILD_WAR_CROSS_LOCK_FILE_FOR_' . PlatformConfig::PLAT_NAME;	// 这是在跨服机上
		}
		else
		{
			$lockPath = '/tmp/GUILD_WAR_INNER_LOCK_FILE_' . $group;	// 这是在服内机器上
		}
		
		// 锁文件
		$lockObj = new SimpleFileLock($lockPath, TRUE);
		if ($lockObj->lock() == FALSE)
		{
			Logger::warning('some other process running, quit. field:%s, op:%s', $field, $op);
			return;
		}
		
		// 跨服机器上需要特殊处理
		if ($field == GuildWarField::CROSS)
		{
			// 设置db
			RPCContext::getInstance()->getFramework()->setDb(GuildWarUtil::getCrossDbName());
				
			//跨服db上只有跨服活动的配置，这就导致db上的主干版本号始终低于平台版本号。
			$curVersion = ActivityConfLogic::getTrunkVersion();
			ActivityConfLogic::doRefreshConf($curVersion, TRUE, FALSE);
			GuildWarConfObj::getInstance(GuildWarField::CROSS);
		}
		
		// 检查是不是在一届跨服军团战中
		$session = GuildWarConfObj::getInstance($field)->getSession();
		if (empty($session))
		{
			Logger::info('not in any session');
			return;
		}
		
		// 获取运行的round
		$force = FALSE;
		if (isset($arrOption[2]))
		{
			$round = intval($arrOption[2]);
			$force = TRUE;
			Logger::info('set round:%d', $round);
		}
		else
		{
			$confObj = GuildWarConfObj::getInstance($field);
			$curRoundByConf = $confObj->getCurRound();
			if ($curRoundByConf == GuildWarRound::INVALID)
			{
				Logger::info('not in any round of guildwar');
				return;
			}
				
			/*TODO
			 如果已经过了最后一个阶段超过一天的时间，就啥也不干了。
			最合理的方式其实应该是再加一个结束round，但是一开始没有考虑到这个问题，后续再加影响太大，所以就这么检查了
			*/
			if ($curRoundByConf >= GuildWarRound::ADVANCED_2)
			{
				$lastRoundStartTime = $confObj->getRoundStartTime($curRoundByConf);
				if (time() - $lastRoundStartTime >= SECONDS_OF_DAY)
				{
					Logger::info('last round:%d start time:%s. no work now', $curRoundByConf, date('Y-m-d H:i:s', $lastRoundStartTime));
					return;
				}
			}
				
			$round = $curRoundByConf;
			Logger::info('get round from conf:%d', $curRoundByConf);
		}
		Logger::info('start guildwar. field:%s, op:%s, round:%d', $field, $op, $round);
		
		// 分服内和跨服运行脚本
		switch ($field)
		{
			case GuildWarField::INNER:
				self::runInner($op, $round, $force);
				break;
			case GuildWarField::CROSS;
				self::runCross($op, $round, $force);
				break;
			default:
				Logger::fatal('invalid field:%s', $field);
				break;
		}
		Logger::info('==========================');
		Logger::info('guildwar done. field:%s, op:%s, round:%d', $field, $op, $round);
		Logger::info('==========================');
		$lockObj->unlock();
	}
	
	/**
	 * 服内运行脚本的入口
	 *
	 * @param string $op
	 * @param int $round
	 * @param bool $force
	 */
	public static function runInner($op, $round, $force)
	{
		$serverId = Util::getFirstServerIdOfGroup();
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		if ($teamId < 0)
		{
			Logger::info('this server not in any team. serverId:%d', $serverId);
			return;
		}
	
		// 服内只需要发个助威奖
		if ($op == 'runRound')
		{
			Logger::fatal('inner machine, no need to op runRound');
		}
		else if ($op == 'cheerReward')
		{
			if (in_array($round, GuildWarRound::$FinalsRound))
			{
				GuildWarScriptLogic::sendCheerReward();
			}
			else
			{
				Logger::info('inner machine, no need to op cheerReward in round:%d', $round);
			}
		}
		else if ($op == 'checkReward')
		{
			Logger::fatal('inner machine, no need to op checkReward');
		}
		else if ($op == 'fightReward')
		{
			Logger::fatal('inner machine, no need to op fightReward');
		}
		else
		{
			Logger::fatal('invalid op:%s', $op);
		}
	}
	
	/**
	 * 跨服运行脚本的入口
	 *
	 * @param string $op
	 * @param int $round
	 * @param bool $force
	 */
	public static function runCross($op, $round, $force)
	{
		if ($op == 'runRound')
		{
			if ($round == GuildWarRound::AUDITION)
			{
				GuildWarScriptLogic::startOpenAudition($force);
			}
			else if (in_array($round, GuildWarRound::$FinalsRound))
			{
				GuildWarScriptLogic::startFinals($round, $force);
			}
			else
			{
				Logger::info('cross machine, no need to op runRound in round:%d', $round);
			}
		}
		else if ($op == 'cheerReward')
		{
			Logger::fatal('cross machine, no need to cheerReward');
		}
		else if ($op == 'checkReward')
		{
			if (in_array($round, GuildWarRound::$FinalsRound))
			{
				GuildWarScriptLogic::checkCheerReward();
			}
			else
			{
				Logger::info('cross machine, no need to op checkReward in round:%d', $round);
			}
		}
		else if ($op == 'fightReward')
		{
			if ($round == GuildWarRound::ADVANCED_2)
			{
				GuildWarScriptLogic::sendFinalReward($force);
			}
			else
			{
				Logger::info('cross machine, no need to op fightReward in round:%d', $round);
			}
		}
		else
		{
			Logger::fatal('invalid op:%s', $op);
		}
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */