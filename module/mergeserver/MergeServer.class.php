<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MergeServer.class.php 135595 2014-10-10 05:00:49Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/MergeServer.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-10-10 05:00:49 +0000 (Fri, 10 Oct 2014) $
 * @version $Revision: 135595 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
 * Class       : MergeServer
 * Description : 合服活动外部实现类
 * Inherit     : IMergeServer
 **********************************************************************************************************************/
class MergeServer implements IMergeServer
{
	/**
	 * uid 用户id 
	 * 
	 * @var int
	 * @access private
	 */
	private $uid;
	
	/**
	 * __construct 构造函数 
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see IMergeServer::getRewardInfo()
	 */
	public function getRewardInfo()
	{	
		return MergeServerLogic::getRewardInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IMergeServer::receiveLoginReward()
	 */
	public function receiveLoginReward($day)
	{
		$day = intval($day);
		
		if($day <= 0)
		{
			throw new FakeException('receiveLoginReward invalid params day[%d].', $day);
		}
		
		return MergeServerLogic::receiveLoginReward($this->uid, $day);
	}
	
	/* (non-PHPdoc)
	 * @see IMergeServer::receiveRechargeReward()
	 */
	public function receiveRechargeReward($num)
	{
		$num = intval($num);
		
		if($num <= 0)
		{
			throw new FakeException('receiveRechargeReward invalid params num[%d].', $num);
		}
		
		return MergeServerLogic::receiveRechargeReward($this->uid, $num);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
