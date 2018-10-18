<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarSetCron.test.php 214471 2015-12-08 06:01:02Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/test/CountryWarSetCron.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-08 06:01:02 +0000 (Tue, 08 Dec 2015) $
 * @version $Revision: 214471 $
 * @brief 
 *  
 **/

class CountryWarSetCron extends BaseScript
{
	private $host = '192.168.1.122';
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{
		$host = $this->host;
		if( isset( $arrOption[0] ) )
		{
			$host = $arrOption[0];
		}
		
		sleep(1);
		$teamTime = CountryWarConfig::getStageStartTime(time(), CountryWarStage::TEAM) + 60;
		$rangTime = CountryWarConfig::getStageStartTime(time(), CountryWarStage::RANGE_ROOM) + 60;
		$arrCron = self::genArrCron($teamTime, $rangTime);
		self::setCronToMachine($arrCron, $host );
	}
	
	static function genArrCron($teamTime, $rangeTime)
	{
		$arrCron = array();
		$teamCronTime = date('i H', $teamTime);
		$rangeCronTime = date('i H', $rangeTime);
		if( $teamCronTime == $rangeCronTime )
		{
			throw new InterException( 'invalid time between team and range, at least 60 seconds' );
		}
		
		$arrCron[$teamCronTime] = sprintf('%s * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/CountryWarScript.php team', $teamCronTime);
		$arrCron[$rangeCronTime] = sprintf('%s * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/CountryWarScript.php range', $rangeCronTime);
		
		return $arrCron;
	}
	
	public static function setCronToMachine($arrCron, $host)
	{
		printf("set crontab to host:%s\n", $host);
	
		$tmpFile = '/tmp/crontab.countrywar';
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
			if( preg_match(sprintf('/CountryWarScript.php/'),  $cronLine) )
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
	
			if( $cronLine == sprintf('#countrywar') )
			{
				$foundTag = true;
			}
		}
	
		if( $foundTag == false )
		{
			$arrBefore[] = '';
			$arrBefore[] = sprintf('#countrywar');
		}
	
		foreach( $arrCron as $time => $val )
		{
			$arrBefore[] = $val;
		}
		$allCron = array_merge($arrBefore, $arrAfter);
		if( !empty($allCron[ count($allCron)-1 ]) )
		{
			$allCron[] = "";
		}
	
		$allCronStr = implode("\n", $allCron);
	
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
