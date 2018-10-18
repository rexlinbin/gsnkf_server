<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FriendLoveObj.class.php 108348 2014-05-15 03:55:34Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/FriendLoveObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-05-15 03:55:34 +0000 (Thu, 15 May 2014) $
 * @version $Revision: 108348 $
 * @brief 
 *  
 **/
class FriendLoveObj 
{
	private $allLove = null;
	private $allLoveBak = null;
	private $uid = null;
	private static $instance = null;
	
	/**
	 * 获取唯一实例
	 * @return FriendLoveObj
	 */
	public static function getInstance()
	{
		if ( self::$instance == null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public static function release()
	{
		if (self::$instance != null)
		{
			self::$instance = null;
		}
	}
	
	function __construct()
	{
		$loveInsession = RPCContext::getInstance()->getSession( 'friend.love' );
		$this->uid = RPCContext::getInstance()->getUid();
		$this->allLove = $loveInsession;
		if ( empty( $this->allLove ) )
		{
			$this->allLove = FriendDao::getAllLove( $this->uid );
			if ( empty( $this->allLove ) )
			{
				$this->allLove = $this->initLove();
			}
		}
		
		$this->allLoveBak = $this->allLove;
		$this->checkRef();
		if ( $this->allLove != $loveInsession )
		{
			RPCContext::getInstance()->setSession('friend.love', $this->allLove);
		}
	}
	
	private function initLove()
	{
		$ini_arr = array(
				'uid' => $this->uid,
				'num' => 0,
				'reftime' => Util::getTime(),
				'pk_num' => 0,
				'bepk_num' => 0,
				'va_love' => array(),
		);
		FriendDao::insertAllLove( $this->uid, $ini_arr );
		
		return $ini_arr;
	}
	
	public function getAllLove()
	{
		return $this->allLove;
	}
	
	public function checkRef()
	{
		$nowtime = util::getTime();
		if ( !Util::isSameDay( $this->allLove['reftime'] ) )
		{
			$this->allLove['reftime'] =Util::getTime();
			$this->allLove['num'] = 0;
			$this->allLove['pk_num'] = 0;
			$this->allLove['bepk_num'] = 0;
		}
		$vaLove = $this->allLove['va_love'];
		//拉取的时候过滤过期（15天）的，添加的时候过滤超过60（可领取列表的上限，也即va条数的上限
		foreach ( $vaLove as $key => $exeinfo )
		{
			if ( $nowtime - $exeinfo['time'] > FriendCfg::MAX_KEEP_TIME )
			{
				unset( $vaLove[ $key ] );
			}
		}
		$this->allLove['va_love'] = $vaLove;
		$this->update();
	}
	
	
	public function subUnfriendLove( $fuid )
	{
		$vaLove = $this->allLove['va_love'];
		foreach ( $vaLove as $index => $loveInfo )
		{
			if ( $loveInfo['uid'] ==  $fuid )
			{
				Logger::info('unset unfriendlove uid: %d, time: %d',$fuid, $loveInfo['time'] );
				unset( $vaLove[$index] );
			}
		}
		$this->allLove['va_love'] = $vaLove;
	}
	
	public function lovedByOther( $uid )
	{
		$veryNew = false;
		//此处$uid不是用户自己的uid，是送给他体力的那个人的uid
		$loveExecution = $this->allLove['va_love'];
		if ( count( $loveExecution ) > FriendCfg::MAX_KEEP_NUM )
		{
			Logger::fatal( 'execution num: %d beyond max: %d', count( $loveExecution ), FriendCfg::MAX_KEEP_NUM );
		}
		while( count( $loveExecution ) >= FriendCfg::MAX_KEEP_NUM )
		{
			
			array_shift( $loveExecution );
			//新赠送的顶掉老的，如果时间一致则按照index的先后顺序,新增加的index要大，shift掉index为0的即可
			$veryNew = true;
		}
		$loveExecution[] = array( 'time' => Util::getTime(), 'uid' => $uid );
		
		$this->allLove['va_love'] = $loveExecution;
		
		return $veryNew;
	}
	
	public function addReceiveNum( $num )
	{
		$this->allLove['num'] += $num;
	}
	
	public function setVaLove( $vaLove )
	{
		$this->allLove['va_love'] = $vaLove;
	}
	
	public function addPkNum()
	{
		$this->allLove['pk_num'] ++;
	}
	
	public function addBepkNum()
	{
		$this->allLove['bepk_num'] ++;
	}
	
	public function update()
	{
		$updateArr = array();
		$vaLove = $this->allLove['va_love'];
		$this->allLove['va_love'] = array_merge( $vaLove );
		foreach ( $this->allLove as $key => $val )
		{
			if ( $val != $this->allLoveBak[ $key ] )
			{
				$updateArr[ $key ] = $val;
			}
		}
		if ( empty( $updateArr ) )
		{
			return;
		}
		FriendDao::updateAllLove( $this->uid, $updateArr );
		$this->allLoveBak = $this->allLove;
		RPCContext::getInstance()->setSession( 'friend.love' , $this->allLove);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */