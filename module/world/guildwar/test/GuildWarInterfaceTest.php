<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarInterfaceTest.php 155947 2015-01-29 07:28:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/test/GuildWarInterfaceTest.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-29 07:28:17 +0000 (Thu, 29 Jan 2015) $
 * @version $Revision: 155947 $
 * @brief 
 *  
 **/
require_once dirname (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/test/UserClient.php";

class MyGuildWarRobot extends UserClient
{
	protected $err = false;
	protected $errTryNum = 1;

	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		printf("pid:%d login ok\n", $pid);
		
		$ret = $this->doConsoleCmd('silver 1000000');
		MyLog::info('console. ret:%s', $ret);
		
		$ret = $this->doConsoleCmd('level 50');
		MyLog::info('console. ret:%s', $ret);
		
		$this->setClass('guildwar');
	}
	
	public function doConsoleCmd($cmd)
	{
		$this->setClass ('console');
		return $this->execute( $cmd );
	}
}

class GuildWarInterfaceTest extends BaseScript
{
	private static $serverAddr;
	private static $serverPort;
	/**
	 * (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 1)
		{
			echo("lack para : method args\n");
			return;
		}
		
		$type = $arrOption[0];
		self::$serverAddr = '192.168.1.121';
		self::$serverPort = 10001;
		MyLog::init('guildwar');
		
		if ($type == 'apply') 
		{
			$this->applay();
		}
		
		if ($type == 'createUser') 
		{
			$this->createUser();
		}
		
		if ($type == 'createGuild') 
		{
			$this->createGuild(20);
		}
		
		if ($type == 'signUpForTest') 
		{
			$this->signUpForTest();
		}
		
		if ($type == 'signUp') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->signUp();
			var_dump($ret);
		}
		
		if ($type == 'getUserGuildWarInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getUserGuildWarInfo($uid);
			var_dump($ret);
		}
		
		if ($type == 'getGuildWarMemberList') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getGuildWarMemberList();
			var_dump($ret);
		}
		
		if ($type == 'updateFormation') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->updateFormation();
			var_dump($ret);
		}
		
		if ($type == 'clearUpdFmtCdByGold') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->clearUpdFmtCdByGold();
			var_dump($ret);
		}
		
		if ($type == 'changeCandidate')
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			//$ret = $robot->changeCandidate(0, 22321);
			$ret = $robot->changeCandidate(1, 22321);
			var_dump($ret);
		}
		
		if ($type == 'getMyTeamInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getMyTeamInfo();
			var_dump($ret);
		}
		
		if ($type == 'cheer') 
		{
			$uid = 32725;
			$pid = 22274;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->cheer(10150, 3001);
			var_dump($ret);
		}
		
		if ($type == 'getGuildWarInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getGuildWarInfo();
			var_dump($ret);
		}
		
		if ($type == 'getHistoryCheerInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getHistoryCheerInfo();
			var_dump($ret);
		}
		
		if ($type == 'getTempleInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getTempleInfo();
			var_dump($ret);
		}
		
		if ($type == 'worship') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->worship(0);
			var_dump($ret);
		}
		
		if ($type == 'getHistoryFightInfo') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getHistoryFightInfo();
			var_dump($ret);
		}
		
		if ($type == 'getReplay') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getReplay(10337, 3001, 10344, 3001);
			var_dump($ret);
		}
		
		if ($type == 'push') 
		{
			GuildWarLogic::push(GuildWarField::INNER, 1);
		}
		
		if ($type == 'tmp') 
		{
			$this->tmp();
		}
		
		if ($type == 'buyMaxWinTimes') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->buyMaxWinTimes();
			var_dump($ret);
		}
		
		if ($type == 'getReplayDetail') 
		{
			$uid = 32725;
			$pid = 32724;
			$robot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $pid);
			$ret = $robot->getReplayDetail(array('GDW_29431'));
			var_dump($ret);
		}
	}
	
	private function applay()
	{
		$arrUid = array
		(
				20011,20012,20013,20014,20015,20016,20017,20018,20019,20020,
				20021,20022,20023,20024,20025,20026,20027,20028,20029,20030,
		);
		$guildId = 10000;
		$presidentUid = 20038;
				
		foreach ($arrUid as $aUid)
		{
			try
			{
				//GuildLogic::applyGuild($aUid, $guildId);
				GuildLogic::agreeApply($presidentUid, $aUid);
			}
			catch (Exception $e)
			{
			}
		}
	}
	
	public static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = 'mbg_' . strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		return array($ret['uid'], $pid);
	}
	
	public static function createGuild($count)
	{
		while ($count-- > 0)
		{
			list($newUid, $newPid) = self::createUser();
			$newRobot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $newPid);
			$newRobot->setClass('guild');
			$ret = $newRobot->createGuild('x' . strval($newPid));
			//var_dump($ret);
			$guildId = $ret['info']['guild_id'];
			$guildObj = GuildObj::getInstance($guildId);
			$guildObj->setGuildLevel(50);
			$guildObj->update();
				
			for ($i = 0; $i < 25; ++$i)
			{
				list($memUid, $memPid) = self::createUser();
				try
				{
					GuildLogic::applyGuild($memUid, $guildId);
					GuildLogic::agreeApply($newUid, $memUid);
				}
				catch (Exception $e)
				{
				}
			}
			
			$newRobot->setClass('guildwar');
			$ret = $newRobot->signUp();
			var_dump($ret);
			printf("signUp for guild:%d\n", $guildId);
		}
	}
	
	public static function signUpForTest()
	{
		$arrPid = array(33712,33660,33608,33556,33504,33452,33400,33348,33296,33244,33192,33140,33088,33036,32984,32932,32880,32828,32776,32724);
		foreach ($arrPid as $aPid)
		{
			try 
			{
				$newRobot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $aPid);
				//$newRobot->doConsoleCmd('setGuildLevel 1 5');
				$newRobot->setClass('guildwar');
				$ret = $newRobot->signUp();
				var_dump($ret);
				printf("signUp for pid:%d\n", $aPid);
			}
			catch(Exception $e)
			{
				Logger::fatal('GuildWarInterfaceTest::signUpForTest failed, pid[%d], msg[%s]', $aPid, $e->getMessage());
			}
		}
	}
	
	public static function tmp()
	{
		$arrPid = array(33712,33660,33608,33556,33504,33452,33400,33348,33296,33244,33192,33140,33088,33036,32984,32932,32880,32828,32776,32724);
		$arrPid = array(265 ,20620,20622,20624,20676,20728,20770,20832,20894,20956,21020,21022,21074,21126,21178,21230,21282,21334,21386,21438);
		foreach ($arrPid as $aPid)
		{
			$newRobot = new MyGuildWarRobot(self::$serverAddr, self::$serverPort, $aPid);
			$newRobot->doConsoleCmd('setGuildLevel 1 5');
			printf("tmp for pid:%d\n", $aPid);
		}
	}
	
	
}

class MyLog
{
	private static $fid;

	public static function init($filename)
	{
		self::$fid = fopen($filename, 'w');
	}

	private static function log($arrArg, $print = 0)
	{

		$arrMicro = explode ( " ", microtime () );
		$content = '[' . date ( 'Ymd H:i:s ' );
		$content .= sprintf ( "%06d", intval ( 1000000 * $arrMicro [0] ) );
		$content .= "]";

		foreach ( $arrArg as $idx => $arg )
		{
			if ($arg instanceof BtstoreElement)
			{
				$arg = $arg->toArray ();
			}
			if (is_array ( $arg ))
			{
				$arrArg [$idx] = var_export ( $arg, true );
			}
		}
		$content .= call_user_func_array ( 'sprintf', $arrArg );
		$content .= "\n";

		if($print)
		{
			echo $content;
		}
		fprintf(self::$fid, $content);

	}
	public static function debug()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, false);
	}
	public static function info()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, true);
	}
	public static function fatal()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, true);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */