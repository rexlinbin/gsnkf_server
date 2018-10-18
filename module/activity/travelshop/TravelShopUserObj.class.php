<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShopUserObj.class.php 246312 2016-06-14 08:03:23Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/TravelShopUserObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-06-14 08:03:23 +0000 (Tue, 14 Jun 2016) $
 * @version $Revision: 246312 $
 * @brief 
 *  
 **/
class TravelShopUserObj
{
	private $id = 0;							
	private $uid = 0;
	private $info = NULL;							// 修改数据
	private $infoBak = NULL; 						// 原始数据
	private static $arrInstance = array();			// 单例数组
	
	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return TravelShopUserObj
	*/
	public static function getInstance($uid)
	{
		if (!isset(self::$arrInstance[$uid]))
		{
			self::$arrInstance[$uid] = new self($uid);
		}
		return self::$arrInstance[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrInstance = array();
		}
		else if (isset(self::$arrInstance[$uid]))
		{
			unset(self::$arrInstance[$uid]);
		}
	}
	
	public function __construct($uid, $info = array())
	{
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		if (empty($info)) 
		{
			$info = TravelShopDao::selectUser($uid);
			if (empty($info))
			{
				$info = $this->init($uid);
			}
		}
		$this->uid = $uid;
		$this->info = $info;
		$this->infoBak = $info;
		$this->refresh();
	}
	
	public function init($uid)
	{
		$arrField = array(
				TravelShopDef::FIELD_UID => $uid,
				TravelShopDef::FIELD_SUM => 0,
				TravelShopDef::FIELD_SCORE => 0,
				TravelShopDef::FIELD_START_TIME => 0,
				TravelShopDef::FIELD_FINISH_TIME => 0,
				TravelShopDef::FIELD_REFRESH_TIME => Util::getTime(),
				TravelShopDef::FIELD_VA_USER => array(),
		);
	
		return $arrField;
	}
	
	public function refresh()
	{
		$now = Util::getTime();
		$refreshTime = $this->getRefreshTime();
		if (!TravelShopLogic::isInCurRound($refreshTime))
		{
			$this->info = $this->init($this->uid);
		}
		else 
		{
			//每日刷新购买信息
			if (!Util::isSameDay($refreshTime))
			{
				$this->unsetBuy();
				$this->setRefreshTime($now);
			}
			//根据充值倒计时来刷新返利等信息
			$finishTime = $this->getFinishTime();
			//充值倒计时不为空，表示处于充值优惠阶段
			if (!empty($finishTime))
			{
				//获取当前充值档位和金额
				$id = $this->getNextPayback();
				$deadline = TravelShopLogic::getDeadlineConf();
				list($pay, $back) = TravelShopLogic::getPaybackConf($id);
				$topup = $this->getTopup($finishTime + $deadline);
				//如果当前充值金额满足档位，表示充值完成，结束充值优惠阶段，进入折扣道具阶段
				//更新当前充值档位返利状态，清空充值倒计时，清空购买进度
				if ($topup >= $pay)
				{
					$this->nogainPayback($id);
					$this->setFinishTime(0);
					$this->setScore(0);
					$this->update();
				}
				//如果当前充值金额不满足档位，并且充值倒计时结束，结束充值优惠阶段，进入折扣道具阶段
				//清空充值倒计时，清空购买进度, 设置充值开始时间为0
				elseif ($finishTime + $deadline < $now)
				{
					$this->setFinishTime(0);
					$this->setScore(0);
					$this->setStartTime(0);
				}
			}
		}
	}
	
	public function addSum($num)
	{
		$this->info[TravelShopDef::FIELD_SUM] += $num;
	}
	
	public function getScore()
	{
		return $this->info[TravelShopDef::FIELD_SCORE];
	}
	
	public function setScore($num)
	{
		$this->info[TravelShopDef::FIELD_SCORE] = $num;
	}
	
