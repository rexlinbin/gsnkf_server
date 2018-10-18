<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ServerTestReward.class.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ServerTestReward.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/

class ServerTestReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript ($arrOption)
	{
		$MAX_EXEC_TIME		= 65536;
		$MAX_TOP_LIST		= 100;
		$MIN_TOP_LIST		= 1;
		$TEN_TOP			= 10;

		$subject_top10 = '等级前十奖励';
		$content_top10 = '恭喜您在封测期间位列等级排行榜前十，以下是活动奖励500金币，请领取附件并在背包中使用物品，感谢您对游戏的支持，祝您游戏愉快。';
		$itemTemplates_top10 = array ( 70012 => 1 );

		$subject_top100 = '等级排行奖励';
		$content_top100 = '恭喜您在封测期间位列等级排行榜前11~100，以下是活动奖励200金币，请领取附件并在背包中使用物品，感谢您对游戏的支持，祝您游戏愉快。';
		$itemTemplates_top100 = array ( 70015 => 1 );

		$subject_level50 = '封测等级50奖励';
		$content_level50 = '恭喜您在封测期间成功达到等级50级，以下是活动奖励200金币，请领取附件并在背包中使用物品，感谢您对游戏的支持，祝您游戏愉快。';
		$itemTemplates_level50 = array ( 70015 => 1 );

		$top_list_file_name = $arrOption[0];
		$level_file_name = $arrOption[1];
		if ( !file_exists($top_list_file_name) )
		{
			Logger::FATAL('invalid top list file name:%s!', $top_list_file_name);
			return;
		}

		$top_list_file = fopen($top_list_file_name, 'r');
		if ( !$top_list_file )
		{
			Logger::FATAL('open file:%s failed!', $top_list_file_name);
			return;
		}

		$top_list = array();
		for ( $i = 0; $i < $MAX_EXEC_TIME; $i++ )
		{
			$line = fgets($top_list_file);
			if ( empty($line) )
			{
				Logger::INFO('TOP LIST END!');
				break;
			}
			$data = explode("\t", $line);
			$index = intval($data[0]);
			$pid = intval($data[1]);
			if ( $index < $MIN_TOP_LIST || $index > $MAX_TOP_LIST )
			{
				Logger::FATAL('invalid top list!index:%d', $index);
				return;
			}

			if ( isset($top_list[$index]) )
			{
				Logger::FATAL('invalid top list!index:%d', $index);
				return;
			}

			if ( in_array($pid, $top_list) )
			{
				Logger::FATAL('pid:%d already in top list!', $pid);
				return;
			}

			$top_list[$index] = $pid;
		}

		if ( count($top_list) != $MAX_TOP_LIST )
		{
			Logger::FATAL('not enough top list!, need:%d, now:%d', $MAX_TOP_LIST, count($top_list));
			return;
		}

		fclose($top_list_file);

		if ( !file_exists($level_file_name) )
		{
			Logger::FATAL('invalid level file name:%s!', $level_file_name);
			return;
		}

		$level_file = fopen($level_file_name, 'r');
		if ( !$level_file )
		{
			Logger::FATAL('open file:%s failed!', $level_file_name);
			return;
		}

		$level_list = array();
		for ( $i = 0; $i < $MAX_EXEC_TIME; $i++ )
		{
			$line = fgets($level_file);
			if ( empty($line) )
			{
				Logger::INFO('LEVEL LIST END!');
				break;
			}
			$data = explode("\t", $line);
			$pid = intval($data[0]);

			if ( in_array($pid, $level_list) )
			{
				Logger::FATAL('pid:%d already in level list!', $pid);
				return;
			}

			$level_list[] = $pid;
		}

		fclose($level_file);

		foreach ( $top_list as $index => $pid )
		{
			$users = UserDao::getUsers($pid, array('uid'));
			if ( empty($users) )
			{
				Logger::INFO('INFO_TOP_LIST: index:%d pid:%d not exist user!', $index, $pid);
				continue;
			}

			if ( $index <= $TEN_TOP )
			{
				$subject = $subject_top10;
				$content = $content_top10;
				$itemTemplates = $itemTemplates_top10;
			}
			else
			{
				$subject = $subject_top100;
				$content = $content_top100;
				$itemTemplates = $itemTemplates_top100;
			}

			$uid = $users[0]['uid'];
			Logger::INFO('send top list mail to pid:%d uid:%d', $pid, $uid);
			MailLogic::sendSysItemMailByTemplate($uid,
					MailConf::DEFAULT_TEMPLATE_ID, $subject, $content, $itemTemplates);
		}

		foreach ( $level_list as $pid )
		{
			$users = UserDao::getUsers($pid, array('uid'));
			if ( empty($users) )
			{
				Logger::INFO('INFO_LEVEL_LIST::pid:%d not exist user!', $pid);
				continue;
			}

			$uid = $users[0]['uid'];
			Logger::INFO('send level list mail to pid:%d uid:%d', $pid, $uid);
			MailLogic::sendSysItemMailByTemplate($uid,
					MailConf::DEFAULT_TEMPLATE_ID, $subject_level50,
					 $content_level50, $itemTemplates_level50);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */