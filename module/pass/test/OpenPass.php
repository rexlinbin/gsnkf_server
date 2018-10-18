<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OpenPass.php 148972 2014-12-25 05:58:09Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/test/OpenPass.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-12-25 05:58:09 +0000 (Thu, 25 Dec 2014) $
 * @version $Revision: 148972 $
 * @brief 
 *  
 **/
class OpenPass extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		
		if( !FrameworkConfig::DEBUG )
		{
			throw new FakeException( 'not in debug mode' );
		}
		if( count($arrOption) < 1 )
		{
			printf("invalid parm.  btscript gamexxx $0 lastTime \n");
			return;
		}
	
		$lastSeconds = $arrOption[0];
		if( $lastSeconds > SECONDS_OF_DAY )
		{
			printf("invalid parm.  last time > 1 day");
			return;
		}
		
		$rewardDelaySeconds = 60;
		if( isset( $arrOption[1] ) )
		{
			if( $arrOption[1] > 60 )
			{
				$rewardDelaySeconds = intval( $arrOption[1] );
			}
			if( $rewardDelaySeconds > $lastSeconds )
			{
				printf("invalid parm.  delay seconds > last seconds ");
				return;
			}
		}
		
		$curTime = Util::getTime();
		$handsOffBeginSeconds = $curTime + $lastSeconds;
		
		//改一下配置
		$handsOffBeginTime = date( "His", $handsOffBeginSeconds );
		$handsOffLastSeconds = strtotime( date( "Ymd", $handsOffBeginSeconds )."235959" ) - $handsOffBeginSeconds;
		popen("/bin/sed -i '/HANDSOFF_LASTTIME/{s/[0-9-]\+/$handsOffLastSeconds/;}' /home/pirate/rpcfw/conf/Pass.cfg.php", 'r');
		popen("/bin/sed -i '/HANDSOFF_BEGINTIME/{s/[0-9-]\+/$handsOffBeginTime/;}' /home/pirate/rpcfw/conf/Pass.cfg.php", 'r');
		
		printf( "set conf handsOffBeginTime: %s, handsOffLastSeconds: %s \n", $handsOffBeginTime, $handsOffLastSeconds );
		
		//清一下db
		self::setDb();
		printf( "set db \n");
		
		//设一下crontab
		$rewardTime = $handsOffBeginSeconds + $rewardDelaySeconds;
		$rewardHourMinu = date( "i H", $rewardTime );
		$arrCron = array( sprintf( '%s * * * $SYNCBTSCRIPT $SCRIPT_ROOT/PassScript.php', $rewardHourMinu ) );
		self::setCron( $arrCron );
		
		printf( "set cron, will send reward in: %s \n", date( "H:i", $rewardTime ) );
		
		printf( "set done \n", $arrCron );
	}

	public static function setDb()
	{
		$curTime = Util::getTime();
		$todayBeginTime = strtotime( date( "Ymd",$curTime )."000000" );
		$data = new CData();
		$data->update( 't_pass' )->set( 
				array( 'refresh_time' => $curTime - SECONDS_OF_DAY, 'reach_time' => $curTime - SECONDS_OF_DAY, 'reward_time' => $curTime - SECONDS_OF_DAY  ) 
		)
		->where( array( 'refresh_time','>',$todayBeginTime ) )->query();
	}
	
	public static function setCron($arrCron)
	{
		printf("set crontab for pass, info: %s\n",$arrCron );
	
		$tmpFile = '/tmp/crontab.pass';
		$bakFile = sprintf('/tmp/crontab.pass.%s', date('Ymd-H-i-s'));
	
		exec( sprintf(' crontab -l > %s && cat %s ', $bakFile, $bakFile ), $arrRet );

		$arrBefore = array();
		$arrAfter = array();
		$foundTag = false;
		foreach( $arrRet as $key => $cronLine)
		{
 			if( preg_match(sprintf('/PassScript.php/'),  $cronLine) )
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
	
			if( $cronLine == sprintf('#pass') )
			{
				$foundTag = true;
			}
		}
	
		if( $foundTag == false )
		{
			$arrBefore[] = '';
			$arrBefore[] = sprintf('#pass');
		}
	
		foreach( $arrCron as $key => $cron )
		{
			$arrBefore[] = $cron;
		}
		
		$allCron = array_merge($arrBefore, $arrAfter);
		if( !empty($allCron[ count($allCron)-1 ]) )
		{
			$allCron[] = "";
		}
	
		$allCronStr = implode("\n", $allCron);
		file_put_contents($tmpFile, $allCronStr);
		exec( sprintf('crontab %s',  $tmpFile) );
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */