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




class Robot extends RPCProxy
{
	protected $pid = 0;
	protected $uid = 0;
	
	protected $err = false;
	
	protected $reviveWhenDead = false;
	
	protected $lastAtkTime = 0;
	
	protected $errTryNum = 1;
	
	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, true);
		
		$this->pid = $pid;
		
		$this->connect($server, $port);
		
		$this->setClass('user');
		$ret = $this->login($this->pid);
		MyLog::debug('pid:%d login. ret:%s', $pid, $ret);
		if($ret != 'ok')
		{
			throw new Exception('login failed');	
		}
		
		$ret = $this->getUsers();
		MyLog::debug('getUuser. ret:%s', $ret);
		
		if(empty($ret))
		{
			throw new Exception('no user');
		}
		$this->uid = $ret[0]['uid'];
		
		$ret = $this->userLogin($this->uid);
		if($ret != 'ok')
		{
			throw new Exception('userLogin failed');
		}
		
		MyLog::info('pid:%d login ok', $pid);
		$this->setClass('boss');
		
	}
	
	public function getBossStarTime($bossId)
	{
		$bossOffsetInServer = $this->getBossOffset();
		
		$starTime = BossUtil::getBossStartTime($bossId);

		MyLog::debug('offset in server:%d, myStartTime:%s', $bossOffsetInServer, date('Y-m-d H:i:s',$starTime) );
		return $starTime - GameConf::BOSS_OFFSET + $bossOffsetInServer;
	}
	
	public function getBossEndTime($bossId)
	{
		$bossOffsetInServer = $this->getBossOffset();
		
		$endTime = BossUtil::getBossEndTime($bossId);
		
		MyLog::debug('offset in server:%d, myEndTime:%s', $bossOffsetInServer, date('Y-m-d H:i:s',$endTime) );
		return $endTime - GameConf::BOSS_OFFSET + $bossOffsetInServer;
	}
	
	public function setReviveWhenDead($flag)
	{
		$this->reviveWhenDead = $flag;
		if( $this->reviveWhenDead )
		{
			$this->setClass('console');
			$ret = $this->execute('gold 100000');
			$this->setClass('boss');
		}
	}
	
	public function canAtk()
	{
		if( $this->reviveWhenDead )
		{
			return true;
		}
		
		$conf = btstore_get()->BOSS_INSPIRE_REVIVE[1];
		$cdTime = $conf[BossDef::ATK_CD];
		
		$now = time();
		if( $now > $this->lastAtkTime + $cdTime )
		{
			return true;
		}
		
		return false;
	}

	
	public function run()
	{
		if( $this->err )
		{
			MyLog::info('pid:%d err', $this->pid);
			return;
		}
		
		$now = time();
		try 
		{
			if( $this->canAtk() )
			{
				$ret = $this->attack();
				MyLog::info('pid:%d attack:%s', $this->pid, $ret['bossAtkHp']);
				
				
				if($this->reviveWhenDead)
				{
					$ret = $this->revive();
					if( empty($ret) )
					{
						$this->err = true;
						MyLog::fatal('revive failed');
					}
				}
				$this->lastAtkTime = $now;
			}
		}
		catch (Exception $e)
		{
			MyLog::fatal('pid:%d, errNum:%d, err:%s', $this->pid, $this->errTryNum, $e->getMessage() );
			if($this->errTryNum > 0)
			{
				$this->errTryNum = $this->errTryNum - 1;
				$this->lastAtkTime = $now;
			}
			else 
			{
				$this->err = true;	
			}
		}
	}
}

class BossRobot extends BaseScript
{
	

	protected $port = 7777;
	
	
	protected function executeScript($arrOption)
	{		
		MyLog::init('log_boss_robot');
		
		$bossId = 1;

		$botNum = 100;
		if( isset($arrOption[0]) )
		{
			$botNum = intval($arrOption[0]);
		}
		
		$arrPid = $this->getArrPidForBot($botNum);
		
		$arrRobot = array();
		foreach($arrPid as $pid)
		{
			try 
			{
				$robot = new Robot($this->serverIp, $this->port, $pid);
			}
			catch (Exception $e)
			{
				MyLog::fatal('new BossRobot failed. pid:%d, err:%s', $pid, $e->getMessage());
				continue;
			}
			$arrRobot[$pid] = $robot;
			
			$ret = $robot->enterBoss($bossId);
			MyLog::debug('enterBoss. ret:%s', $ret);
			
			if( $ret['boss_time'] != 1 )
			{
				MyLog::fatal('not boss time. time startTime:%s, endTime:%s', 
					date('Y-m-d H:i:s', $robot->getBossStarTime($bossId) ) , 
					date('Y-m-d H:i:s', $robot->getBossEndTime($bossId) ) );
				return;
			}
		}
		if(empty($arrRobot))
		{
			MyLog::fatal('no robot');
			return;
		}
		
		//current($arrRobot)->setReviveWhenDead(true);
		

		while(true)
		{
			MyLog::info('------------------[%s]', date('Y-m-d H:i:s') );
			foreach ($arrRobot as $robot)
			{
				$robot->run();
			}
			
			sleep(1);
		}
		
	}

	
	public function getArrPidForBot($num = 10)
	{
		$arrDefPid = array(570);
	
		if(count($arrDefPid) >=  $num)
		{
			return array_slice($arrDefPid, 0, $num);
		}
	
		$leftNum = $num - count($arrDefPid);
	
		$data = new CData();
		$arrRet = $data->select( array('pid') )->from('t_user')
					->where('status', '=', UserDef::STATUS_OFFLINE)
					->where('pid', '>', UserConf::PID_MAX_RETAIN)
					->where('level','<',40)
					->limit(0, $num)->query();
		$arrDbPid = Util::arrayExtract($arrRet, 'pid');
	
		MyLog::debug('arrDefPid:%s, arrDbPid:%s', $arrDefPid, $arrDbPid);
	
		$arrDbPid = array_diff($arrDbPid, $arrDefPid);
		$arrDbPid = array_slice($arrDbPid, 0, $leftNum);
	
		$arrPid = array_merge($arrDefPid, $arrDbPid );
	
		MyLog::info('addPid for Bot:%s', $arrPid);
		return $arrPid;
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