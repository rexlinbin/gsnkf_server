<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordRobot.php 129052 2014-08-25 13:39:10Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/test/LordRobot.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-08-25 13:39:10 +0000 (Mon, 25 Aug 2014) $
 * @version $Revision: 129052 $
 * @brief 
 *  
 **/

require_once dirname (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/test/UserClient.php";
//require_once dirname (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Lordwar.def.php";

class Robot extends UserClient
{

	protected $err = false;
	
	protected $errTryNum = 1;
	
	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		
		MyLog::info('pid:%d login ok', $pid);
		
		
		
		$ret = $this->doConsoleCmd('silver 10000000');
		MyLog::info('console. ret:%s', $ret);
		
		$ret = $this->doConsoleCmd('level 66');
		MyLog::info('console. ret:%s', $ret) ;
		
		$this->setClass('lordwar');
		
	}
	
	public function tryRegister()
	{
		try
		{
			$ret = $this->register();
			MyLog::info('register ok. pid:%d, uid:%d, ret:%s', $this->pid, $this->uid, $ret);
		}
		catch (Exception $e)
		{
			MyLog::info('register failed. pid:%d, uid:%d, erro:%s', $this->pid, $this->uid, $e->getMessage());
		}
	}
	
	public function trySupport($pos, $teamType)
	{
		try
		{
			$ret = $this->support($pos, $teamType);
			MyLog::info('support ok. pid:%d, uid:%d, pos:%d, ret:%s', $this->pid, $this->uid, $pos, $ret);
		}
		catch (Exception $e)
		{
			MyLog::info('support failed. pid:%d, uid:%d pos:%d, erro:%s', $this->pid, $this->uid, $pos, $e->getMessage());
		} 
	}


}

class LordRobot extends BaseScript
{

	private $port = 7777;
	
	protected function executeScript($arrOption)
	{		
		MyLog::init( sprintf('log_robot_%s', RPCContext::getInstance()->getFramework()->getGroup()) );
		
		$botNum = 10;
		if( isset($arrOption[0]) )
		{
			$botNum = intval($arrOption[0]);
		}
		if( isset( $arrOption[1] ) )
		{
			$this->port = intval( $arrOption[1] );
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
			
		}
		if(empty($arrRobot))
		{
			MyLog::fatal('no robot');
			return;
		}
		
		$ob = current($arrRobot);

		$round = 0;
		$status = 0;
		while(true)
		{
			$ret = $ob->getLordInfo();
			$round = $ret['round'];
			$status = $ret['status'];
			if( $round < LordwarRound::REGISTER )
			{
				MyLog::info('before register. round:%d, status:%d. please wait', $round, $status);
				sleep(1);
			}
			else
			{
				break;
			}
		}
		
		if( $round == LordwarRound::REGISTER )
		{
			MyLog::info('can register now. stage:%d, status:%d', $round, $status);
	
			foreach ($arrRobot as $robot)
			{
				if( $ob->pid == $robot->pid )
				{
					continue;
				}
				$robot->tryRegister();
			}
		}
		
		while( $round < LordwarRound::INNER_AUDITION )
		{
			$ret = $ob->getLordInfo();
			$round = $ret['round'];
			$status = $ret['status'];
			MyLog::info('before promotion. curRound:%d, curStatus:%d', $round, $status);
			sleep(1);
		}
		
		
		$lastRound = 0;
		$lastStatus = 0;
		$hasInnerHis = false;
		$hasCrossHis = false;
		$hasSupport = false;
		while( $round >= LordwarRound::INNER_AUDITION 
				&& ( $round < LordwarRound::CROSS_2TO1  || $status < LordwarStatus::DONE) )
		{
			$ret = $ob->getLordInfo();
			$round = $ret['round'];
			$status = $ret['status'];
			MyLog::info('curRound:%d, curStatus:%d', $round, $status);
			
			try 
			{
				if( ( $round == LordwarRound::INNER_AUDITION  || $round == LordwarRound::CROSS_AUDITION  )
					&& $status <= LordwarStatus::FIGHTEND)
				{
					sleep(1);
					continue;
				}
				
			
				if( $round != $lastRound || $status != $lastStatus )
				{
					$promotionInfo = $ob->getPromotionInfo();
					MyLog::info('getPromotionInfo:%s', $promotionInfo);
					
					if( !$hasInnerHis && $round >= LordwarRound::CROSS_AUDITION )
					{
						$ret = $ob->getPromotionHistory(LordwarRound::INNER_2TO1);
						MyLog::info('getPromotionHistory inner:%s', $ret);
						$hasInnerHis = true;
					}
					if( ! $hasCrossHis && $round == LordwarRound::CROSS_2TO1 && $status >= LordwarStatus::FIGHTEND )
					{
						$ret = $ob->getPromotionHistory(LordwarRound::CROSS_2TO1);
						MyLog::info('getPromotionHistory cross:%s', $ret);
						$hasCrossHis = true;
					}
					if( $round != $lastRound )
					{
						$hasSupport = false;
					}
					
					if( $round < LordwarRound::CROSS_2TO1 
						&& $status >= LordwarStatus::REWARDEND  
						&& ! $hasSupport)
					{
						$pos = -1;
						
						$arrType = array(  LordwarTeamType::WIN => 'winLord', LordwarTeamType::LOSE => 'loseLord'  );
						
						$nextRound = LordwarUtil::getNextRound($round);
						$step = LordwarConf::AUDITION_PROMOTED_NUM/LordwarRound::$ROUND_RET_NUM[$nextRound];
						
				
						foreach( $arrType as $teamType => $keyField )
						{
							foreach($promotionInfo[$keyField] as $key => $value)
							{
								if( !empty($value['pid']) &&  $value['rank'] == LordwarRound::$ROUND_RET_NUM[$round]  )
								{
									$promoteeIndex = $key;
									$offset = floor( $promoteeIndex/$step ) * $step ;
									
									$fighters = LordwarLogic::getPromotionFighters($promotionInfo[$keyField], LordwarRound::$ROUND_RET_NUM[$nextRound], $offset);
									if( count( $fighters['fightArr'] ) != 2 )//轮空啦，计算错误啦
									{
										MyLog::debug('cant support pos:%d', $key);
										continue;
									}
									
									$pos = $key;
								}
							}
							if( $pos >= 0 )
							{
								$ret = $ob->trySupport($pos, $teamType);
								break;
							}
							else
							{
								MyLog::info('not found pos to support, teamType:%d', $teamType);
							}
						}
						
						
						$hasSupport = true;
					}
					
					$lastRound = $round;
					$lastStatus = $status;
				}
			}
			catch (Exception $e)
			{
				MyLog::fatal('some thing wrong:%s', $e->getMessage() );
			}
			sleep(1);
			
		}
		
		MyLog::info('done');
	}

	
	public function getArrPidForBot($num = 10)
	{
		$arrDefPid = array();
	
		if(count($arrDefPid) >=  $num)
		{
			return array_slice($arrDefPid, 0, $num);
		}
	
		$leftNum = $num - count($arrDefPid);
	
		$data = new CData();
		$arrRet = $data->select( array('pid') )->from('t_user')
					->where('status', '=', UserDef::STATUS_OFFLINE)
					->where('pid', '>', UserConf::PID_MAX_RETAIN)
					//->where('level','<',40)
					->orderBy('pid', false)
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