	public function addScore($num)
	{
		if ($this->info[TravelShopDef::FIELD_SCORE] < TravelShopDef::SCORE_LIMIT) 
		{
			$this->info[TravelShopDef::FIELD_SCORE] += $num;
			if ($this->info[TravelShopDef::FIELD_SCORE] >= TravelShopDef::SCORE_LIMIT) 
			{
				$this->info[TravelShopDef::FIELD_SCORE] = TravelShopDef::SCORE_LIMIT;
				$id = $this->checkPayback();
				if (!empty($id)) 
				{
					$this->gainPayback($id);
					$this->id = $id;
				}
				$this->setFinishTime(Util::getTime());
				$this->setStartTime(Util::getTime());
			}
		}
	}
	
	public function getStartTime()
	{
		return $this->info[TravelShopDef::FIELD_START_TIME];
	}
	
	public function setStartTime($time)
	{
		$this->info[TravelShopDef::FIELD_START_TIME] = $time;
	}
	
	public function getFinishTime()
	{
		return $this->info[TravelShopDef::FIELD_FINISH_TIME];
	}
	
	public function setFinishTime($time)
	{
		$this->info[TravelShopDef::FIELD_FINISH_TIME] = $time;
	}
	
	public function getRefreshTime()
	{
		return $this->info[TravelShopDef::FIELD_REFRESH_TIME];
	}
	
	public function setRefreshTime($time)
	{
		$this->info[TravelShopDef::FIELD_REFRESH_TIME] = $time;
	}
	
	public function getBuy()
	{
		if (!isset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY]))
		{
			return array();
		}
		return $this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY];
	}
	
	public function unsetBuy()
	{
		unset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY]);
	}
	
	public function getBuySum()
	{
		return array_sum($this->getBuy());
	}
	
	public function getBuyNum($goodsId)
	{
		if (!isset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY][$goodsId])) 
		{
			return 0;
		}
		return $this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY][$goodsId];
	}
	
	public function addBuyNum($goodsId, $num)
	{
		if (!isset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY][$goodsId])) 
		{
			$this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY][$goodsId] = 0;
		}
		$this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::BUY][$goodsId] += $num;
		$this->addSum($num);
	}
	
	public function getPayback()
	{
		if (!isset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::PAYBACK])) 
		{
			return array();
		}
		return $this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::PAYBACK];
	}
	
	public function gainPayback($id)
	{
		$this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::PAYBACK][$id] = TravelShopDef::GAIN;
		$this->setStartTime(0);
	}
	
	public function nogainPayback($id)
	{
		$this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::PAYBACK][$id] = TravelShopDef::NOGAIN;
	}
	
	public function canGainPayback($id)
	{
		$payback = $this->getPayback();
		if (isset($payback[$id]) && $payback[$id] == TravelShopDef::NOGAIN) 
		{
			return true;
		}
		return false;
	}
	
	public function getNextPayback()
	{
		return count($this->getPayback()) + 1;
	}
	
	public function checkPayback()
	{
		$id = $this->getNextPayback() - 1;
		return $this->canGainPayback($id) ? $id : 0;
	}
	
	public function sendPayback($id)
	{
		list($pay, $back) = TravelShopLogic::getPaybackConf($id);
		RewardUtil::reward3DtoCenter($this->uid, array(array(array(RewardConfType::GOLD, 0, $back))), RewardSource::TRAVEL_SHOP_PAY_BACK_GOLD);
	}
	
	public function getReward()
	{
		if (!isset($this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::REWARD]))
		{
			return array();
		}
		return $this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::REWARD];
	}
	
	public function gainReward($id)
	{
		$this->info[TravelShopDef::FIELD_VA_USER][TravelShopDef::REWARD][] = $id;
	}
	
	public function canGainReward($id)
	{
		$reward = $this->getReward();
		if (!in_array($id, $reward)) 
		{
			return true;
		}
		return false;
	}
	
	public function getTopup($endTime = 0)
	{
		$topup = 0;
		$startTime = $this->getStartTime();
		$endTime = empty($endTime) ? Util::getTime() : $endTime;
		if (!empty($startTime)) 
		{
			$topup = EnUser::getRechargeGoldByTime($startTime, $endTime, $this->uid);
		}
		return $topup;
	}
	
	public function update()
	{
		if ($this->info != $this->infoBak)
		{
			TravelShopDao::insertOrUpdateUser($this->info);
			if (!empty($this->id)) 
			{
				$this->sendPayback($this->id);
				$this->id = 0;
			}
			$this->infoBak = $this->info;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */