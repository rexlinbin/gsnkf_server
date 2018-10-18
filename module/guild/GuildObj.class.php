<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildObj.class.php 230613 2016-03-02 10:42:57Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/GuildObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:42:57 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230613 $
 * @brief 
 *  
 **/
class GuildObj
{
	private $guildId = 0;							// 军团id
	private $guild = NULL;							// 修改数据
	private $guildBak = NULL; 						// 原始数据
	private $arrLockField = NULL;					// 锁的字段
	/**
	 * 
	 * @var Locker
	 */
	private static $locker = NULL;					// 锁
	private static $arrGuild = NULL;				// 实例对象数组
	
	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return GuildObj
	 */
	public static function getInstance($guildId, $arrLockField = array())
	{
		if (!isset(self::$arrGuild[$guildId]))
		{
			self::$arrGuild[$guildId] = new self($guildId, $arrLockField);
		}
		else
		{
		    $guild = self::$arrGuild[$guildId];
		    $preArrLockField = $guild->getArrLockField();
		    //两次锁的字段有不同的
		    $diff = array_diff($arrLockField, $preArrLockField);
		    if (!empty($diff))
		    {
		    	$preLockStr = implode(",", $preArrLockField);
		    	$nowLockStr = implode(",", $arrLockField);
		        if(empty($preArrLockField))
		        {
		            Logger::info('reload guild. prelock:%s nowlock:%s', $preLockStr, $nowLockStr);
		        }
		        else
		        {
		        	//两次锁的字段有相同的，会造成死锁
		        	$intersect = array_intersect($arrLockField, $preArrLockField);
		        	if (!empty($intersect)) 
		        	{
		        		throw new FakeException('dead lock! prelock:%s nowlock:%s', $preLockStr, $nowLockStr);
		        	}
		        	else 
		        	{
		        		Logger::warning('reload guild. prelock:%s nowlock:%s', $preLockStr, $nowLockStr);
		        	}
		        }
		        self::$arrGuild[$guildId] = new self($guildId, $arrLockField);
		    }
		}
		return self::$arrGuild[$guildId];
	}
	
	public static function release($guildId)
	{
		if ($guildId == 0)
		{
			self::$arrGuild = array();
		}
		else if (isset(self::$arrGuild[$guildId]))
		{
			unset(self::$arrGuild[$guildId]);
		}
	}
	
	public static function createGuild($uid, $name, $slogan, $post, $passwd)
	{
	    $initInfo = self::init();
	    $initInfo[GuildDef::CREATE_UID] = $uid;
	    $initInfo[GuildDef::JOIN_NUM] = 1;
	    $initInfo[GuildDef::GUILD_NAME] = $name;
	    $initInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::SLOGAN] = $slogan;
	    $initInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::POST] = $post;
	    $initInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD] = $passwd;
	    $guildId = GuildDao::insertGuild($initInfo);
	    if (!empty($guildId)) 
	    {
	    	$initInfo[GuildDef::GUILD_ID] = $guildId;
	    	self::$arrGuild[$guildId] = new self($guildId, array(), $initInfo);
	    }
	    return $guildId;
	}
	
	public function __construct($guildId, $arrLockField, $info = array())
	{
	    if(empty($guildId))
	    {
	        throw new FakeException('invalid guildId:%d',$guildId);
	    }
	    $this->guildId = $guildId;
	    $this->arrLockField = $arrLockField;
	    //先加锁，再取数据
	    $this->lockArrField();
	    if(empty($info))
	    {
	        $info = GuildDao::selectGuild($this->guildId);
	    }
	    //还是取不到数据
		if (empty($info))
		{
			throw new InterException("empty info guildId:%d", $this->guildId);
		}
		$this->guild = $info;
		$this->refresh();
		//只进行局部更新
		$this->guildBak = $this->guild;
	}
	
	public function getArrLockField()
	{
	    return $this->arrLockField;
	}
	
	public function lockArrField()
	{
		sort($this->arrLockField);
	    if(empty($this->arrLockField))
	    {
	        Logger::debug('guildId:%d is no lock', $this->guildId);
	        return;
	    }
	    if(empty(self::$locker))
	    {
	        self::$locker = new Locker();
	    }
	    foreach ($this->arrLockField as $field)
	    {
	        if (!in_array($field, GuildDef::$GUILD_FIELDS_LOCK))
	        {
	            throw new InterException('field:%s is not in guild field lock.', $field);
	        }
	        $lockKey = GuildDef::GUILD_LOCK_KEY_PREFIX."$field.".$this->guildId;
	        self::$locker->lock($lockKey);
	    }
	}
	
	public function unlockArrField()
	{
	    if(empty($this->arrLockField))
	    {
	        return;
	    }
	    foreach ($this->arrLockField as $field)
	    {
	        if (!in_array($field, GuildDef::$GUILD_FIELDS_LOCK))
	        {
	            throw new InterException('field:%s is not in guild field lock.', $field);
	        }
	        $lockKey = GuildDef::GUILD_LOCK_KEY_PREFIX."$field.".$this->guildId;
	        self::$locker->unlock($lockKey);
	    }
	    $this->arrLockField = array();
	}
	
	public static function init()
	{
		$now = Util::getTime();
		$arrField = array(
				GuildDef::GUILD_NAME => '',
				GuildDef::GUILD_LEVEL => GuildConf::GUILD_DEFAULT_LEVEL,
				GuildDef::GUILD_ICON => key(btstore_get()->GUILD_ICON->toArray()),
				GuildDef::FIGHT_FORCE => 0,
				GuildDef::UPGRADE_TIME => $now,
				GuildDef::CREATE_UID => 0,
				GuildDef::CREATE_TIME => $now,
				GuildDef::JOIN_NUM => 0,
				GuildDef::JOIN_TIME => $now,
				GuildDef::CONTRI_NUM => 0,
				GuildDef::CONTRI_TIME => $now,
				GuildDef::REWARD_NUM => 0,
				GuildDef::REWARD_TIME => $now,
				GuildDef::GRAIN_NUM => 0,
				GuildDef::ATTACK_NUM => 0,
				GuildDef::DEFEND_NUM => 0,
		        GuildDef::ROBNUM_RFRTIME => $now,
				GuildDef::REFRESH_NUM => 0,
		        GuildDef::REFRESH_NUM_BYGUILDEXP => 0,
		        GuildDef::RFRNUM_RFRTIME => $now,
				GuildDef::FIGHT_BOOK => btstore_get()->GUILD_BARN[GuildDef::GUILD_CHALLENGE_FREE],
		        GuildDef::FIGHTBOOK_RFRTIME => $now,
				GuildDef::CURR_EXP => GuildConf::GUILD_DEFAULT_EXP,
				GuildDef::SHARE_CD => 0,
				GuildDef::STATUS => GuildStatus::OK,
				GuildDef::VA_INFO => GuildConf::$GUILD_BUILD_DEFAULT,
		);
		$arrField[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::FIELDS] = GuildConf::$GUILD_FIELD_DEFAULT;
		return $arrField;
	}

	public function refresh()
	{
		if (empty($this->guild[GuildDef::GUILD_ICON])) 
		{
			$defaultIcon = key(btstore_get()->GUILD_ICON->toArray());
			$this->setGuildIcon($defaultIcon);
		}
		
		$now = Util::getTime();	
		if (!Util::isSameDay($this->guild[GuildDef::JOIN_TIME]))
		{
		    $this->setJoinNum(0);
		    $this->setJoinTime($now);
		}		
		if (!Util::isSameDay($this->guild[GuildDef::CONTRI_TIME]))
		{
		    $this->setContriNum(0);
		    $this->setContriTime($now);
		}
		if (!Util::isSameDay($this->guild[GuildDef::REWARD_TIME]))
		{
			$this->setRewardNum(0);
			$this->setRewardTime($now);
		}		
		if (!Util::isSameDay($this->guild[GuildDef::ROBNUM_RFRTIME]))
		{
		    $this->setAttackNum(0);
		    $this->setDefendNum(0);
		    $this->setRobNumRfrTime($now);
		}		
		if (!Util::isSameDay($this->guild[GuildDef::RFRNUM_RFRTIME]))
		{
		    $this->setRefreshNum(0);
		    $this->setRefreshNumByGuildExp(0);
		    $this->setRefreshNumRfrTime($now);
		}		
		if (!Util::isSameDay($this->guild[GuildDef::FIGHTBOOK_RFRTIME]))
		{
			$defaultNum = btstore_get()->GUILD_BARN[GuildDef::GUILD_CHALLENGE_FREE];
		    $this->setFightBook($defaultNum);
		    $this->setFightBookRfrTime($now);
		}
		
		//是以数组的形式存储的，如果以后修改改变的话一定要通知前端同步修改
		foreach (GuildConf::$GUILD_BUILD_DEFAULT as $type => $buildInfo)
		{
			$this->getBuildInfo($type);
		}
		$this->getFields();
		
		//更新军团共享商品列表的购买次数
		if (!empty($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS])) 
		{
			$conf = btstore_get()->GUILD_GOODS;
			foreach ($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] as $goodsId => $goodsInfo)
			{
				$exchangeOffset = MallDef::REFRESH_OFFSET;
				if (!empty($conf[$goodsId][MallDef::MALL_EXCHANGE_OFFSET]))
				{
					$exchangeOffset = $conf[$goodsId][MallDef::MALL_EXCHANGE_OFFSET];
				}
				$exchangeType = $conf[$goodsId][MallDef::MALL_EXCHANGE_TYPE];
				if (GuildDef::REFRESH_EVERYDAY == $exchangeType
				&& !Util::isSameDay($goodsInfo[GuildDef::TIME], $exchangeOffset))
				{
					unset($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId]);
				}
			}
		}
	}
	
	public function refreshGoods()
	{
		//是否到达刷新时间
		$now = Util::getTime();
		$refreshCd = $this->getRefreshCd();
		if($now < $refreshCd)
		{
			return;
		}
		
		//开始刷新商品
		$type = GuildDef::STORE;
		$conf = btstore_get()->GUILD_STORE;
		$specialGoods = $conf[GuildDef::GUILD_SPECIAL_GOODS]->toArray();
		$specialNum = $conf[GuildDef::GUILD_SPECIAL_NUM];
		$specialCd = $conf[GuildDef::GUILD_SPECIAL_CD];
		//是否开启福利活动
		$specialWeal = EnWeal::getWeal(WealDef::GUILD_GOODS_SALE);
		if (!empty($specialWeal))
		{
			$specialGoods = $specialWeal;
		}
		
		//处理刷新时间
		if (empty($refreshCd))
		{
			$date = intval(strftime("%Y%m%d", $now));
			$refreshCd = strtotime($date . " " . GuildConf::SPECIAL_REFRESH_TIME);
			if ($now < $refreshCd)
			{
				$date = intval(strftime("%Y%m%d", strtotime("- 1 day", $now)));
				$refreshCd = strtotime($date . " " . GuildConf::SPECIAL_REFRESH_TIME);
			}
			while ($refreshCd < $now)
			{
				$refreshCd += $specialCd;
			}
		}
		else
		{
			$refreshCd = $refreshCd + (intval(($now - $refreshCd)/$specialCd)+1) * $specialCd;
		}
		
		//处理刷新列表
		$goods = $this->getGoods();
		$level = $this->getBuildLevel($type);
		$conf = btstore_get()->GUILD_GOODS->toArray();
		foreach ($conf as $goodsId => $goodsConf)
		{
			//排除掉不在特殊商品列表里的商品
			if (!in_array($goodsId, $specialGoods))
			{
				unset($conf[$goodsId]);
				continue;
			}
			//排除掉非珍品类商品
			if (GuildDef::SPECIAL != $goodsConf[GuildDef::GUILD_GOODS_TYPE])
			{
				unset($conf[$goodsId]);
				continue;
			}
			//排除掉商店等级不够的商品
			if ($level < $goodsConf[GuildDef::GUILD_STORE_LEVEL])
			{
				unset($conf[$goodsId]);
				continue;
			}
			//排除掉达到购买上限的商品
			if (isset($goods[$goodsId])
			&& $goods[$goodsId][GuildDef::SUM] >= $goodsConf[GuildDef::GUILD_GOODS_LIMIT])
			{
				unset($conf[$goodsId]);
				continue;
			}
		}
		if (count($conf) < $specialNum)
		{
			$specialNum = count($conf);
			Logger::info('no enough goods to refresh! conf:%s', $conf);
		}
		$refreshList = Util::noBackSample($conf, $specialNum);
		$this->setRefreshList($refreshList);
		$this->setRefreshCd($refreshCd);
		
		Logger::trace('goods:%s refreshList:%s, refreshCd:%d', $goods, $refreshList, $refreshCd);
	}
	
	public function getInfo()
	{
		return $this->guild;
	}
	
	public function getTemplateInfo()
	{
		return array_slice($this->guild, 0, 2);
	}
	
	public function getGuildId()
	{
		return $this->guildId;
	}
	
	public function getGuildName()
	{
		return $this->guild[GuildDef::GUILD_NAME];
	}
	
	public function setGuildName($name)
	{
		$this->guild[GuildDef::GUILD_NAME] = $name;
	}
	
	public function getGuildLevel()
	{
		return $this->guild[GuildDef::GUILD_LEVEL];
	}
	
	public function setGuildLevel($level)
	{
		$this->guild[GuildDef::GUILD_LEVEL] = $level;
	}
	
	public function setGuildIcon($icon)
	{
		$this->guild[GuildDef::GUILD_ICON] = $icon;
	}
	
	public function getGuildIcon()
	{
		return $this->guild[GuildDef::GUILD_ICON];
	}
	
	public function getFightForce()
	{
		return $this->guild[GuildDef::FIGHT_FORCE];
	}
	
	public function setFightForce($num)
	{
		$this->guild[GuildDef::FIGHT_FORCE] = $num;
	}
	
	public function getUpgradeTime()
	{
		return $this->guild[GuildDef::UPGRADE_TIME];
	}
	
	public function setUpgradeTime($time)
	{
		$this->guild[GuildDef::UPGRADE_TIME] = $time;
	}
	
	public function getJoinNum()
	{
		return $this->guild[GuildDef::JOIN_NUM];
	}

	public function setJoinNum($num)
	{
		$this->guild[GuildDef::JOIN_NUM] = $num;
	}
	
	public function setJoinTime($time)
	{
		$this->guild[GuildDef::JOIN_TIME] = $time;
	}
	
	public function setContriNum($num)
	{
		$this->guild[GuildDef::CONTRI_NUM] = $num;
	}
	
	public function addContriNum($num)
	{
		$this->guild[GuildDef::CONTRI_NUM] += $num;
	}
	
	public function setContriTime($time)
	{
		$this->guild[GuildDef::CONTRI_TIME] = $time;
	}
	
	public function getRewardNum()
	{
		return $this->guild[GuildDef::REWARD_NUM];
	}
	
	public function setRewardNum($num)
	{
		$this->guild[GuildDef::REWARD_NUM] = $num;
	}
	
	public function getRewardTime()
	{
		return $this->guild[GuildDef::REWARD_TIME];
	}
	
	public function setRewardTime($time)
	{
		$this->guild[GuildDef::REWARD_TIME] = $time;
	}
	
	public function getGrainNum()
	{
		return $this->guild[GuildDef::GRAIN_NUM];
	}
	
	public function addGrainNum($num)
	{
		$limit = $this->getGrainLimit();
		$this->guild[GuildDef::GRAIN_NUM] += $num;
		if ($this->guild[GuildDef::GRAIN_NUM] >= $limit) 
		{
			$this->guild[GuildDef::GRAIN_NUM] = $limit;
		}
	}
	
	public function subGrainNum($num)
	{
		if ($this->guild[GuildDef::GRAIN_NUM] < $num)
		{
			return false;
		}
		else
		{
			$this->guild[GuildDef::GRAIN_NUM] -= $num;
			return true;
		}
	}
	
	public function getGrainLimit()
	{
		$level = $this->getBuildLevel(GuildDef::BARN);
		return btstore_get()->GUILD_BARN[GuildDef::GUILD_GRAIN_CAPACITY][$level];
	}
	
	public function getAttackNum()
	{
		return $this->guild[GuildDef::ATTACK_NUM];
	}
	
	public function setAttackNum($num)
	{
		$this->guild[GuildDef::ATTACK_NUM] = $num;
	}
	
	public function addAttackNum($num)
	{
		$this->guild[GuildDef::ATTACK_NUM] += $num;
	}
	
	public function getDefendNum()
	{
		return $this->guild[GuildDef::DEFEND_NUM];
	}
	
	public function setDefendNum($num)
	{
		$this->guild[GuildDef::DEFEND_NUM] = $num;
	}
	
	public function addDefendNum($num)
	{
		$this->guild[GuildDef::DEFEND_NUM] += $num;
	}
	
	public function setRobNumRfrTime($time)
	{
		$this->guild[GuildDef::ROBNUM_RFRTIME] = $time;
	}
	
	public function getRefreshNum()
	{
		return $this->guild[GuildDef::REFRESH_NUM];
	}
	
	public function setRefreshNum($num)
	{
		$this->guild[GuildDef::REFRESH_NUM] = $num;
	}
	
	public function getRefreshNumByGuildExp()
	{
		return $this->guild[GuildDef::REFRESH_NUM_BYGUILDEXP];
	}
	
	public function setRefreshNumByGuildExp($num)
	{
	    $this->guild[GuildDef::REFRESH_NUM_BYGUILDEXP] = $num;
	}
	
	public function setRefreshNumRfrTime($time)
	{
	    $this->guild[GuildDef::RFRNUM_RFRTIME] = $time;
	}
	
	public function getFightBook()
	{
		return $this->guild[GuildDef::FIGHT_BOOK];
	}
	
	public function setFightBook($num)
	{
		$this->guild[GuildDef::FIGHT_BOOK] = $num;
	}
	
	public function addFightBook($num)
	{
		$this->guild[GuildDef::FIGHT_BOOK] += $num;
	}
	
	public function subFightBook($num)
	{
		$this->guild[GuildDef::FIGHT_BOOK] -= $num;
	}
	
	public function setFightBookRfrTime($time)
	{
		$this->guild[GuildDef::FIGHTBOOK_RFRTIME] = $time;
	}
	
	public function getCurrExp()
	{
		return $this->guild[GuildDef::CURR_EXP];
	}
	
	public function addCurrExp($num)
	{
		$this->guild[GuildDef::CURR_EXP] += $num;
	}
	
	public function subCurrExp($num)
	{
		if ($this->guild[GuildDef::CURR_EXP] < $num)
		{
			return false;
		}
		else
		{
			$this->guild[GuildDef::CURR_EXP] -= $num;
			return true;
		}
	}
	
	public function getShareCd()
	{
		return $this->guild[GuildDef::SHARE_CD];
	}
	
	public function setShareCd($time)
	{
		$this->guild[GuildDef::SHARE_CD] = $time;
	}
	
	public function setStatus($status)
	{
		$this->guild[GuildDef::STATUS] = $status;
	}
	
	public function setSlogan($slogan)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::SLOGAN] = $slogan;
	}
	
	public function setPost($post)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::POST] = $post;
	}
	
	public function setPasswd($passwd)
	{
		if (!empty($passwd))
		{
			$passwd = md5($passwd);
		}
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD] = $passwd;
	}
	
	public function verifyPasswd($passwd)
	{
		$oldPasswd = $this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD];
		if (empty($oldPasswd))
		{
			$oldPasswd = md5(GuildConf::DEFAULT_PASSWD);
		}
		return md5($passwd) == $oldPasswd ? true : false;
	}
	
	public function getGoods()
	{
		if (!isset($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS])) 
		{
			$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = array();
		}
		return $this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS];
	}
	
	public function getRefreshList()
	{
		if (!isset($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_LIST]))
		{
			$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_LIST] = array();
		}
		return $this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_LIST];
	}
	
	public function setRefreshList($list)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_LIST] = $list;
	}
	
	public function getRefreshCd()
	{
		if (!isset($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD])) 
		{
			$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD] = 0;
		}
		return $this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD];
	}
	
	public function setRefreshCd($time)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD] = $time;
	}
	
	public function getGoodsSum($goodsId)
	{
		if (!isset($this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId][GuildDef::SUM]))
		{
			$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId][GuildDef::SUM] = 0;
		}
		return $this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId][GuildDef::SUM];
	}
	
	public function setGoodsSum($goodsId, $num)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId][GuildDef::SUM] = $num;
	}
	
	public function addGoodsSum($goodsId, $num)
	{
		//检查是否是军团共享类型商品
		$exchangeType = btstore_get()->GUILD_GOODS[$goodsId][MallDef::MALL_EXCHANGE_TYPE];
		if (in_array($exchangeType, array(GuildDef::REFRESH_EVERYDAY, GuildDef::REFRESH_NERVER)))
		{
			$sum = $this->getGoodsSum($goodsId);
			$this->setGoodsSum($goodsId, $sum + $num);
			$this->setGoodsTime($goodsId, Util::getTime());
			//给军团所有人推送商品购买次数
			$goodsInfo = array($goodsId => array(GuildDef::SUM => $sum + $num));
			RPCContext::getInstance()->sendFilterMessage('guild', $this->guildId, PushInterfaceDef::REFRESH_GOODS, $goodsInfo);
		}
	}
	
	public function setGoodsTime($goodsId, $time)
	{
		$this->guild[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS][$goodsId][GuildDef::TIME] = $time;
	}
	
	public function canBuy($goodsId, $num)
	{
		$type = GuildDef::STORE;
		$goodsConf = btstore_get()->GUILD_GOODS[$goodsId];
		//检查是否达到商店购买等级
		$level = $this->getBuildLevel($type);
		if ($level < $goodsConf[GuildDef::GUILD_STORE_LEVEL])
		{
			return false;
		}
		
		//如果是珍品类商品，检查是不是在刷新列表里
		$refreshList = $this->getRefreshList();
		if (GuildDef::SPECIAL == $goodsConf[GuildDef::GUILD_GOODS_TYPE]
		&& !in_array($goodsId, $refreshList))
		{
			return false;
		}
		
		//检查是否是军团共享类型商品，是则需要判断军团里这个商品是否还有剩余的购买次数
		$exchangeType = $goodsConf[MallDef::MALL_EXCHANGE_TYPE];
		if (GuildDef::REFRESH_EVERYDAY == $exchangeType
		|| GuildDef::REFRESH_NERVER == $exchangeType)
		{
			$sum = $this->getGoodsSum($goodsId);
			$limit = $goodsConf[GuildDef::GUILD_GOODS_LIMIT];
			Logger::trace('num:%d, sum:%d, limit:%d', $num, $sum, $limit);
			if ($num + $sum > $limit)
			{
				return false;
			}
		}
		return true;
	}
	
	public function getBuildInfo($type)
	{
		if (!isset($this->guild[GuildDef::VA_INFO][$type]))
		{
			$this->guild[GuildDef::VA_INFO][$type] = GuildConf::$GUILD_BUILD_DEFAULT[$type];
		}
		return $this->guild[GuildDef::VA_INFO][$type];
	}
	
	public function getBuildLevel($type)
	{
		$guildLevel = $this->guild[GuildDef::GUILD_LEVEL];
		$buildLevel = $this->guild[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
		if ($type == GuildDef::GUILD) 
		{
			if ($guildLevel != $buildLevel) 
			{
				Logger::warning('guildId:%d, guildLevel:%d, buildLevel:%d', $this->guildId, $guildLevel, $buildLevel);
			}
			return $guildLevel;
		}
		else 
		{
			return $buildLevel;
		}
	}
	
	public function setBuildLevel($type, $level)
	{
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::LEVEL] = $level;
	}
	
	public function getBuildMaxLevel($type)
	{
		$confname = GuildDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		$guildLevel = $this->getGuildLevel();
		switch ($type)
		{
			case GuildDef::GUILD:
				$maxLevel = $conf[GuildDef::GUILD_MAX_LEVEL];
				break;
			case GuildDef::TEMPLE:
			case GuildDef::STORE:
			case GuildDef::COPY:
			case GuildDef::TASK:
			case GuildDef::BARN:
				$maxLevel = ceil($guildLevel * $conf[GuildDef::GUILD_LEVEL_RATIO] / 100);
				break;
		}
		return $maxLevel;
	}
	
	public function getBuildExp($type)
	{
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::ALLEXP];
	}
	
	public function addBuildExp($type, $num)
	{
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::ALLEXP] += $num;
	}

	public function getBuildTime($type, $level)
	{
		if (GuildDef::BARN != $type) 
		{
			throw new FakeException('invalid to get build:% time', $type);
		}
		//军团等级未到，粮仓未开
		if($this->isGuildBarnOpen() == FALSE)
		{
		    return -1;
		}
		//粮仓开启，默认等级要特殊处理
		if (!isset($this->guild[GuildDef::VA_INFO][$type][GuildDef::TIME][$level])) 
		{
			if ($level != GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL]) 
			{
				return -1;
			}
			else 
			{
				return 0;
			}
		}
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::TIME][$level];
	}
	
	public function addBuildTime($type, $level, $time)
	{
		if (GuildDef::BARN != $type)
		{
			throw new FakeException('invalid to add build:% time', $type);
		}
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::TIME][$level] = $time;
		if (GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL] == $level) 
		{
			$shareCd = btstore_get()->GUILD_BARN[GuildDef::GUILD_SHARE_CD];
			$this->setShareCd($time + $shareCd);
		}
	}
	
	public function getFields()
	{
		foreach (GuildConf::$GUILD_FIELD_DEFAULT as $fieldId => $fieldInfo)
		{
			$this->getFieldInfo($fieldId);
		}
		return $this->guild[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::FIELDS];
	}
	
	public function getFieldCount()
	{
		$count = 0;
		$type = GuildDef::BARN;
		$barnLevel = $this->getBuildLevel($type);
		$fieldNum = btstore_get()->GUILD_BARN[GuildDef::GUILD_FIELD_NUM];
		foreach ($this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS] as $fieldId => $fieldInfo)
		{
			if ($fieldNum[$fieldId] <= $barnLevel)
			{
				$count++;
			}
		}
		return $count;
	}
	
	public function getFieldInfo($fieldId)
	{
		$type = GuildDef::BARN;
		if (!isset($this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId]))
		{
			$this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId] = GuildConf::$GUILD_FIELD_DEFAULT[$fieldId];
		}
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId];
	}
	
	public function getFieldLevel($fieldId)
	{
		$type = GuildDef::BARN;
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId][0];
	}
	
	public function setFieldLevel($fieldId, $level)
	{
		$type = GuildDef::BARN;
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId][0] = $level;
	}
	
	public function getFieldLevelLimit()
	{
		$limit = 0;
		$conf = btstore_get()->GUILD_BARN[GuildDef::GUILD_FIELD_LEVEL];
		foreach ($conf as $key => $value)
		{
			if ($this->getBuildLevel(GuildDef::BARN) < $key)
			{
				break;
			}
			$limit = $value;
		}
		return $limit;
	}
	
	public function getFieldExp($fieldId)
	{
		$type = GuildDef::BARN;
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId][1];
	}
	
	public function setFieldExp($fieldId, $num)
	{
		$type = GuildDef::BARN;
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId][1] = $num;
	}
	
	public function addFieldExp($fieldId, $num)
	{
		$type = GuildDef::BARN;
		$level = $this->getFieldLevel($fieldId);
		$levelLimit = $this->getFieldLevelLimit();
		$num = $level >= $levelLimit ? 0 : $num;
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::FIELDS][$fieldId][1] += $num;
		$this->upgradeField($fieldId);
	}
	
	public function upgradeField($fieldId)
	{
		$expId = btstore_get()->GUILD_BARN[GuildDef::GUILD_FIELD_EXPID][$fieldId];
		$conf = btstore_get()->EXP_TBL[$expId];
		$exp = $this->getFieldExp($fieldId);
		$level = 0;
		foreach ($conf as $needLv => $needExp)
		{
			if ($exp < $needExp)
			{
				break;
			}
			$level = $needLv;
		}
		$this->setFieldLevel($fieldId, $level);
	}
	
	public function upgradeBuild($type)
	{
		$level = $this->getBuildLevel($type);
		$maxLevel = $this->getBuildMaxLevel($type);
		if ($level >= $maxLevel)
		{
			return false;
		}

		$level++;
		$needExp = $this->getBuildUpgradeExp($type, $level);
		if ($this->subCurrExp($needExp) == false)
		{
			return false;
		}
		
		$now = Util::getTime();
		$conf = btstore_get()->GUILD_BARN;
		$this->setBuildLevel($type, $level);
		$this->addBuildExp($type, $needExp);
		if (GuildDef::GUILD == $type)
		{
			$this->setGuildLevel($level);
			$this->setUpgradeTime($now);
		}
		if (isset($conf[GuildDef::GUILD_BARN_OPEN][$type])
		&& $conf[GuildDef::GUILD_BARN_OPEN][$type] == $level
		&& $this->isGuildBarnOpen())
		{
			//触发粮仓开启
			$type = GuildDef::BARN;
			$level = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
		}
		//粮仓特定等级需要额外记录升级时间
		$fieldNum = $conf[GuildDef::GUILD_FIELD_NUM]->toArray();
		if (GuildDef::BARN == $type && in_array($level, $fieldNum)) 
		{
			$this->addBuildTime($type, $level, $now);
		}
		
		return true;
	}
	
	public function getBuildUpgradeExp($type, $level)
	{
		$confname = GuildDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		$expId = $conf[GuildDef::GUILD_EXP_ID];
		$expTbl = btstore_get()->EXP_TBL[$expId];
		return $level == 1 ? $expTbl[$level] : ($expTbl[$level] - $expTbl[$level - 1]);
	}
	
	public function isGuildBarnOpen()
	{
		$openNeedLv = btstore_get()->GUILD_BARN[GuildDef::GUILD_BARN_OPEN];
		foreach($openNeedLv as $type => $needLv)
		{
			$level = $this->getBuildLevel($type);
			if($level < $needLv)
			{
				return false;
			}
		}
		return true;
	}
	
	public function isGuildTechOpen()
	{
		$openNeedLv = btstore_get()->GUILD[GuildDef::GUILD_TECH_OPEN];
		foreach($openNeedLv as $type => $needLv)
		{
			$level = $this->getBuildLevel($type);
			if($level < $needLv)
			{
				return false;
			}
		}
		return true;
	}
	
	public function getSkillLevel($id)
	{
		$type = GuildDef::TECH;
		if (!isset($this->guild[GuildDef::VA_INFO][$type][GuildDef::SKILLS][$id])) 
		{
			return 0;
		}
		return $this->guild[GuildDef::VA_INFO][$type][GuildDef::SKILLS][$id];
	}
	
	public function setSkillLevel($id, $level)
	{
		$type = GuildDef::TECH;
		$this->guild[GuildDef::VA_INFO][$type][GuildDef::SKILLS][$id] = $level;
	}
	
	public function update($noCache = false)
	{
		$arrField = array();
		foreach ($this->guild as $key => $value)
		{
			if ($this->guildBak[$key] != $value) 
			{
				$arrField[$key] = $value;
			}
		}
		if (!empty($arrField)) 
		{
		    $arrUpdateField = $arrField;
		    foreach($arrField as $field => $value)
		    {
		        if(!isset(GuildDef::$GUILD_BOUND_FIELDS[$field]))
		        {
		            continue;
		        }
		        foreach(GuildDef::$GUILD_BOUND_FIELDS[$field] as $boundField)
		        {
		            if(!isset($arrUpdateField[$boundField]))
		            {
		                $arrUpdateField[$boundField] = $this->guild[$boundField];
		            }
		        }
		    }
		    foreach ($arrUpdateField as $field => $value)
		    {
		        if (!in_array($field, $this->arrLockField)
		        && in_array($field, GuildDef::$GUILD_FIELDS_LOCK))
		        {
		            throw new InterException('update field:%s is not in lock field:%s', $field, implode(",", $this->arrLockField));
		        }
		    }
			GuildDao::updateGuild($this->guildId, $arrUpdateField, $noCache);
		}
		$this->guildBak = $this->guild;
		//解锁，清空加锁的字段
		$this->unlockArrField();
	}
	
	public static function addGuildGrainNum($guildId, $num)
	{
		if ($num == 0)
		{
			return;
		}
		
		$cond = array();
		if ($num > 0)
		{
			$opGrain = new IncOperator($num);
		}
		else
		{
			$opGrain = new DecOperator(-$num);
			$cond = array(GuildDef::GRAIN_NUM, '>=', -$num);
		}
		$arrField = array(
				GuildDef::GUILD_ID => $guildId,
				GuildDef::GRAIN_NUM => $opGrain,
		);
		
		$data = new CData();
		$data->update(GuildDef::TABLE_GUILD)
		     ->set($arrField)
		     ->where(array(GuildDef::GUILD_ID, '=', $guildId));
		if (!empty($cond))
		{
		    $data->where($cond);
		}
		$data->query();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */