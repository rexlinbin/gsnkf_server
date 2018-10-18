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

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/test/UserClient.php";


class Robot extends UserClient
{

	protected $err = false;
	
	protected $errTryNum = 1;
	
	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		
		MyLog::info('pid:%d login ok', $pid);
		
		
		$ret = $this->doConsoleCmd('silver 100000');
		MyLog::info('console. ret:%s', $ret);
		
		$ret = $this->doConsoleCmd('open');
		MyLog::info('console. ret:%s', $ret);
		
		$this->setClass('olympic');
		
	}
	
	public function trySign($index)
	{
		try
		{
			$ret = $this->signUp($index);
			MyLog::info('sign ok. pid:%d, uid:%d index:%d, ret:%s', $this->pid, $this->uid, $index, $ret);
		}
		catch (Exception $e)
		{
			MyLog::info('sign failed. pid:%d, uid:%d index:%d, erro:%s', $this->pid, $this->uid, $index, $e->getMessage());
		}
	}
	
	public function tryChallenge($index)
	{
		try
		{
			$ret = $this->challenge($index);
			MyLog::info('challenge ok. pid:%d, uid:%d, index:%d, ret:%s', $this->pid, $this->uid, $index, $ret);
		}
		catch (Exception $e)
		{
			MyLog::info('challenge failed. pid:%d, uid:%d, index:%d, erro:%s', $this->pid, $this->uid, $index, $e->getMessage());
		}
	}
	
	public function tryCheer($uid)
	{
		try
		{
			$ret = $this->cheer($uid);
			MyLog::info('cheer ok. pid:%d, uid:%d, cheerUid:%d, ret:%s', $this->pid, $this->uid, $uid, $ret);
		}
		catch (Exception $e)
		{
			MyLog::info('cheer failed. pid:%d, uid:%d cheerUid:%d, erro:%s', $this->pid, $this->uid, $uid, $e->getMessage());
		}
	}
	
	

	/*
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
	}*/
}

class OlympicRobot extends BaseScript
{

	protected $port = 7777;
	
	
	protected function executeScript($arrOption)
	{		
		MyLog::init('log_boss_robot');
		

		$botNum = 2;
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
				return;
				continue;
			}
			$arrRobot[$pid] = $robot;
			
		}
		if(empty($arrRobot))
		{
			MyLog::fatal('no robot');
			return;
		}
		
		$ob = current($arrRobot);
		$stage = 0;
		$status = 0;
		while(true)
		{
			$ret = $ob->getInfo();
			$stage = $ret['stage'];
			$status = $ret['status'];
			if( $stage == OlympicStage::PRE_OLYMPIC )
			{
				MyLog::info('before olympic. stage:%d, status:%d. please wait', $stage, $status);
				sleep(1);
			}
			else
			{
				break;
			}
		}
		MyLog::info('can join now. stage:%d, status:%d', $stage, $status);
		if( $stage == OlympicStage::PRELIMINARY_MATCH )
		{
			$index = 0;
			foreach ($arrRobot as $robot)
			{
				if($index <= OlympicDef::MAX_SIGNUP_INDEX)
				{
					$robot->trySign($index);
				}
				else
				{
					$robot->tryChallenge( $index % (OlympicDef::MAX_SIGNUP_INDEX+1) );
				}
				$index++;
			}
		}
		
		$hasCheer = false;
		while(true)
		{
			$ret = $ob->getInfo();
			$stage = $ret['stage'];
			$status = $ret['status'];
			MyLog::info('stage:%d, status:%d. please wait', $stage, $status);
			
			if( $hasCheer == false 
					&&  $stage == OlympicStage::SEMI_FINAL 
					&& $status == OlympicStageStatus::PREPARE )
			{
				$arrUid = array();
				foreach ( $ret['rank_list'] as $uid => $userInfo )
				{
					if( $userInfo['final_rank'] == 4 )
					{
						$arrUid[] = $uid;
					}
				}
				if( empty($arrUid) )
				{
					MyLog::fatal('no body to cheer');
					break;
				} 
				$index = 0;
				foreach ($arrRobot as $robot)
				{
					$robot->tryCheer( $arrUid[ $index % count($arrUid) ] );
					$index++;
				}
				$hasCheer = true;
			}
			sleep(1);
		}
		/*
		while(true)
		{
			MyLog::info('------------------[%s]', date('Y-m-d H:i:s') );
			foreach ($arrRobot as $robot)
			{
				$robot->run();
			}
			break;
			sleep(1);
		}
		*/
		printf("done\n");
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