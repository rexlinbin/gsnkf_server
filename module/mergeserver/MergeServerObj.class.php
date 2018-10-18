<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MergeServerObj.class.php 137358 2014-10-23 10:09:35Z BaoguoMeng $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/MergeServerObj.class.php $$
 * @author $$Author: BaoguoMeng $$(mengbaoguo@babeltime.com)
 * @date $$Date: 2014-10-23 10:09:35 +0000 (Thu, 23 Oct 2014) $$
 * @version $$Revision: 137358 $$
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : MergeServerObj 
 * Description : 合服活动数据类
 * Inherit     : 
 **********************************************************************************************************************/
class MergeServerObj
{
	/**
	 * $sArrInstance 用户合服活动缓存数组
	 *
	 * @var array
	 * @static
	 * @access private
	 */
	private static $sArrInstance = array();

	/**
	 * $obj 合服活动原始信息
	 *
	 * @var array
	 * @access private
	 */
	private $obj = array();

	/**
	 * $objModify 合服活动更新信息
	 *
	 * @var array
	 * @access private
	 */
	private $objModify = array();

	/**
	 * getInstance 获取用户实例
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return MergeServerObj
	 */
	public static function getInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}
		
		if (!isset(self::$sArrInstance[$uid]))
		{
			self::$sArrInstance[$uid] = new MergeServerObj($uid);
		}
		
		return self::$sArrInstance[$uid];
	}

	/**
	 * releaseInstance 释放用户实例
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return 
	 */
	public static function releaseInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}
		
		if (isset(self::$sArrInstance[$uid]))
		{
			unset(self::$sArrInstance[$uid]);
		}
	}	

	/**
	 * __construct 构造函数
	 *
	 * @param int $uid 用户id
	 * @access private
	 * @return void
	 */
	private function __construct($uid)
	{
		$this->obj = $this->getUserInfo($uid);
		if (empty($this->obj)) 
		{
			$this->obj = $this->createUserInfo($uid);
		}
		
		$this->objModify = $this->obj;
	}
	
	/**
	 * getUserInfo 获得用户信息
	 *
	 * @param int $uid 用户id
	 * @access public
	 * @return array 用户信息
	 */
	public function getUserInfo($uid)
	{
		$arrCond = array(array(MergeServerDef::TBL_FIELD_UID, '=', $uid));
		$arrBody = MergeServerDef::$MERGESERVER_ALL_FIELDS;
		
		return MergeServerDao::select($arrCond, $arrBody);
	}
	
	/**
	 * createUserInfo 创建用户信息
	 *
	 * @param int $uid 用户id
	 * @access public
	 * @return array 用户信息
	 */
	public function createUserInfo($uid)
	{
		$arrRet = array (MergeServerDef::TBL_FIELD_UID => $uid,
						 MergeServerDef::TBL_FIELD_COMPENSATE_TIME => 0,
						 MergeServerDef::TBL_FIELD_LOGIN_TIME => 0,
						 MergeServerDef::TBL_FIELD_LOGIN_COUNT => 0,
						 MergeServerDef::TBL_FIELD_VA_EXTRA => array(
						 MergeServerDef::TBL_VA_EXTRA_FIELD_LOGIN_GOT => array(),
						 MergeServerDef::TBL_VA_EXTRA_FIELD_RECHARGE_GOT => array()),
		);
		MergeServerDao::insert($arrRet);
		
		return $arrRet;
	}
	
	/**
	 * isCompensated 是否已经补偿过
	 *
	 * @access public
	 * @return bool 是否已经补偿过
	 */
	public function isCompensated()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_COMPENSATE_TIME] != 0;
	}
	
	/**
	 * setCompensateTime 设置补偿领取时间
	 *
	 * @param int $time 补偿领取时间
	 * @access public
	 * @return void
	 */
	public function setCompensateTime($time)
	{
		$this->objModify[MergeServerDef::TBL_FIELD_COMPENSATE_TIME] = $time;
	}
	
	/**
	 * getUid 获取用户id
	 *
	 * @access public
	 * @return int 用户id
	 */
	public function getUid()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_UID];
	}
	
	/**
	 * getLoginCount 获取登陆次数
	 *
	 * @access public
	 * @return int 登陆次数
	 */
	public function getLoginCount()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_LOGIN_COUNT];
	}
	
	/**
	 * getLoginGotGroup 获取已经领取累积登陆奖励的天数数组
	 *
	 * @access public
	 * @return array 已经领取累积登陆奖励的天数数组
	 */
	public function getLoginGotGroup()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_LOGIN_GOT];
	}
	
	/**
	 * getLoginCanGroup 获取可以领取累积登陆奖励的天数数组
	 *
	 * @param int $loginCount 登陆次数
	 * @param array $arrGot 已经领取的天数数组
	 * @access public
	 * @return array 能够领取的天数数组
	 */
	public function getLoginCanGroup($loginCount, $arrGot)
	{
		$arrCan = array();
		
		$rewardConfig =  MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_LOGIN);
		$rewardCount = count($rewardConfig);
		
		for ($i = 1; $i <= $loginCount && $i <= $rewardCount; ++$i)
		{
			if (in_array($i, $arrGot) || in_array($i, $arrCan))
			{
				continue;
			}
			$arrCan[] = $i;
		}
	
		return $arrCan;
	}
	
	/**
	 * getLoginTime 获取登陆时间
	 *
	 * @access public
	 * @return int 登陆时间
	 */
	public function getLoginTime()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_LOGIN_TIME];
	}
	
	/**
	 * addLoginGotGroup 增加领取连续登陆奖励的天数
	 *
	 * @param int $data 领取连续登陆奖励的天数
	 * @access public
	 * @return void
	 */
	public function addLoginGotGroup($data)
	{
		$arrGot = $this->getLoginGotGroup();
		$arrGot[] = intval($data);
		sort($arrGot);
	
		$this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_LOGIN_GOT] = $arrGot;
	}
	
	/**
	 * getRechargeNum 获取充值金额
	 *
	 * @access public
	 * @return int 充值金额
	 */
	public function getRechargeNum()
	{
		$start = MergeServerUtil::getActivityStartTime(MergeServerDef::MSERVER_TYPE_RECHARGE);
		$end = MergeServerUtil::getActivityEndTime(MergeServerDef::MSERVER_TYPE_RECHARGE);
		$uid = $this->getUid();
	
		return EnUser::getRechargeGoldByTime($start, $end, $uid);
	}
	
	/**
	 * getRechargeGotGroup 获取已经领取累计充值奖励的档位数组
	 *
	 * @access public
	 * @return array 已经领取累计充值奖励的档位数组
	 */
	public function getRechargeGotGroup()
	{
		return $this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_RECHARGE_GOT];
	}
	
	/**
	 * getRechargeCanGroup 获取能够领取累计充值奖励的档位数组
	 *
	 * @param int $rechargeNum 充值金额
	 * @param array $arrGot 已经领取的奖励档位数组
	 * @access public
	 * @return array 能够领取累计充值奖励的档位数组
	 */
	public function getRechargeCanGroup($rechargeNum, $arrGot)
	{
		$arrCan = array();
	
		$rewardConfig =  MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_RECHARGE);
		$rewardCount = count($rewardConfig);
	
		for ($i = 1; $i <= $rewardCount; ++$i)
		{
			if ($rechargeNum >= $rewardConfig[$i]['expense'] && !in_array($i, $arrGot))
			{
				$arrCan[] = $i;
			}
		}
	
		return $arrCan;
	}
	
	/**
	 * addRechargeGotGroup 增加领取累计充值奖励的档位
	 *
	 * @param int $data 领取累计充值奖励的档位
	 * @access public
	 * @return void
	 */
	public function addRechargeGotGroup($data)
	{
		$arrGot = $this->getRechargeGotGroup();
		$arrGot[] = intval($data);
		sort($arrGot);
		
		$this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_RECHARGE_GOT] = $arrGot;
	}
	
	/**
	 * increLoginCount 更新用户登陆信息，使用户累积登陆天数加1
	 *
	 * @access public
	 * @return void
	 */
	public function increLoginCount()
	{
		$this->objModify[MergeServerDef::TBL_FIELD_LOGIN_TIME] = Util::getTime();
		$this->objModify[MergeServerDef::TBL_FIELD_LOGIN_COUNT]++;
	}
	
	/**
	 * update 将已经更新的数据同步到数据库
	 *
	 * @access public
	 * @return void
	 */
	public function update()
	{
		$arrField = array();
		foreach ($this->obj as $key => $value)
		{
			if ($this->objModify[$key] != $value)
			{
				$arrField[$key] = $this->objModify[$key];
			}
		}
			
		if (empty($arrField))
		{
			Logger::debug('no change');
			return;
		}

		Logger::debug("update MergeServerObj uid:%d, changed field:%s", $this->getUid(), $arrField);
		
		$arrCond = array(array(MergeServerDef::TBL_FIELD_UID, '=', $this->getUid()));
		MergeServerDao::update($arrCond, $arrField);
		
		$this->obj = $this->objModify;
	}
	
	/**
	 * setLoginCountForConsole 设置登陆次数
	 *
	 * @param $loginCount 登陆次数
	 * @access public
	 * @return void
	 */
	public function setLoginCountForConsole($loginCount)
	{
		if(!FrameworkConfig::DEBUG)
		{
			throw new FakeException('can only setLoginCountForConsole when debug');
		}
		
		$this->objModify[MergeServerDef::TBL_FIELD_LOGIN_COUNT] = $loginCount;
	}
	
	/**
	 * setLoginTimeForConsole 设置登陆时间
	 *
	 * @param $time 登陆时间
	 * @access public
	 * @return void
	 */
	public function setLoginTimeForConsole($time)
	{
		if(!FrameworkConfig::DEBUG)
		{
			throw new FakeException('can only setLoginTimeForConsole when debug');
		}
		
		$this->objModify[MergeServerDef::TBL_FIELD_LOGIN_TIME]= $time;
	}
	
	/**
	 * setLoginGotGroupForConsole 设置已经领取累积登陆奖励的天数数组，console使用
	 *
	 * @param $arrGot 已经领取累积登陆奖励的天数数组
	 * @access public
	 * @return void
	 */
	public function setLoginGotGroupForConsole($arrGot)
	{
		if(!FrameworkConfig::DEBUG)
		{
			throw new FakeException('can only setLoginGotGroupForConsole when debug');
		}
	
		$this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_LOGIN_GOT] = $arrGot;
	}
	
	/**
	 * setRechargeGotGroupForConsole 设置已经领取累计充值奖励的档位数组，console使用
	 *
	 * @param $arrGot 已经领取累计充值奖励的档位数组
	 * @access public
	 * @return void
	 */
	public function setRechargeGotGroupForConsole($arrGot)
	{
		if(!FrameworkConfig::DEBUG)
		{
			throw new FakeException('can only setRechargeGotGroupForConsole when debug');
		}
	
		$this->objModify[MergeServerDef::TBL_FIELD_VA_EXTRA][MergeServerDef::TBL_VA_EXTRA_FIELD_RECHARGE_GOT] = $arrGot;
	}
}	

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
