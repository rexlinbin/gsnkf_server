<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildMemberObj.class.php 230743 2016-03-03 07:55:11Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/GuildMemberObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-03 07:55:11 +0000 (Thu, 03 Mar 2016) $
 * @version $Revision: 230743 $
 * @brief 
 *  
 **/
class GuildMemberObj
{
	private $init = FALSE;							// 是否插入数据
	private $uid = 0;								// 用户id
	private $member = NULL;							// 修改数据
	private $memberBak = NULL; 						// 原始数据
	private static $arrMember = NULL;				// 实例对象数组
	
	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return GuildMemberObj
	 */
	public static function getInstance($uid)
	{
		if (!isset(self::$arrMember[$uid]))
		{
			self::$arrMember[$uid] = new self($uid);
		}
		return self::$arrMember[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrMember = array();
		}
		else if (isset(self::$arrMember[$uid]))
		{
			unset(self::$arrMember[$uid]);
		}
	}

	public function __construct($uid)
	{
		$info = GuildDao::selectMember($uid);
		$this->uid = $uid;
		if (empty($info)) 
		{
			$info = $this->init();
			$this->init = TRUE;
		}
		$this->member = $info;
		$this->refresh();
		$this->memberBak = $this->member;
	}
	
	public function init()
	{
		$now = Util::getTime();
		$arrField = array(
				GuildDef::USER_ID => $this->uid,
				GuildDef::GUILD_ID => 0,
				GuildDef::MEMBER_TYPE => GuildMemberType::NONE,
				GuildDef::CONTRI_POINT => 0,
				GuildDef::CONTRI_TOTAL => 0,
				GuildDef::CONTRI_WEEK => 0,
				GuildDef::LAST_CONTRI_WEEK => 0,
				GuildDef::CONTRI_NUM => 0,
				GuildDef::CONTRI_TIME => $now,
				GuildDef::REWARD_TIME => 0,
				GuildDef::REWARD_BUY_NUM => 0,
				GuildDef::REWARD_BUY_TIME => $now,
				GuildDef::LOTTERY_NUM => 0,
				GuildDef::LOTTERY_TIME => $now,
				GuildDef::GRAIN_NUM => 0,
				GuildDef::MERIT_NUM => 0,
				GuildDef::ZG_NUM => 0,
				GuildDef::REFRESH_NUM => 0,
				GuildDef::REJOIN_CD => 0,
                GuildDef::PLAYWITH_NUM => 0,
                GuildDef::BE_PLAYWITH_NUM => 0,
                GuildDef::PLAYWITH_TIME => $now,
				GuildDef::VA_MEMBER => array(
						GuildDef::FIELDS => GuildConf::$MEMBER_FIELD_DEFAULT
				)
		);
		return $arrField;
	}

	public function refresh()
	{
		$contriTime = $this->getContriTime();
		$lastSignupEndTime = EnCityWar::getLastSignupEndTime();
		if ($contriTime < $lastSignupEndTime)
		{
			$contriWeek = $contriTime < $lastSignupEndTime - CityWarConf::ROUND_DURATION ? 0 : $this->getContriWeek();
			$this->setContriWeek(0);
			$this->setLastContriWeek($contriWeek);
		}
		
		//与老版本兼容
		$now = Util::getTime();
		if (defined('PlatformConfig::NEW_GUILD_REFRESH_TIME')
		&& Util::isSameDay(strtotime(PlatformConfig::NEW_GUILD_REFRESH_TIME)))
		{
			if (!Util::isSameDay($contriTime))
			{
				$this->setContriNum(0);
				$this->setContriTime($now);
			}
			if (!Util::isSameDay($this->member[GuildDef::REWARD_BUY_TIME]))
			{
				$this->setRewardBuyNum(0);
				$this->setRewardBuyTime($now);
			}
			if (!Util::isSameDay($this->member[GuildDef::PLAYWITH_TIME]))
			{
				$this->setPlayWithNum(0);
				$this->setBePlayWithNum(0);
				$this->setPlayWithTime($now);
			}
		}
		else
		{
			if (!Util::isSameDay($contriTime))
			{
				$this->setContriNum(0);
				$this->setContriTime($now);
				$this->setRewardBuyNum(0);
				$this->setRewardBuyTime($now);
				$this->setLotteryNum(0);
				$this->setLotteryTime($now);
				$this->setRefreshNum(0);
				$this->setPlayWithNum(0);
				$this->setBePlayWithNum(0);
				$this->setPlayWithTime($now);
			}
		}
		
		//老用户需要初始化数据, 然后刷新粮田采集信息
		foreach (GuildConf::$MEMBER_FIELD_DEFAULT as $fieldId => $fieldInfo)
		{
		    $this->getFieldInfo($fieldId);
		}
	}
	
	/**
	 * 何时使用粮田采集次数,就调用一下refreshFields    
	 */
	public function refreshFields()
	{
	    $guildId = $this->getGuildId();
	    if (!empty($guildId))
	    {
	        $now = Util::getTime();
	        $conf = btstore_get()->GUILD_BARN;
	        $fieldNum = $conf[GuildDef::GUILD_FIELD_NUM];
	        $harvestNum = $conf[GuildDef::GUILD_HARVEST_NUM];
	        $maxHarvestNum = $conf[GuildDef::MAX_HARVEST_NUM];
	        $guild = GuildObj::getInstance($guildId);
	        foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => &$fieldInfo)
	        {
	            $upgrade = $guild->getBuildTime(GuildDef::BARN, $fieldNum[$fieldId]);
	            $refresh = $this->getFieldTime($fieldId);
	            if ($upgrade == -1 || Util::isSameDay($refresh))
	            {
	                continue;
	            }
	            $upgrade = $upgrade == 0 ? $now : $upgrade;
	            $refresh = $refresh == 0 ? $now - SECONDS_OF_DAY : $refresh;
	            $days = Util::getDaysBetween(max($refresh, $upgrade - SECONDS_OF_DAY));
	            Logger::trace('refreshFields field:%d upgrade:%d refresh:%d days:%d addnum:%d',
	                    $fieldId, $upgrade, $refresh, $days, $harvestNum[$fieldId] * $days);
	            //不能调用addFieldNum，会死循环
	            $fieldInfo[0] = min($fieldInfo[0] + $harvestNum[$fieldId] * $days, $maxHarvestNum);
	            $this->setFieldTime($fieldId, $now);
	        }
	    }
	    unset($fieldInfo);
	}
	
	public function getInfo()
	{
		return $this->member;
	}
	
	public function getGuildId()
	{
		return $this->member[GuildDef::GUILD_ID];
	}
	
	public function setGuildId($guildId)
	{
		$this->member[GuildDef::GUILD_ID] = $guildId;
		if (!empty($guildId)) 
		{
			$this->refreshAllFieldTime();
			$this->refreshFields();
		}
	}
	
	public function getMemberType()
	{
		return $this->member[GuildDef::MEMBER_TYPE];
	}
	
	public function setMemberType($type)
	{
		$this->member[GuildDef::MEMBER_TYPE] = $type;
	}
	
	public function addContriPoint($num)
	{
		$this->member[GuildDef::CONTRI_POINT] += $num;
		$this->member[GuildDef::CONTRI_TOTAL] += $num;
	}
	
	public function subContriPoint($num)
	{
		if ($this->member[GuildDef::CONTRI_POINT] < $num) 
		{
			return false;
		}
		else 
		{
			$this->member[GuildDef::CONTRI_POINT] -= $num;
			return true;
		}
	}
	
	public function getContriTotal()
	{
		return $this->member[GuildDef::CONTRI_TOTAL];
	}
	
	public function getContriWeek()
	{
		return $this->member[GuildDef::CONTRI_WEEK];
	}
	
	public function setContriWeek($num)
	{
		$this->member[GuildDef::CONTRI_WEEK] = $num;
	}
	
	public function addContriWeek($num)
	{
		$this->member[GuildDef::CONTRI_WEEK] += $num;
	}
	
	public function setLastContriWeek($num)
	{
		$this->member[GuildDef::LAST_CONTRI_WEEK] = $num;
	}
	
	public function getContriNum()
	{
		return $this->member[GuildDef::CONTRI_NUM];
	}

	public function setContriNum($num)
	{
		$this->member[GuildDef::CONTRI_NUM] = $num;
	}

	public function getContriTime()
	{
		return $this->member[GuildDef::CONTRI_TIME];
	}
	
	public function setContriTime($time)
	{
		$this->member[GuildDef::CONTRI_TIME] = $time;
	}

	public function getRewardTime()
	{
		return $this->member[GuildDef::REWARD_TIME];
	}

	public function setRewardTime($time)
	{
		$this->member[GuildDef::REWARD_TIME] = $time;
	}
	
	public function getRewardBuyNum()
	{
		return $this->member[GuildDef::REWARD_BUY_NUM];
	}
	
	public function setRewardBuyNum($num)
	{
		$this->member[GuildDef::REWARD_BUY_NUM] = $num;
	}
	
	public function getRewardBuyTime()
	{
		return $this->member[GuildDef::REWARD_BUY_TIME];
	}
	
	public function setRewardBuyTime($time)
	{
		$this->member[GuildDef::REWARD_BUY_TIME] = $time;
	}
	
	public function getLotteryNum()
	{
		return $this->member[GuildDef::LOTTERY_NUM];
	}
	
	public function setLotteryNum($num)
	{
		$this->member[GuildDef::LOTTERY_NUM] = $num;
	}
	
	public function setLotteryTime($time)
	{
		$this->member[GuildDef::LOTTERY_TIME] = $time;
	}
	
	public function getGrainNum()
	{
		return $this->member[GuildDef::GRAIN_NUM];
	}
	
	public function addGrainNum($num)
	{
		$this->member[GuildDef::GRAIN_NUM] += $num;
	}
	
	public function subGrainNum($num)
	{
		if ($this->member[GuildDef::GRAIN_NUM] < $num)
		{
			return false;
		}
		else
		{
			$this->member[GuildDef::GRAIN_NUM] -= $num;
			return true;
		}
	}
	
	public function getMeritNum()
	{
		return $this->member[GuildDef::MERIT_NUM];
	}
	
	public function addMeritNum($num)
	{
		$this->member[GuildDef::MERIT_NUM] += $num;
	}
	
	public function subMeritNum($num)
	{
		if ($this->member[GuildDef::MERIT_NUM] < $num)
		{
			return false;
		}
		else
		{
			$this->member[GuildDef::MERIT_NUM] -= $num;
			return true;
		}
	}
	
	public function getZgNum()
	{
		return $this->member[GuildDef::ZG_NUM];
	}
	
	public function addZgNum($num)
	{
		$this->member[GuildDef::ZG_NUM] += $num;
	}
	
	public function subZgNum($num)
	{
		if ($this->member[GuildDef::ZG_NUM] < $num)
		{
			return false;
		}
		else
		{
			$this->member[GuildDef::ZG_NUM] -= $num;
			return true;
		}
	}
	
	public function getRefreshNum()
	{
		return $this->member[GuildDef::REFRESH_NUM];
	}
	
	public function setRefreshNum($num)
	{
		$this->member[GuildDef::REFRESH_NUM] = $num;
	}
	
	public function getRejoinCd()
	{
		return $this->member[GuildDef::REJOIN_CD];
	}
	
	public function setRejoinCd($time)
	{
		$this->member[GuildDef::REJOIN_CD] = $time;
	}
	
	public function getPlayWithNum()
	{
		return $this->member[GuildDef::PLAYWITH_NUM];
	}
	
	public function setPlayWithNum($num)
	{
		$this->member[GuildDef::PLAYWITH_NUM] = $num;
	}
	
	public function getBePlayWithNum()
	{
		return $this->member[GuildDef::BE_PLAYWITH_NUM];
	}
	
	public function setBePlayWithNum($num)
	{
		$this->member[GuildDef::BE_PLAYWITH_NUM] = $num;
	}
	
	public function setPlayWithTime($time)
	{
		$this->member[GuildDef::PLAYWITH_TIME] = $time;
	}
	
	public function getFields()
	{
		return $this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS];
	}
	
	public function getFieldCount($barnLevel)
	{
		$count = 0;
		$fieldNum = btstore_get()->GUILD_BARN[GuildDef::GUILD_FIELD_NUM];
		foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
		{
			if ($fieldNum[$fieldId] <= $barnLevel)
			{
				$count++;
			}
		}
		return $count;
	}
	
	public function canRfrOwn($barnLevel)
	{
	    $conf = btstore_get()->GUILD_BARN;
	    $fieldNum = $conf[GuildDef::GUILD_FIELD_NUM];
	    $harvestNum = $conf[GuildDef::GUILD_HARVEST_NUM];
	    $maxHarvestNum =  $conf[GuildDef::MAX_HARVEST_NUM];
	    $canRfr = true;
	    foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
	    {
	        if ($fieldNum[$fieldId] <= $barnLevel
				&& $this->getFieldNum($fieldId) >= $maxHarvestNum)
	        {
	            $canRfr = FALSE;
	        }
	    }
	    return $canRfr;
	}
	
	public function refreshOwn($barnLevel, $num)
	{
		$fieldNum = btstore_get()->GUILD_BARN[GuildDef::GUILD_FIELD_NUM];
		foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
		{
			if ($fieldNum[$fieldId] <= $barnLevel)
			{
				$this->addFieldNum($fieldId, $num);
			}
		}
	}
	
	public function getFieldInfo($fieldId)
	{
		if (!isset($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId]))
		{
			$this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId] = GuildConf::$MEMBER_FIELD_DEFAULT[$fieldId];
		}
		return $this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId];
	}
	
	public function getFieldNum($fieldId)
	{
		$this->refreshFields();
		return $this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0];
	}
	
	public function addFieldNum($fieldId, $num)
	{
		$this->refreshFields();
		$maxHarvestNum = btstore_get()->GUILD_BARN[GuildDef::MAX_HARVEST_NUM];
		$this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] += $num;
		if ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] > $maxHarvestNum)
		{
			$this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] = $maxHarvestNum;
		}
	}
	
	public function subFieldNum($fieldId, $num)
	{
		$this->refreshFields();
	    if ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] < $num)
	    {
	        return false;
	    }
	    else
	    {
	        $this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] -= $num;
	        return true;
	    }
	}
	
	public function getFieldTime($fieldId)
	{
		return $this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][1];
	}
	
	public function setFieldTime($fieldId, $time)
	{
		$this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][1] = $time;
	}
	
	public function refreshAllFieldTime()
	{
		foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
		{
			if ($fieldInfo[1] == 0 || Util::isSameDay($fieldInfo[1])) 
			{
				continue;
			}
			$this->setFieldTime($fieldId, Util::getTime() - SECONDS_OF_DAY);
		}
	}
	
	public function getSkillLevel($id)
	{
		if (!isset($this->member[GuildDef::VA_MEMBER][GuildDef::SKILLS][$id]))
		{
			return 0;
		}
		return $this->member[GuildDef::VA_MEMBER][GuildDef::SKILLS][$id];
	}
	
	public function setSkillLevel($id, $level)
	{
		$this->member[GuildDef::VA_MEMBER][GuildDef::SKILLS][$id] = $level;
	}
	
	public function getAddAttr()
	{
		if (!isset($this->member[GuildDef::VA_MEMBER][GuildDef::SKILLS])) 
		{
			return array();
		}
		
		$addAttr = array();
		$conf = btstore_get()->GUILD_SKILL;
		foreach ($this->member[GuildDef::VA_MEMBER][GuildDef::SKILLS] as $id => $level)
		{
			if ($conf[$id][GuildDef::GUILD_SKILL_TYPE] == 1)
			{
				foreach ($conf[$id][GuildDef::GUILD_SKILL_ATTR] as $attrId => $attrValue)
				{
					if (!isset($addAttr[$attrId])) 
					{
						$addAttr[$attrId] = 0;
					}
					$addAttr[$attrId] += $attrValue * $level;
				}
			}
		}
		return $addAttr;
	}
	
	public function update()
	{
		$arrField = array();
		foreach ($this->member as $key => $value)
		{
			if ($this->memberBak[$key] != $value)
			{
				$arrField[$key] = $value;
			}
		}
		if (!empty($arrField))
		{
			$arrUpdateField = $arrField;
			$intersect = array_intersect(array_keys($arrField), GuildDef::$MEMBER_BOUND_FIELDS);
			//如果有交集，就更新所有绑定字段
			if (!empty($intersect)) 
			{
				foreach (GuildDef::$MEMBER_BOUND_FIELDS as $boundField)
				{
					if (!isset($arrUpdateField[$boundField]))
					{
						$arrUpdateField[$boundField] = $this->member[$boundField];
					}
				}
			}
			$guid = RPCContext::getInstance()->getUid();
			//如果非用户线程并且不是初始化, 只能更新特定字段，因为更新其他字段都转请求了
			if ($this->uid != $guid && !$this->init)
			{
				foreach($arrUpdateField as $field => $value)
				{
					$otherCanUpdateFields = array(GuildDef::GUILD_ID, GuildDef::MEMBER_TYPE, GuildDef::REJOIN_CD);
					if ($this->memberBak[GuildDef::GUILD_ID] == 0 
					&& $this->member[GuildDef::GUILD_ID] != 0) 
					{
						$otherCanUpdateFields[] = GuildDef::VA_MEMBER;
					}
					if(!in_array($field, $otherCanUpdateFields))
					{
						Logger::fatal('invalid update field:%s for other user', $field);
					}
				}
			}
			if (true == $this->init)
			{
				$this->init = false;
				GuildDao::insertMember($this->member);
			}
			else
			{
				$arrCond = array(array(GuildDef::USER_ID, '=', $this->uid));
				GuildDao::updateMember($arrCond, $arrUpdateField);
			}
		}
		$this->memberBak = $this->member;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */