<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GameProxy.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/GameProxy.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 用于和远程lcserver服务器进行交互
 *
 **/
class GameProxy
{

	/**
	 * 服务器id
	 * @var string
	 */
	private $group;

	/**
	 * 日志id
	 * @var string
	 */
	private $logid;

	/**
	 * 要请求的地址
	 * @var string
	 */
	private $url;

	/**
	 * 构造函数
	 * @param string $url
	 */
	public function __construct($url)
	{

		$this->url = $url;
	}

	/**
	 * proxy的初始化工作
	 * @param string $group
	 * @param string $logid
	 */
	public function init($group, $logid)
	{

		$this->group = $group;
		$this->logid = $logid;
	}

	/**
	 * 执行方法
	 * @param string $method
	 * @param array $arrArg
	 * @throws Exception
	 */
	private function execute($method, $arrArg)
	{

		$proxy = new HTTPClient ( $this->url );
		$proxy->setHeader ( 'GAME_ADDR', ScriptConf::PRIVATE_HOST );
		$proxy->setHeader ( 'GAME_GROUP', $this->group );

		$arrRequest = $this->makeRequest ( $method, $arrArg );
		$postData = Util::amfEncode ( $arrRequest );
		$ret = $proxy->post ( $postData );
		$arrRet = Util::amfDecode ( $ret );

		if (empty ( $arrRet ['response'] ))
		{
			Logger::fatal ( "GameProxy request:%s decode failed:%s", $arrRequest, $ret );
			throw new Exception ( 'inter' );
		}

		$response = $arrRet ['response'];
		if (is_array ( $response ))
		{
			$arrResponse = $response;
		}
		else
		{
			$arrResponse = Util::amfDecode ( $response );
		}

		if (empty ( $arrResponse ['err'] ))
		{
			Logger::fatal ( "GameProxy request:%s deocde failed:%s", $arrRequest, $ret );
			throw new Exception ( 'inter' );
		}

		if ($arrResponse ['err'] != 'ok')
		{
			Logger::fatal ( "GameProxy request:%s return failed:%s", $arrRequest,
					$arrResponse ['err'] );
			throw new Exception ( $arrResponse ['err'] );
		}

		return $arrResponse ['ret'];
	}

	/**
	 * 构造一个合法的请求出来
	 * @param string $method
	 * @param array $arrArg
	 */
	private function makeRequest($method, $arrArg)
	{

		$this->logid ++;
		$arrRequest = array ('session' => array ('global.dummy' => true ),
				'isession' => array ('global.dummy' => true ),
				'request' => array ('method' => $method, 'private' => true, 'args' => $arrArg,
						'callback' => array ('callbackName' => 'dummy' ),
						'token' => strval ( $this->logid ) ) );
		return $arrRequest;
	}

	/**
	 * 异步请求
	 * @param int $uid
	 * @param strign $method
	 * @param array $arrArg
	 */
	private function asyncExecute($uid, $method, $arrArg)
	{

		$arrArg = array ($uid, $method, $arrArg );
		return $this->execute ( 'proxy.asyncExecute', $arrArg );
	}

	/**
	 * 同步请求
	 * @param string $method
	 * @param array $arrArg
	 */
	private function syncExecute($method, $arrArg)
	{

		$arrArg = array ($method, $arrArg );
		return $this->execute ( 'proxy.syncExecute', $arrArg );
	}

	/**
	 * 获取总在线用户数
	 * @return int
	 */
	public function getTotalUserCount()
	{

		return $this->execute ( 'proxy.getTotalUserCount', array () );
	}

	/**
	 * 将某个用户踢下线
	 * @param int $uid
	 */
	public function closeUser($uid)
	{

		return $this->execute ( 'proxy.closeUser', array ($uid ) );
	}

	/**
	 * 获取战斗结果
	 * @param int $brid
	 * @return string 战斗串
	 */
	public function getBattleRecord($brid)
	{

		return $this->syncExecute ( 'battle.getRecord', array ($brid ) );
	}

	/**
	 * 获取前几名的公会信息
	 * @param int $limit
	 * @return array
	 * <code>
	 * [{
	 * guild_id:工会id
	 * name:工会名称
	 * president_uid:工会会长uid
	 * president_uname:会长名称
	 * rank:排名
	 * }]
	 * </code>
	 */
	public function getTopGuild($limit)
	{

		return $this->syncExecute ( 'guild.getTopGuild', array ($limit ) );
	}

	/**
	 * 给用户增加金币
	 * @param int $uid 用户uid
	 * @param string $orderId 订单id
	 * @param int $goldNum 金币数量
	 * @param int $returnNum 返还金币数量
	 */
	public function addGold($uid, $orderId, $goldNum, $returnNum)
	{

		return $this->asyncExecute ( $uid, 'user.addGold4BBpay',
				array ($uid, $orderId, $goldNum, $returnNum ) );
	}

	/**
	 * 通知用户有新的gm回复
	 * @param int $uid
	 */
	public function notifyNewGmResponse($uid)
	{

		return $this->asyncExecuteRequest ( 0, 'gm.newResponse', array ($uid ) );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
