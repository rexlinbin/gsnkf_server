<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WelcomebackObj.class.php 259700 2016-08-31 08:15:16Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/welcomeback/WelcomebackObj.class.php $
 * @author $Author: YangJin $(jinyang@babeltime.com)
 * @date $Date: 2016-08-31 08:15:16 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259700 $
 * @brief 老玩家回归
 *  
 **/

/**
 * 当且仅当要开启活动时，调用initData()
	data = array(
		'uid' => uid,							uid
		'offline_time' => offlinetime,			活动对应的玩家离线时间		
		'back_time' => backtime,				回归活动开始时间
		'end_time' => endtime, 					回归活动结束时间
		'need_bufa' => 0|1						活动结束后需不需要补发没领的奖励到中心
		'va_info' => array(
	 		'gift' => array(
				id => gainGift					1:未领取礼包，2：已领取
			), 
			'task' => array(
				id => array(
						0 => finishedTimes, 	目前执行次数
						1 => status,			0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
						2 => select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
				)
			), 
			'recharge' => array(
				id => array(
						0 => gold,				需要充值金币
						1 => rechargeTimes,		总的可充值次数
						2 => hadRewardTimes,	已领奖次数
						3 => toRewardTimes,		待领奖次数
						4 => array(
								select			-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
							 )	
				)
			),
			'rechargeUpdateTime' => rechargeUpdateTime,  单充更新时间
			'shop' => array(
				id => buyTimes					已购买次数
			)
		)
	)
 */
class WelcomebackObj
{
	private $uid;
	private $data = array();
	private $dataBak = array();
	private static $instance = array();
	/**
	 * 任务列表（仅包含任务，不包括领礼包、单充、商店）
	 * $taskList = array(
	 * 		typeId => array(id1, id2, id3...),
	 * )
	 */
	private $taskList = array();
	
	private function __construct($uid)
	{
		$this->uid = $uid;
		$this->data = WelcomebackDao::getInfo($this->uid);
		$this->dataBak = $this->data;
	}
	
	/**
	 * 
	 * @return WelcomebackObj
	 */
	public static function getInstance($uid = 0)
	{
		if (empty($uid)) 
		{
			$uid = RPCContext::getInstance()->getUid();
			if (empty($uid))
			{
				throw new FakeException('uid is empty');
			}
		}
		
		if (empty(self::$instance[$uid])) 
		{
			self::$instance[$uid] = new self($uid);
		}
		
		return self::$instance[$uid];
	}
	
	/**
	 * ！！！注意！！！当且仅当【在活动第一次开，或者上一次回归活动已经结束，又开启新的活动】时调用，最后要在外部调用update来保存
	 */
	public function initData()
	{
		if (empty($this->data) || Util::getTime() > $this->data['end_time'])
		{
			$backTime = Util::getTime();
			$endTime = strtotime(date('Ymd', $backTime + 86400));//第二天0点整
			$vaArray = $this->initVaArray();
			$this->data = array(
					WelcomebackDef::UID => $this->uid,
					WelcomebackDef::OFFLINE_TIME => EnUser::getUserObj()->getLastLogoffTime(),
					WelcomebackDef::BACK_TIME => $backTime,
					WelcomebackDef::END_TIME => $endTime,
					Welcomebackdef::NEED_BUFA => 1,
					WelcomebackDef::VA_INFO => array(
							WelcomebackDef::VA_INFO_GIFT => $vaArray[WelcomebackDef::VA_INFO_GIFT],
							WelcomebackDef::VA_INFO_TASK => $vaArray[WelcomebackDef::VA_INFO_TASK],
							WelcomebackDef::VA_INFO_RECHARGE => $vaArray[WelcomebackDef::VA_INFO_RECHARGE],
							WelcomebackDef::VA_INFO_RECHARGEUPDATETIME => $backTime,
							WelcomebackDef::VA_INFO_SHOP => $vaArray[WelcomebackDef::VA_INFO_SHOP]
					)
			);
			
			//$this->update();//主要是将玩家离线时间、回归时间、结束时间保存数据库保存下来//外部调用update来保存吧
			
			Logger::debug('initData is %s', $this->data);
		}
		else 
			throw new FakeException('cant init data because a welcomeback activity is going on');
	}
	
