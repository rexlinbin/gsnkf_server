<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLotteryObj.class.php 254481 2016-08-03 06:59:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/SevensLotteryObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-03 06:59:25 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254481 $
 * @brief 
 *  
 **/
class SevensLotteryObj
{
	private $uid = NULL;
	private $data = NULL;
	private $dataModify = NULL;
	private static $arrInstance = array();
	
	/**
	 * @param int $uid
	 * @return SevensLotteryObj
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

	private function __construct($uid)
	{
		if($uid <= 0)
		{
			throw new FakeException('invalid uid:%d', $uid);
		}
		$this->uid = $uid;
		$this->data = $this->dataModify = SevensLotteryDao::select($this->uid);
		if (empty($this->dataModify))
		{
			$this->init();
		}
		$this->refresh();
	}
	
	public function init()
	{
		$this->dataModify = array(
				SevensLotteryDef::FIELD_UID => $this->uid,
				SevensLotteryDef::FIELD_NUM => 0,
				SevensLotteryDef::FIELD_POINT => 0,
				SevensLotteryDef::FIELD_LUCKY => 0, 
				SevensLotteryDef::FIELD_FREE_TIME => 0,
				SevensLotteryDef::FIELD_REFRESH_TIME => Util::getTime(),
		);
	}
	
	public function refresh()
	{
		//每日刷新
		$refreshTime = $this->getRefreshTime();
		if (!Util::isSameDay($refreshTime))
		{
			$this->setNum(0);
			$this->setRefreshTime();
		}
	}
	
	public function getNum()
	{
		return $this->dataModify[SevensLotteryDef::FIELD_NUM];
	}
	
	public function setNum($num)
	{
		$this->dataModify[SevensLotteryDef::FIELD_NUM] = $num;
	}
	
	public function addNum($num)
	{
		$this->dataModify[SevensLotteryDef::FIELD_NUM] += $num;
	}
	
	public function getPoint()
	{
		return $this->dataModify[SevensLotteryDef::FIELD_POINT];
	}
	
	public function addPoint($num)
	{
		$this->dataModify[SevensLotteryDef::FIELD_POINT] += $num;
	}
	
	public function subPoint($num)
	{
		if ($this->dataModify[SevensLotteryDef::FIELD_POINT] < $num)
		{
			return false;
		}
		else
		{
			$this->dataModify[SevensLotteryDef::FIELD_POINT] -= $num;
			return true;
		}
	}
	
	public function getLucky()
	{
		return $this->dataModify[SevensLotteryDef::FIELD_LUCKY];
	}
	
	public function setLucky($num)
	{
		$this->dataModify[SevensLotteryDef::FIELD_LUCKY] = $num;
	}
	
	public function addLucky($num)
	{
		$this->dataModify[SevensLotteryDef::FIELD_LUCKY] += $num;
	}
	
	public function getFree()
	{
		$freeTime = $this->getFreeTime();
		return SevensLotteryUtil::isSamePeriod($freeTime) ? 0 : 1;
	}
	
	public function setFreeTime()
	{
		$this->dataModify[SevensLotteryDef::FIELD_FREE_TIME] = Util::getTime();
	}
	
	public function getFreeTime()
	{
		return $this->dataModify[SevensLotteryDef::FIELD_FREE_TIME];
	}
	
	public function getRefreshTime()
	{
		return $this->dataModify[SevensLotteryDef::FIELD_REFRESH_TIME];
	}
	
	public function setRefreshTime()
	{
		$this->dataModify[SevensLotteryDef::FIELD_REFRESH_TIME] = Util::getTime();
	}
	
	public function update()
	{
		if($this->uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $this->uid);
		}

		if ($this->data != $this->dataModify)
		{
			if (empty($this->data))
			{
				SevensLotteryDao::insert($this->dataModify);
			}
			else
			{
				$arrField = array();
				foreach ($this->dataModify as $key => $value)
				{
					if ($this->data[$key] != $value)
					{
						$arrField[$key] = $value;
					}
				}
				SevensLotteryDao::update($this->uid, $arrField);
			}
			$this->data = $this->dataModify;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */