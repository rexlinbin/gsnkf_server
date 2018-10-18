<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RPCContext.class.php 258416 2016-08-25 06:56:00Z GuohaoZheng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/RPCContext.class.php $
 * @author $Author: GuohaoZheng $(hoping@babeltime.com)
 * @date $Date: 2016-08-25 06:56:00 +0000 (Thu, 25 Aug 2016) $
 * @version $Revision: 258416 $
 * @brief
 *
 **/

class RPCContext
{

	private function __construct()
	{

		$this->framework = null;
		$this->arrListenerList = array ();
		$this->arrCallbackList = array ();
	}

	private static $instance = null;

	private $framework;

	private $arrCallbackList;

	
	/**
	 * 得到RPCContext的实例
	 * @return RPCContext
	 */
	public static function getInstance()
	{

		if (empty ( self::$instance ))
		{
			self::$instance = new RPCContext ();
		}
		return self::$instance;
	}

	public function getRequestTime()
	{

		$arrRequest = $this->getRequest ();
		return $arrRequest ['time'];
	}

	public function setFramework(RPCFramework $framework)
	{

		$this->framework = $framework;
	}

	public function getSession($key)
	{

		return $this->getFramework ()->getSession ( $key );
	}

	public function getSessions()
	{

		return $this->getFramework ()->getSessions ();
	}

	public function setSessions($arrSession)
	{

		$this->getFramework ()->setSessions ( $arrSession );
	}

	public function unsetSession($key)
	{

		$this->getFramework ()->unsetSession ( $key );
	}

	public function setSession($key, $value)
	{

		return $this->getFramework ()->setSession ( $key, $value );
	}

	public function getRequest()
	{

		return $this->getFramework ()->getRequest ();
	}

	public function resetSession()
	{

		$this->getFramework ()->resetSession ();
	}

	/**
	 * @return RPCFramework
	 */
	public function getFramework()
	{

		if ($this->framework == null)
		{
			$this->framework = new RPCFramework ();
		}
		return $this->framework;
	}
	
	public function getUid()
	{
	
		return intval ( $this->getSession ( 'global.uid' ) );
	}
	public function getCallback()
	{
	
		return $this->arrCallbackList;
	}
	
	public function delAllCallBack()
	{
	    $this->arrCallbackList = array();
	}
	
	
	

	/**
	 *
	 * @param array $arrTargetUid    如果发生给所有用户此参数为array(0)
	 * @param string $callback        发生消息的callback，前端提供的接口
	 * @param array $arrArg
	 * @throws Exception
	 */
	public function sendMsg($arrTargetUid, $callback, $arrArg)
	{
	
		$arrTargetUid = array_unique ( $arrTargetUid );
		foreach ( $arrTargetUid as $index => $uid )
		{
			if (! is_numeric ( $uid ))
			{
				Logger::fatal ( "uid:%s is not integer", $uid );
				throw new Exception ( 'inter' );
			}
			$arrTargetUid [$index] = intval ( $uid );
		}
		
		$arrTargetUid = array_merge($arrTargetUid);
	
		$this->addCallback ( 'sendMsg',
				array ($arrTargetUid,
						array ('err' => 'ok', 'callback' => array ('callbackName' => $callback ),
								'ret' => $arrArg ) ) );
	}

	/**
	 * 过滤类型
	 * @param string $filterType group|guild|copy|resource|harbor|town|treasure|arena
	 * @param int $filterValue 对应的id
	 * @param string $callback 前端回调函数名
	 * @param mixed $ret 前端回调对应的参数
	 * @param string $err 是否有异常
	 */
	public function sendFilterMessage($filterType, $filterValue, $callback, $ret, $err = "ok")
	{

		$this->addCallback ( 'sendFilterMessage',
				array ($filterType, $filterValue,
						array ('callback' => array ('callbackName' => $callback ), 'err' => $err,
								'ret' => $ret ) ) );
	}

	public function broadcast($callback, $arrRet)
	{
	
		return $this->sendMsg ( array (0 ), $callback, $arrRet );
	}
	
	
	public function closeConnection($uid)
	{
	
		$this->addCallback ( 'closeConnection', array (intval ( $uid ) ) );
	}
	

	public function executeRequest($arrRequest)
	{
	
		$arrRequest ['private'] = true;
		return $this->getFramework ()->executeRequest ( $arrRequest, false );
	}
	
	public function executeTask($uid, $method, $arrArg, $isAsync = true, $callback = 'dummy')
	{
	
		$token = strval ( $this->getFramework ()->getLogid () + 100 );
		$this->addCallback ( 'asyncExecuteRequest',
				array ($uid,
						array ('method' => $method, 'args' => $arrArg, 'token' => $token,
								'backend' => $this->framework->getLocalIp (),
								'callback' => array ('callbackName' => $callback ) ), $isAsync ) );
	}
	
	public function addTimer($uid, $executeTime, $method, $arrArg, $isAsync = true, $callback = 'dummy')
	{
	
		$token = strval ( $this->getFramework ()->getLogid () + 100 );
		$this->addCallback ( 'addTimer',
				array ($uid, $executeTime,
						array ('method' => $method, 'args' => $arrArg, 'token' => $token,
								'backend' => $this->framework->getLocalIp (),
								'callback' => array ('callbackName' => $callback ) ), $isAsync ) );
	}
	
	/**
	 * 执行一个用户请求
	 * @param string $method
	 * @param array $arrArg
	 * @param array $arrCallback 注意这里必须是一个前端格式的callback，比如{callbackName:xxxx}
	 */
	public function executeUserTask($method, $arrArg, $arrCallback)
	{
	
		$token = strval ( $this->getFramework ()->getLogid () + 100 );
		$this->addCallback ( 'execUserRequest',
				array (
						array ('method' => $method, 'args' => $arrArg, 'token' => $token,
								'backend' => $this->framework->getLocalIp (),
								'callback' => $arrCallback ) ) );
	}
	
	public function asyncExecuteTask($method, $arrArg, $executeTimeout = 1000, $retry = 10)
	{
	
		$token = strval ( $this->getFramework ()->getLogid () + 100 );
	
		$arrRequest = array ('method' => $method, 'args' => $arrArg, 'token' => $token,
				'backend' => $this->framework->getLocalIp (),
				'recursLevel' => $this->getFramework ()->getRecursLevel () + 1,
				'callback' => array ('callbackName' => 'dummy' ), 'private' => true );
	
		$compress = false;
		$request = Util::amfEncode ( $arrRequest, $compress );
		Logger::info ( "asyncExecuteTask: method:%s, request:%s", $method,
		base64_encode ( $request ) );
	
		$this->addCallback ( 'asyncExecuteLong', array ($arrRequest, $executeTimeout, $retry ) );
	}
	
	public function addConnection()
	{
	
		$this->addCallback ( 'addConnection', array () );
	}
	
	public function addListener($method, $arrArgs = array())
	{
	
		$this->addCallback ( 'addListener',
				array (array ('method' => $method, 'callback' => $method, 'args' => $arrArgs ) ) );
	}
	
	private function addCallback($method, $arrArg)
	{
	
		$this->arrCallbackList [] = array ('method' => $method, 'args' => $arrArg );
	}
	
	public function delConnection($uid)
	{
	
		$this->addCallback ( 'delConnection', array ($uid ) );
	}
	
	
	/**
	 * 创建组队
	 * @param bool $isAutoStart
	 * @param int $joinLimit
	 * @param string $startMethod
	 */
	public function createTeam($isAutoStart, $joinLimit, $startMethod = '')
	{
	
		$this->addCallback ( 'createTeam',
				array ($isAutoStart, $joinLimit, $startMethod, $this->framework->getCallback () ) );
		$this->framework->resetCallback ();
	}
	
	public function joinTeam($teamId)
	{
	
		$this->addCallback ( 'joinTeam', array ($teamId, $this->framework->getCallback () ) );
		$this->framework->resetCallback ();
	}
	
	public function createGroupBattle($robId, $startTime, $battleInfo)
	{
		$this->addCallback('createGroupBattle', array($robId, $startTime, $battleInfo, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function enterGroupBattle($robId, $userData)
	{
		$this->addCallback('enterGroupBattle', array($robId, $userData, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function getGroupBattleEnterInfo($robId, $userData, $extraData)
	{
		$this->addCallback('getGroupBattleEnterInfo', array($robId, $userData, $extraData, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function joinGroupBattle($uid, $robId, $transferId, $battleData)
	{
		$this->addCallback('joinGroupBattle', array($uid, $robId, $transferId, $battleData));
		$this->framework->resetCallback ();
	}
	
	public function freeGroupBattle($robId)
	{
		$this->addCallback('freeGroupBattle', array($robId));
	}
	
	public function removeGroupBattleJoinCd()
	{
		$this->addCallback("removeGroupBattleJoinCd", array());
	}
	
	public function broadcastGroupBattle($robId, $msg, $callback, $err = 'ok')
	{
		$msgData = array('err' => $err, 'ret' => $msg, 'callback' => array('callbackName' => $callback));
		$this->addCallback ("broadcastGroupBattle", array ($robId, $msgData));
	}
	
	public function leaveGroupBattle()
	{
		$this->addCallback('leaveGroupBattle', array($this->getFramework()->getCallback()));
		$this->getFramework()->resetCallback();
	}
	
	public function speedUpGroupBattle($multiple)
	{
		$this->addCallback("speedUpGroupBattle", array($multiple));
	}
	
	public function endGroupBattle($robId)
	{
		$this->addCallback('endGroupBattle', array($robId));
	}
	
	public function enterCountryBattle($battleId, $userData)
	{
		$this->addCallback('enterCountryBattle', array($battleId, $userData, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}

	public function getCountryBattleEnterInfo($robId, $userData)
	{
		$this->addCallback('getCountryBattleEnterInfo', array($robId, $userData, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function joinCountryBattle($uid, $robId, $transferId, $battleData)
	{
		$this->addCallback('joinCountryBattle', array($uid, $robId, $transferId, $battleData));
		$this->framework->resetCallback ();
	}
	
	public function freeCountryBattle($robId)
	{
		$this->addCallback('freeCountryBattle', array($robId));
	}
	
	public function removeCountryBattleJoinCd()
	{
		$this->addCallback("removeCountryBattleJoinCd", array());
	}
	
	public function broadcastCountryBattle($robId, $msg, $callback, $err = 'ok')
	{
		$msgData = array('err' => $err, 'ret' => $msg, 'callback' => array('callbackName' => $callback));
		$this->addCallback ("broadcastCountryBattle", array ($robId, $msgData));
	}
	
	public function leaveCountryBattle()
	{
		$this->addCallback('leaveCountryBattle', array($this->getFramework()->getCallback()));
		$this->getFramework()->resetCallback();
	}
	
	public function hpRecoverCountryBattle($userData)
	{
		$this->addCallback('hpRecoverCountryBattle', array( $userData, $this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function inspireCountryBattle($type,$cost,$level)
	{
		$this->addCallback('inspireCountryBattle', array( $type,$cost,$level,$this->framework->getCallback()));
		$this->framework->resetCallback ();
	}
	
	public function setOfflineBattleData($uid, $battleId, $battleData)
	{
	    $this->addCallback('setOfflineBattleData', array($battleId, $uid, $battleData));
	    $this->framework->resetCallback();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