	private function initVaArray()
	{
		$ret = array(
			WelcomebackDef::VA_INFO_GIFT => array(),
			WelcomebackDef::VA_INFO_TASK => array(),
			WelcomebackDef::VA_INFO_RECHARGE => array(),
			WelcomebackDef::VA_INFO_SHOP => array()
		);
		
		$rewardConf = WelcomebackUtil::getRewardConf();
		$taskConf = WelcomebackUtil::getWelcomebackConf();
		
		foreach ($taskConf[WelcomebackDef::VA_INFO_GIFT] as $giftId)
		{
			if (!isset($rewardConf[$giftId]))
				throw new FakeException('cant find id:[%d] in return_reward.csv', $giftId);
			$ret[WelcomebackDef::VA_INFO_GIFT][$giftId] = 1;//初始化1，表示未领取礼包
		}
		
		$userLevel = EnUser::getUserObj()->getLevel();
		foreach ($taskConf[WelcomebackDef::VA_INFO_TASK] as $taskId)
		{
			if (!isset($rewardConf[$taskId]))
				throw new FakeException('cant find id:[%d] in return_reward.csv', $taskId);
			
			if ($userLevel >= $rewardConf[$taskId][WelcomebackDef::LEVEL_LIMITS])
				$ret[WelcomebackDef::VA_INFO_TASK][$taskId] = array(0, 0, -1);//初始化0,0,-1，表示执行了0次，未完成任务,玩家还没选奖励
		}
		
		foreach ($taskConf[WelcomebackDef::VA_INFO_RECHARGE] as $rechargeId)
		{
			if (!isset($rewardConf[$rechargeId]))
				throw new FakeException('cant find id:[%d] in return_reward.csv', $rechargeId);
			$ret[WelcomebackDef::VA_INFO_RECHARGE][$rechargeId] = array($rewardConf[$rechargeId][WelcomebackDef::GOLD], $rewardConf[$rechargeId][WelcomebackDef::RECHARGE_TIMES], 0, 0, array());//后三个表示已领奖0次，待领奖0次，玩家还没选
		}
		
		foreach ($taskConf[WelcomebackDef::VA_INFO_SHOP] as $shopId)
		{
			if (!isset($rewardConf[$shopId]))
				throw new FakeException('cant find id:[%d] in return_reward.csv', $shopId);
			$ret[WelcomebackDef::VA_INFO_SHOP][$shopId] = 0;//初始化0，表示已购买0次
		}
		
		//为了后续单充的数据刷新方便，按所需充值金额倒序排列
		if(count($ret[WelcomebackDef::VA_INFO_RECHARGE]) > 1)
			uasort($ret[WelcomebackDef::VA_INFO_RECHARGE], array($this, 'mySort'));
		
		return $ret;
	}
	
	/**
	 * 将$this->rechargeObj按照需要充值金额倒序排列
	 */
	private function mySort($x, $y)
	{
		if($x[0] > $y[0])
			return -1;
		else if ($x[0] < $y[0])
			return 1;
		else
			return 0;
	}
	
	public function isOpen()
	{
		if (empty($this->data))
			return false;
		if (Util::getTime() > $this->data[WelcomebackDef::END_TIME] || Util::getTime() < $this->data[WelcomebackDef::BACK_TIME])
			return false;
		return true;
	}
	
	public function getInfo()
	{
		return $this->data;
	}

	public function getOfflineTime()
	{
		return isset($this->data[WelcomebackDef::OFFLINE_TIME])? $this->data[WelcomebackDef::OFFLINE_TIME] : 0;
	}
	
	/**
	 * 控制台用
	 */
	public function setOfflineTime($offlineTime)
	{
		$this->data[WelcomebackDef::OFFLINE_TIME] = $offlineTime;
	}
	
	public function getBackTime()
	{
		return isset($this->data[WelcomebackDef::BACK_TIME])? $this->data[WelcomebackDef::BACK_TIME] : 0;
	}
	
	public function getEndTime()
	{
		return isset($this->data[WelcomebackDef::END_TIME])? $this->data[WelcomebackDef::END_TIME] : 0;
	}
	
	/**
	 * 控制台用
	 */
	public function setEndTime($endTime)
	{
		$this->data[WelcomebackDef::END_TIME] = $endTime;
	}
	
	/**
	 * @param int $yes  0:不补发，1：补发
	 */
	public function setNeedBufa($yes)
	{
		$this->data[WelcomebackDef::NEED_BUFA] = $yes;
	}
	
	public function getNeedBufa()
	{
		return isset($this->data[WelcomebackDef::NEED_BUFA])? $this->data[WelcomebackDef::NEED_BUFA] : 0;
	}

