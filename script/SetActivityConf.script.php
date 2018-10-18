<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SetActivityConf.script.php 250587 2016-07-08 06:48:03Z LeiZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/SetActivityConf.script.php $
 * @author $Author: LeiZhang $(wuqilin@babeltime.com)
 * @date $Date: 2016-07-08 06:48:03 +0000 (Fri, 08 Jul 2016) $
 * @version $Revision: 250587 $
 * @brief
 *
 **/


/**
 * 将某个活动的配置更新为指定内容
 * 这个函数有两个问题
 * 【1】只是更新当前最新版本号对应的版本，导致当前服的配置和平台中记录的不一样
 * 【2】存在和doRefreshConf的并发问题
 * 所以这脚本只能作为测试时使用
 *
 * 示例：
 * btscript game001 SetActivityConf.script.php shop 0 2147483647 2147483647  file.csv
 * @author wuqilin
 *
 */
class SetActivityConf extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		if( count( $arrOption ) < 2)
		{
			echo "invalid param\n";
			echo "配置活动：btscript game001 SetActivityConf.script.php set activitName startTime endTime needOpenTime  [file.csv] \n";
			echo "打开活动：btscript game001 SetActivityConf.script.php open activitName  startTime endTime needOpenTime \n";
			echo "关闭活动：btscript game001 SetActivityConf.script.php close activitName   \n";
			echo "查看活动：btscript game001 SetActivityConf.script.php show activitName   \n";
			return;
		}

		ActivityConf::$STRICT_CHECK_CONF = false;



		$confStr = '';

		$op = $arrOption[0];
		$name = $arrOption[1];

		if( $name == 'all' )
		{
			$arrName = ActivityConfLogic::getAllConfName();
		}
		else
		{
			$arrName = array($name);
		}

		if( $op == 'set' )
		{
			$start = $arrOption[2];
			$end = $arrOption[3];
			$needOpenTime = $arrOption[4];

			if( !empty($arrOption[5]) )
			{
				$file = $arrOption[5];
				if( !file_exists( $file ) )
				{
					printf("not found %s\n", $file);
					return;
				}
				$confStr = file_get_contents($file);
				// 检查要不要转码
				$e = mb_detect_encoding($confStr, array('UTF-8', 'GBK'));
				if($e == 'GBK')
				{
					$confStr = iconv('GBK', 'UTF-8', $confStr);
				}
			}
			$ret = self::setConf($name, $start, $end, $needOpenTime, $confStr);

			if( $ret )
			{
				printf("set activity conf:%s done\n", $name);
			}
			else
			{
				printf("set activity conf:%s failed\n", $name);
			}
		}
		else if( $op == 'close' )
		{
			$op = 'close';
			$start = 0;
			$end = 0;
			$needOpenTime = 0;

			foreach($arrName as $v)
			{
				self::setActivityTime($v, $start, $end, $needOpenTime);
			}
			$ret = ActivityConfLogic::updateMem();

		}
		else if( $op == 'open' )
		{
			$start = $arrOption[2];
			$end = $arrOption[3];
			$needOpenTime = $arrOption[4];

			foreach($arrName as $v)
			{
				self::setActivityTime($v, $start, $end, $needOpenTime);
			}
			$ret = ActivityConfLogic::updateMem();
		}
		else if( $op == 'show' )
		{
			$arrRet = ActivityConfLogic::getAllConfInGame();

			foreach( $arrRet as $ret )
			{
				if( in_array($ret['name'], $arrName) )
				{
					printf("name:%s, start:%s, end:%s, needOpenTime:%s, version:%s\n\n",
						$ret['name'],
						date( 'Ymd H:i:s', $ret['start_time']),
						date( 'Ymd H:i:s', $ret['end_time']),
						date( 'Ymd H:i:s', $ret['need_open_time']),
						date( 'Ymd H:i:s', $ret['version'])
					);
				}

			}
		}
		else
		{
			printf("invalid op:%s\n", $op);
			return;
		}



		printf("done\n");



	}

	public static function setActivityTime($name, $startTime, $endTime, $needOpenTime)
	{
		$ret = ActivityConfDao::getCurConfByName($name, ActivityDef::$ARR_CONF_FIELD);
		if( empty($ret) )
		{
			$msg = sprintf("WARN: no %s now", $name);
			printf("%s\n", $msg);
			Logger::info('%s', $msg);
			return;
		}

		$version = Util::getTime();
		$conf = array(
					'name' => $name,
					'version' => $version,
					'start_time' => $startTime,
					'end_time' => $endTime,
					'need_open_time' => $needOpenTime,
					'str_data' => $ret['str_data'],
					'va_data' => $ret['va_data'],
					);
		ActivityConfDao::insertOrUpdate($conf);

		$ret = ActivityConfLogic::updateMem();

		$msg = sprintf('setConf done. name:%s, start:%s, end:%s, needOpenTime:%s, version:%s',
				$name,
				date( 'Ymd H:i:s ',$startTime),
				date( 'Ymd H:i:s ',$endTime),
				date( 'Ymd H:i:s ',$needOpenTime),
				date( 'Ymd H:i:s ',$version));
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
	}


	/**
	 * @param string $name
	 * @param int $startTime
	 * @param int $endTime
	 * @param int $needOpenTime
	 * @param string $confStr
	 */
	public static function setConf($name, $startTime, $endTime, $needOpenTime, $confStr)
	{
		if(!FrameworkConfig::DEBUG)
		{
			Logger::warning('this method can only run in debug');
			return false;
		}
		$ret = ActivityConfDao::getCurConfByName($name, ActivityDef::$ARR_CONF_FIELD);
		$version = Util::getTime();

		$arrNewConf = array(
				 array(
					'name' => $name,
					'version' => $version,
					'start_time' => $startTime,
					'end_time' => $endTime,
			 		'need_open_time' => $needOpenTime,
					'data' => $confStr,
				 	),
		);

		$arrNewConf = ActivityConfLogic::decodeConf($arrNewConf);

		if( empty($arrNewConf) )
		{
			printf("decode conf:%s failed\n", $name);
			return false;
		}

		foreach($arrNewConf as $conf)
		{
			ActivityConfDao::insertOrUpdate($conf);
		}

		$ret = ActivityConfLogic::updateMem();


		$msg = sprintf('setConf done. name:%s, start:%s, end:%s, needOpenTime:%s, version:%s',
					$name,
					date( 'Ymd H:i:s ',$startTime),
					date( 'Ymd H:i:s ',$endTime),
					date( 'Ymd H:i:s ',$needOpenTime),
					$version);
		printf("%s\n", $msg);
		Logger::info('%s', $msg);
		return true;
	}
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */