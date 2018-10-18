<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarRobot.test.php 218764 2015-12-30 10:33:51Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/test/CountryWarRobot.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-30 10:33:51 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218764 $
 * @brief 
 *  
 **/
require_once dirname (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/test/UserClient.php";

class CwRobot extends UserClient
{
	private $signTime = 0;
	private $supportCountry = 0;
	private $worshipTime = 0;
	
	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		printf('pid:%d login ok\n', $pid);
		
		$ret = $this->doConsoleCmd('level 90');
		$ret = $this->doConsoleCmd('gold 3000');
		MyLog::info('console. ret:%s', $ret) ;
		$this->setClass('countrywarinner');
		$this->exchangeCocoin(1000);
	}
	
	public function signI()
	{
		try 
		{
			$cou = rand(1 , 4);
			$ret = $this->signForOneCountry($cou);
		}
		catch (Exception $e)
		{
			printf('pid:%d sign fail \n', $this->pid);
		}
	}
	public function getLoginInfoI()
	{
		try
		{
			$ret = $this->getLoginInfo();
		}
		catch (Exception $e)
		{
			printf('pid:%d getlogin fail\n', $this->pid);
		}
		return $ret;
	}
	public function getCountryWarInfoI()
	{
		try
		{
			$ret = $this->getCoutrywarInfo();
		}
		catch (Exception $e)
		{
			printf('pid:%d getCountryWarInfoI fail\n', $this->pid);
		}
		return $ret;
	}
	public function supportUserI()
	{
		$serverId = Util::getServerIdOfConnection();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $this->pid);
		$teamRoomId = $crossUser->getTeamRoomId();
		$allFinalMembers = CountryWarCrossUser::getAllFinalMembers(CountryWarConfig::roundStartTime(time()), $teamRoomId);
		if( empty( $allFinalMembers ) )
		{
			return;
		}
		try
		{
			$this->supportOneUser( $allFinalMembers[0]['pid'],$allFinalMembers[0]['server_id'] );
		}
		catch (Exception $e)
		{
			printf('pid:%d supportOneUser fail, \n', $this->pid);
		}
	}
	
	public function supportCountryI()
	{
		try
		{
			$this->supportFinalSide( 1 );
			$this->supportCountry = 1;
		}
		catch (Exception $e)
		{
			printf('pid:%d supportFinalSide fail, \n', $this->pid);
		}
	}
	
	public function worshipI()
	{
		$this->worship();
		$this->worshipTime = time();
		
	}
	
	public function getStage()
	{
		return CountryWarConfig::getStageByTime(time());
	}
	public function op()
	{
		$stage = $this->getStage();
		switch ($stage)
		{
			case CountryWarStage::TEAM:
			case CountryWarStage::RANGE_ROOM:
			printf( "[%s],ignore by innerrobot,stage: %s \n", date( 'Ymd H:i:s', time() ), $stage );
			break;
			case CountryWarStage::SINGUP:
				if( empty( $this->signTime ) )
				{
					$this->signI();
					$this->signTime = time();
				}
				break;
			case CountryWarStage::SUPPORT:
				$this->getCountryWarInfoI();
				if( empty($this->supportCountry) )
				{
					$this->supportCountryI();
					$this->supportUserI();
				}
				break;
			case CountryWarStage::WORSHIP:
				$this->getCountryWarInfoI();
				if( empty( $this->worshipTime ) )
				{
					$this->worshipI();
					exit();
				}
		}
		
	}
}
class CwRobotCross extends UserClient
{
	private $serverId = 0;
	//private $pid = 0; 
	private $signTime=0;
	private $loginTime=0;
	private $enterTime = 0;
	private $enterFinalTime = 0;
	private $joinCd = 30;
	private $lastJoinTime = 0;
	private $lastJoinFinalTime = 0;
	private $signCountry = 0;
	private $side = 0;
	private $finalMember = NULL;
	function __construct($server, $port )
	{
		parent::__construct($server, $port, NULL,NULL,false);
		printf('server:%d,port:%s connet ok\n', $server,$port);
		$this->setClass('countrywarcross');
	}
	public function getLoginInfoR()
	{
		try
		{
			$ret = $this->getLoginInfo();
		}
		catch (Exception $e)
		{
			printf('pid:%d getlogin fail\n', $this->pid);
		}
		return $ret;
	}
	public function loginCrossR( $serverId,$pid, $token )
	{
		try
		{
			$ret = $this->loginCross( $serverId,$pid, $token );
			$this->serverId = $serverId;
			$this->pid = $pid;
		}
		catch (Exception $e)
		{
			printf('pid:%d loginCross fail, ret:%s\n', $this->pid);
		}
		
	}
	public function enterR( $countryId = NULL )
	{
		try
		{
			$ret = $this->enter($countryId);
		}
		catch (Exception $e)
		{
			printf('pid:%d enter fail\n', $this->pid);
		}
	
		return $ret;
	}
	public function getEnterInfoR()
	{
		try
		{
			$ret = $this->getEnterInfo();
			var_dump( $ret );
		}
		catch (Exception $e)
		{
			printf('pid:%d getEnterInfo fail, \n', $this->pid);
		}
	
		return $ret;
	}
	public function joinTransferR( $transferId )
	{
		try
		{
			$ret = $this->joinTransfer( $transferId );
			$this->lastJoinTime = time();
		}
		catch (Exception $e)
		{
			printf('pid:%d transferId fail, ret:%s\n', $this->pid, $ret);
		}
	
		return $ret;
	}
	public function recoverR()
	{
		try
		{
			$ret = $this->recoverByUser();
		}
		catch (Exception $e)
		{
			printf('pid:%d recoverByUser fail \n', $this->pid);
		}
	}
	
	public function inspireR()
	{
		try
		{
			$ret = $this->inspire(CountryWarConf::INSPIRE_DED);
		}
		catch (Exception $e)
		{
			printf('pid:%d inspire fail \n', $this->pid);
		}
	
	}
	
	public function setRecoverParaR()
	{
		try
		{
			$ret = $this->setRecoverPara(9990);
		}
		catch (Exception $e)
		{
			printf('pid:%d setRecoverPara fail \n', $this->pid);
		}
	
		return $ret;
	}
	
	public function turnAutoRecoverR()
	{
		try
		{
			$ret = $this->turnAutoRecover(CountryWarConf::AUTO_RECOVER_ON);
		}
		catch (Exception $e)
		{
			printf('pid:%d setRecoverPara fail \n', $this->pid);
		}
	
		return $ret;
	}
	
	public function leaveR()
	{
		try
		{
			$ret = $this->leave();
		}
		catch (Exception $e)
		{
			printf('pid:%d leave fail, ret:%s\n', $this->pid, $ret);
		}
	
		return $ret;
	}
	
	public function getStage()
	{
		return CountryWarConfig::getStageByTime(time());
	}
	
	public function op(  )
	{
		$stage = $this->getStage();
		switch ($stage)
		{
			case CountryWarStage::TEAM:
			case CountryWarStage::RANGE_ROOM:
			case CountryWarStage::SINGUP:
				printf( "[%s],ignore by crossRobot,stage: %s \n", date( 'Ymd H:i:s', time() ), $stage );
				break;
			case CountryWarStage::AUDITION:
				$rankList = $this->getRankListR();
				if( empty( $this->enterTime ) )
				{
					$this->enterR($this->getCountryId());
					$this->getEnterInfoR();
					$this->setRecoverParaR();
					$this->turnAutoRecoverR();
					$this->enterTime = time();
					
				}
				if( empty($this->lastJoinTime) || $this->lastJoinTime + $this->joinCd < time() )
				{
					//$rankList = $this->getRankListR();
					//var_dump("rank list in audition \n");
					//var_dump( $rankList ); 
					
					$transferId = CountryWarUtil::getTransferIdBySide($this->getSide());
					$this->joinTransferR($transferId);
					$this->inspireR();
					$this->recoverR();//就这么写着吧
					$this->lastJoinTime = time();
				}
				else
				{
					$data = $this->receiveAnyData();
					if( isset($data[0]['callback']['callbackName']) && $data[0]['callback']['callbackName'] == 'push.countrywarcross.refresh' )
					{
						break;
					}
					printf("receive data pid:%s \n",$this->pid );
					var_dump( $data );
				}
				break;
			case CountryWarStage::FINALTION:
				if( !$this->isFinalMember() )
				{
					printf("pid: %s, not a final member",$this->pid );
					break;
				}
				$rankList = $this->getRankListR();
				if( empty( $this->enterFinalTime ) )
				{
					//CountryWarUtil::getBattIdArr($teamRoomId)
					CountryWarCrossUser::releaseInstance();
					$this->enterR();
					$this->getEnterInfoR();
					$this->enterFinalTime = time();
				}
				if( empty($this->lastJoinFinalTime) || $this->lastJoinFinalTime + $this->joinCd < time() )
				{
					//$rankList = $this->getRankListR();
					//var_dump("rank list in finaltion \n");
					//var_dump( $rankList );
					$finalSide = CountryWarUtil::getFinalSideByCountryId(time(),$this->getCountryId());
					$transferId = CountryWarUtil::getTransferIdBySide($finalSide);
					Logger::debug('side and country in final:%s, %s',$finalSide,$this->getCountryId());
					$this-> joinTransferR($transferId);
					$this->lastJoinFinalTime = time();
				}
				else
				{
					$data = $this->receiveAnyData();
					printf("receive data pid:%s \n",$this->pid );
					if( isset($data[0]['callback']['callbackName']) && $data[0]['callback']['callbackName'] == 'push.countrywarcross.refresh' )
					{
						break;
					}
					var_dump( $data );
				}
				break;
				
		}
	}
	public function getCountryId()
	{
		if( empty($this->signCountry) )
		{
			CountryWarCrossUser::releaseInstance();
			$this->signCountry = CountryWarCrossUser::getInstance($this->serverId, $this->pid)->getCountryId();
		}
		return $this->signCountry;
	}
	public function getSide()
	{
		if( empty($this->side) )
		{
			CountryWarCrossUser::releaseInstance();
			$this->side = CountryWarCrossUser::getInstance($this->serverId, $this->pid )->getSide();
			Logger::debug('side in robot:%s', $this->side);
		}
		return $this->side;
	}
	public function setCountryId($countryId)
	{
		$this->signCountry = $countryId;
	}
	public function setSide($side)
	{
		$this->side = $side;
	}
	public function isFinalMember()
	{
		if( $this->finalMember === NULL )
		{
			CountryWarCrossUser::releaseInstance();
			$this->finalMember = CountryWarCrossUser::getInstance($this->serverId, $this->pid)->isFinalMember();
		}
		return $this->finalMember;
	}
	
	public function getRankListR()
	{
		$rankList = array();
		try 
		{
			$rankList = $this->getRankList();
		}
		catch (Exception $e)
		{
			printf( 'getrank fail' );
		}
		return $rankList;
	}
	
}

class CountryWarRobot extends BaseScript
{
	private $ipaddr = ScriptConf::PRIVATE_HOST;
	private $pid = 2111;
	private $port = 7777;
	private $robotNum = 1;
	private $innerRobot = array();
	private $crossRobot = array();
	private $teamTime = 0;
	private $rangeTime = 0;
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption) 
	{
		if( isset( $arrOption[0] ) )
		{
			$this->port = intval( $arrOption[0] );
		}
		if( isset( $arrOption[1] ) )
		{
			$this->robotNum = intval( $arrOption[1] );
		}
		MyLog::init( 'cwrobotlog' );
		
		$pidArr = $this->getPidArr($this->robotNum);
		foreach ($pidArr as $index => $pidInfo )
		{
			$this->innerRobot[$pidInfo['pid']] = new CwRobot($this->ipaddr, $this->port, $pidInfo['pid']);
		}
		$this->crossRobot = array();
		while ( true )
		{
			usleep( 10 );
			$stage = CountryWarConfig::getStageByTime(time());
			printf( "[%s],wait==========,stage: %s \n", date( 'Ymd H:i:s', time() ), $stage );
			switch ( $stage )
			{
				/* case CountryWarStage::TEAM:
					if( empty($this->teamTime) )
					{
						CountryWarScrLogic::syncAllTeamFromPlat2Cross();
						$this->teamTime = time();
					}
					break;
				case CountryWarStage::RANGE_ROOM:
					if( empty( $this->rangeTime ) )
					{
						CountryWarLogic::scrRangeRoom(false);
						$this->rangeTime = time();
					}
					break; */
				case CountryWarStage::AUDITION:
					if( empty( $this->crossRobot ) )
					{
						foreach ($this->innerRobot as $onePid => $robot)
						{
							$loginInfo = $robot->getLoginInfoI();
							$crossIp = $loginInfo['serverIp'];
							$port = $loginInfo['port'];
							$token = $loginInfo['token'];
							$this->crossRobot[$onePid] = new CwRobotCross($crossIp, $port);
							$this->crossRobot[$onePid]->loginCrossR(Util::getServerIdOfConnection(), $onePid, $token);
						}
					}
					break;
				default:
					break;
			}
			
			$this->adaptOp();
		}
		
	}
	
	
	function adaptOp()
	{
		foreach ( $this->innerRobot as $pid => $robot )
		{
			try
			 {
				$robot->op();
				if( isset( $this->crossRobot[$pid] ))
				{
					$this->crossRobot[$pid]->op();
				}
			}
			catch ( Exception $e )
			{
				printf( "try op fail \n" );
			}
				
		}
		
	}
	
	
	function getPidArr( $num, $db = '' )
	{
		$data = new CData();
		if( !empty( $db ) )
		{
			$data->useDb($db);
		}
		
		$ret = $data ->select( array('pid','uid') )->from( 't_user' )
		->where(array( 'uid','>',20000 ))
		->where( array('status','>',1) ) 
		->limit(0, $num)
		->query();
		Logger::debug('all getpid:%s',$ret);
		return $ret;
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