	/**
	 * 获取上次活动遗留的未领取的奖励
	 * 参考：
	 * 		'task' => array(
				id => array(
						0 => finishedTimes, 	目前执行次数
						1 => status,			0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
						2 => select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
				)
			), 
			'recharge' => array(
				id => array(
						0 => gold,				需要充值金币
						1 => rechargeTimes,		总的可充值次数
						2 => hadRewardTimes,	已领奖次数
						3 => toRewardTimes,		待领奖次数
						4 => array(
								select			-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
							 )	
				)
			)
	 */
	public function getRewardNotGained()
	{
		$ret = array();
		if (1 == $this->getNeedBufa())
		{
			if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK]))
			{
				foreach ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK] as $id => $task)
				{
					if (1 == $task[1])
						$ret[] = $id;
				}
			}
				
			if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE]))
			{
				foreach ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE] as $id => $recharge)
				{
					for ($i =0; $i < $recharge[3]; $i++)
						$ret[] = $id;
				}
			}
		}
		return $ret;
	}
	
	/**
	 * 		'gift' => array(
				id => gainGift					1:未领取礼包，2：已领取
			)
	 */
	public function getGiftInfo()
	{
		return isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT])? $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT] : array();
	}
	
	/**
	 * @return 1:未领取，2：已领取
	 */
	public function getGiftGained($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id]))
			return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id];
		throw new FakeException('err id:[%d]', $id);
	}
	
	public function setGiftGained($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id] = 2;
		else
			throw new FakeException('err id:[%d]', $id);
	}
	/**
	 * 控制台用
	 */
	public function setGiftUnGained($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_GIFT][$id] = 1;
		else
			throw new FakeException('no gift:[%d] in data', $id);
	}
	
	/**
	 * 更新单充的待领奖次数
	 * 		'recharge' => array(
				id => array(
						0 => gold,					需要充值金币
						1 => rechargeTimes,			总的可充值次数
						2 => hadRewardTimes,		已领奖次数
						3 => toRewardTimes,			待领奖次数
						4 => array(
								select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
							 )
				)
			)
	 */
	public function updateRecharge()
	{
		//充值记录
		if (!isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGEUPDATETIME]))
		{
			Logger::fatal('welcomeback data is too old to support current activity');
			return;
		}
		
		$skip = true;
		foreach ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE] as $id => $oneRecharge)
		{
			if ($oneRecharge[1] > ($oneRecharge[2] + $oneRecharge[3]))
			{
				$skip = false;
				break;
			}
		}
		
		if ($skip)
			return ;//$skip为true说明所有单充任务都完成了，直接退出。避免拉取充值信息。
		
		$beginTime = $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGEUPDATETIME] + 1;//由于前段调用，每次充值后直接update，所以保存的刚好是充值时间，不+1的话还会把这个充值给算上
		$endTime = Util::getTime();
		$chargeOrderArr = EnUser::getChargeOrderByTime($beginTime, $endTime, $this->uid);
		$mtime = array();
		foreach ($chargeOrderArr as $key => $value)
		{
			$mtime[$key] = $value['mtime'];
		}
		array_multisort($mtime, SORT_ASC, $chargeOrderArr);//按充值时间升序
		
		$push = 0;
		foreach ($chargeOrderArr as $chargeOrder)
		{
			foreach ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE] as $id => $oneRecharge)
			{
				if ($chargeOrder['gold_num'] >= $oneRecharge[0]
					&& $oneRecharge[1] > ($oneRecharge[2] + $oneRecharge[3])) 
				{
					$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][3] += 1;
					$push = $id;
					Logger::info('welcomeback update recharge, chargeOrder:[%s], gold:[%d], taskId:[%d], 
							taskInfo:[%s]', $chargeOrder['order_id'], $chargeOrder["gold_num"], $id, $oneRecharge);
					break;
				}
			}
		}
		
		//有新的订单，给前端推送、更新db
		if ($push != 0)
		{
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGEUPDATETIME] = $endTime;
			$this->update();
			RPCContext::getInstance()->sendMsg(array($this->uid), PushInterfaceDef::WELCOMEBACK_TASK_FINISH, array($push));
		}
	}
	
	public function setRechargeUpdateTime($time)
	{
		$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGEUPDATETIME] = $time;
	}
	
	public function getRechargeToReward($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id]))
			return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][3];
		throw new FakeException('err id:[%d]', $id);
	}
	
	public function setRechargeToReward($id, $toReward)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][3] = $toReward;
		else 
			throw new FakeException('err id:[%d]', $id);
	}
	
	public function getRechargeInfo()
	{
		return isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE])? $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE] : array();
	}
	
	public function getRechargeHadReward($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id]))
			return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][2];
		throw new FakeException('err id:[%d]', $id);
	}
	
	public function setRechargeHadReward($id, $hadReward, $select)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id]))
		{	
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][2] = $hadReward;
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_RECHARGE][$id][4][$hadReward -1] = $select;
		}
		else 
			throw new FakeException('err id:[%d]', $id);
	}
	
	/**
	 * 更新任务
	 * 		'task' => array(
				id => array(
						0 => finishedTimes, 	目前执行次数
						1 => status				0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
						2 => select				-1:未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
				)
			)
	 *@param int $typeId 每个模块对应一个id，比如竞技场是106，在WelcomebackDef中有定义，查找：WelcomebackDef::TASK_TYPE_
	 *@param int $num 任务完成次数
	 */
	public function updateTask($typeId, $num = 1)
	{
		if (!$this->isOpen())
			return ;

		if (empty($this->taskList))
		{
			//建立任务类型和任务id的对应关系
			$conf = WelcomebackUtil::getRewardConf();
			if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK]))
			{
				foreach ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK] as $id => $task)
				{
					$this->taskList[$conf[$id][WelcomebackDef::TYPE_ID]][] = $id;
				}
			}
		}
		
		if (empty($this->taskList[$typeId])) 
		{
			Logger::fatal('WelcomebackObj: typeId:[%d] not in task list', $typeId);//由于会让外部模块调用，参数有不确定性，只打日志，不影响其他模块正常使用
			return ;
		}
		
		$idList = $this->taskList[$typeId];
		$needPush = false;
		//相同类型的任务都会更新。比如有两个任务都是打竞技场，一个打5次，一个打10次，那么打了3次后，任务的进度为3/5、3/10
		foreach ($idList as $id)
		{
			if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id])) 
			{
				Logger::info('updateTask begin: typeId:[%d], $num:[%d], id:[%d], original data is [%s]',
					 $typeId, $num, $id, $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]);
				
				if ($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1] == 0) //==1,==2已经完成任务了，不用更新
				{
					$conf = WelcomebackUtil::getRewardConf();
					if (isset($conf[$id]) && $conf[$id][WelcomebackDef::TYPE] == WelcomebackDef::TYPE_TASK) 
					{
						$total = $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0] + $num;
						if ($total >= $conf[$id][WelcomebackDef::FINISH])
						{
							$needPush = true;
							$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0] = $conf[$id][WelcomebackDef::FINISH];
							$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1] = 1;
						}
						else
						{
							$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0] = $total;
						}
					}
					else 
						throw new FakeException('err id:[%d] or type in return_reward.csv', $id);
					
					Logger::info('welcomeback updateTask success: typeId:[%d], $num:[%d], id:[%d], now data is [%s]',
						$typeId, $num, $id, $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]);
				}
				else
					Logger::info('welcomeback updateTask end and no data changes.');
			}
			else 
			{
				throw new FakeException('task id:[%d] not exists in mysql', $id);
			}
		}
		
		//给前端推送
		if ($needPush)
			RPCContext::getInstance()->sendMsg(array($this->uid), PushInterfaceDef::WELCOMEBACK_TASK_FINISH, array($idList[0]));
		
		$this->update();
	}
	
	public function getTaskInfo()
	{
		return isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK])? $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK] : array();
	}
	
	/*
	public function getTaskFinishedTimes($id)
	{
		return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0];
	}*/
	/**
	 * 控制台用
	 */
	public function setTaskFinishedTimes($id, $num)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]))
		{
			$conf = WelcomebackUtil::getRewardConf();
			if ($num >= $conf[$id][WelcomebackDef::FINISH])
			{
				$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0] = $conf[$id][WelcomebackDef::FINISH];
				$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1] = 1;
			}
			else
			{
				$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][0] = $num;
				$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1] = 0;
			}
			
		}
		else
			throw new FakeException('err id:[%d]', $id);
	}
	
	/**
	 * status: 0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
	 */
	public function setTaskStatus($id, $status)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1] = $status;
		else
			throw new FakeException('err id:[%d]', $id);
	}

	/**
	 * status: 0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
	 */
	public function getTaskStatus($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]))
			return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][1];
		throw new FakeException('err id:[%d]', $id);
	}
	
	public function setTaskSelect($id, $select)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_TASK][$id][2] = $select;
		else
			throw new FakeException('err id:[%d]', $id);
	}
	/**
	 * 		'shop' => array(
				id => buyTimes					已购买次数
				)
	 */
	public function getShopInfo()
	{
		return isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP])? $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP] : array();
	}
	
	public function getShopBuyTimes($id)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id]))
			return $this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id];
		throw new FakeException('err id:[%d]', $id);
	}
	
	public function addShopBuyTimes($id, $num)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id] += $num;
		else
			throw new FakeException('err id:[%d]', $id);
	}
	
	public function setShopBuyTimes($id, $num)
	{
		if (isset($this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id]))
			$this->data[WelcomebackDef::VA_INFO][WelcomebackDef::VA_INFO_SHOP][$id] = $num;
		else
			throw new FakeException('err id:[%d]', $id);
	}
	
	public function update()
	{
		if ($this->data != $this->dataBak) 
		{
			WelcomebackDao::update($this->uid, $this->data);
			$this->dataBak = $this->data;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */