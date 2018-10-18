<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyStar.class.php 242361 2016-05-12 08:37:49Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/MyStar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-12 08:37:49 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242361 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : MyStar
 * Description : 名将数据持有类
 * Inherit     :
 **********************************************************************************************************************/

class MyStar
{
	private $uid = 0;								// 用户ID
	private $star = NULL;							// 名将数据
	private $starModify = NULL; 					// 修改后的名将数据
	private static $arrStar = array();				// 实例对象数组

	/**
	 * 获取本类的实例
	 * 
	 * @param int $uid								用户id
	 * @return MyStar
	 */
	public static function getInstance($uid)
	{
		if (!isset(self::$arrStar[$uid]))
		{
			self::$arrStar[$uid] = new self($uid);
		}
		return self::$arrStar[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrStar = array();
		}
		else if (isset(self::$arrStar[$uid]))
		{
			unset(self::$arrStar[$uid]);
		}
	}
	
	/**
	 * 构造函数，获取 session信息
	 * 没有名将信息的话，star和starModify都为空
	 * 依赖于用户有名将信息就一定有后宫信息
	 */
	private function __construct($uid)
	{
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		// 如果在用户当前的线程中
		if ($uid == RPCContext::getInstance()->getUid())
		{
			// 从session中取得用户有关名将的所有信息
			$allStar = RPCContext::getInstance()->getSession(StarDef::STAR_SESSION_KEY);
			// session中没有取到数据
			if(empty($allStar))
			{
				// 查询数据库，分别获取用户的后宫信息和所有名将信息(不含uid)
				// 没有处理：有后宫信息，无名将信息这种情况
				$allStar = StarDao::selectAllStar($uid);
				$star = StarDao::selectStar($uid);
				if (!empty($star))
				{
					//出现数据不一致的情况需要修数据
					if (empty($allStar)) 
					{
						Logger::fatal('fixed me! table t_all_star has no data!');
						$allStar = StarDao::initAllStar($uid);
					}
					// 以名将id为键值索引
					$allStar[StarDef::STAR_LIST] = Util::arrayIndex($star, StarDef::STAR_ID);
					Logger::trace('user:%d star list:%s', $uid, $allStar[StarDef::STAR_LIST]);
				}
				// 设置进session
				RPCContext::getInstance()->setSession(StarDef::STAR_SESSION_KEY, $allStar);
			}
		}
		else // 非用户当前线程
		{
			$allStar = StarDao::selectAllStar($uid);
			$star = StarDao::selectStar($uid);
			if (!empty($star)) 
			{
				if (empty($allStar))
				{
					Logger::fatal('fixed me! table t_all_star has no data!');
					$allStar = StarDao::initAllStar($uid);
				}
				$allStar[StarDef::STAR_LIST] = Util::arrayIndex($star, StarDef::STAR_ID);	
				Logger::trace('user:%d star list:%s', $uid, $allStar[StarDef::STAR_LIST]);
			}	
		}
		$this->uid = $uid;
		$this->star = $allStar;
		$this->starModify = $allStar;
		if (!empty($allStar)) 
		{
			$this->refresh();
		}
	}
	
	/**
	 * 刷新每日数据
	 */
	public function refresh()
	{
		$now = Util::getTime();
		$sendTime = $this->getSendTime();
		if (!Util::isSameDay($sendTime))
		{
			$this->setSendNum(0);
			$this->setSendTime($now);
			$this->setDrawNum(0);
			$actInfos = $this->getActInfo();
			foreach ($actInfos as $actId => $actInfo)
			{
				$this->setActNum($actId, 0);
			}
		}
	}
	
	/**
	 * 初始化用户数据
	 */
	public function initInfo()
	{
		$this->setSendNum(0);
		$this->setSendTime(0);
		$this->setDrawNum(0);
		$this->starModify[StarDef::STAR_VA_INFO] = array();
		$this->starModify[StarDef::STAR_LIST] = array();
	}
	
	/**
	 * 获取用户的所有信息
	 * 
	 * @return array mixed                  	所有信息
	 */
	public function getAllInfo()
	{
		if (empty($this->starModify))
		{
			return array();
		}
		return $this->starModify;
	}
	
	/**
	 * 获取用户的所有行为信息
	 *
	 * @return array mixed                  	所有行为信息
	 */
	public function getActInfo()
	{
		if (!isset($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_ACT])) 
		{
			return array();
		}
		return $this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_ACT];
	}
	
	/**
	 * 获取用户的所有名将模板id
	 * 
	 * @return array $allStid					所有名将模板id
	 */
	public function getAllStarTid()
	{
		if (empty($this->starModify)) 
		{
			Logger::trace('user has no star.');
			return array();
		}
		return Util::arrayIndexCol($this->starModify[StarDef::STAR_LIST], StarDef::STAR_ID, StarDef::STAR_TID);
	}
	
	/**
	 * 获取用户的所有名将技能id
	 *
	 * @return array $allStid					所有名将技能id
	 */
	public function getAllStarSkill()
	{
		if (empty($this->starModify))
		{
			Logger::trace('user has no star.');
			return 0;
		}
		return Util::arrayIndexCol($this->starModify[StarDef::STAR_LIST], StarDef::STAR_ID, StarDef::STAR_FEEL_SKILL);
	}
	
	/**
	 * 获取用户的所有名将等级
	 *
	 * @return array $allStid					所有名将技能id
	 */
	public function getAllStarLevel()
	{
		if (empty($this->starModify))
		{
			Logger::trace('user has no star.');
			return array();
		}
		return Util::arrayIndexCol($this->starModify[StarDef::STAR_LIST], StarDef::STAR_TID, StarDef::STAR_LEVEL);
	}
	
	/**
	 * 获取用户的某个名将信息
	 *
	 * @param int $sid							名将id
	 * @return array mixed                  	名将信息
	 */
	public function getStarInfo($sid)
	{
		if (empty($this->starModify[StarDef::STAR_LIST][$sid])) 
		{
			return array();
		}
		return $this->starModify[StarDef::STAR_LIST][$sid];
	}
	
	/**
	 * 获取用户当天使用的金币赠送次数
	 * 
	 * @return int $num                  		当天使用的金币赠送次数
	 */
	public function getSendNum()
	{
		return $this->starModify[StarDef::STAR_SEND_NUM];		
	}
	
	/**
	 * 设置用户当天使用的金币赠送次数
	 *
	 * @param int $num							金币赠送次数
	 */
	public function setSendNum($num)
	{
		$this->starModify[StarDef::STAR_SEND_NUM] = $num;
	}
	
	/**
	 * 获取用户的上次刷新时间
	 *
	 * @return int $time                    	上次刷新时间
	 */
	public function getSendTime()
	{
		return $this->starModify[StarDef::STAR_SEND_TIME];
	}
	
	/**
	 * 设置用户的刷新时间
	 *
	 * @param int $time                    		刷新时间
	 */
	public function setSendTime($time)
	{
		$this->starModify[StarDef::STAR_SEND_TIME] = $time;
	}
	
	/**
	 * 获取用户当天使用的翻牌次数
	 *
	 * @return int $num                  	
	 */
	public function getDrawNum()
	{
		return $this->starModify[StarDef::STAR_DRAW_NUM];
	}
	
	/**
	 * 设置用户当天使用的翻牌次数
	 *
	 * @param int $num							翻牌次数
	 */
	public function setDrawNum($num)
	{
		$this->starModify[StarDef::STAR_DRAW_NUM] = $num;
	}
	
	/**
	 * 获取用户当天某个行为的使用次数
	 *
	 * @param int $actId						行为id
	 */
	public function getActNum($actId)
	{
		if (empty($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_ACT][$actId]))
		{
			return 0;
		}
		return $this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_ACT][$actId];
	}
	
	/**
	 * 设置用户使用这个行为的次数
	 *
	 * @param int $actId 						行为id
	 * @param int $num							行为次数
	 */
	public function setActNum($actId, $num)
	{
		$this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_ACT][$actId] = $num;
	}
	
	/**
	 * 获得名将的翻牌花型
	 *
	 * @param int $sid
	 */
	public function getStarDraw($sid)
	{
		if (empty($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_DRAW][$sid]))
		{
			return array();
		}
		return $this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_DRAW][$sid];
	}
	
	/**
	 * 设置名将的翻牌花型
	 *
	 * @param int $sid 					
	 * @param int $draw
	 */
	public function setStarDraw($sid, $draw)
	{
		$this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_DRAW][$sid] = $draw;
	}
	
	/**
	 * 清空名将的翻牌花型信息
	 * 
	 * @param int $sid
	 */
	public function unsetStarDraw($sid)
	{
		unset($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_DRAW][$sid]);
	}
	
	/**
	 * 获得主角装备的名将技能
	 *
	 */
	public function getEquipSkill()
	{
		if (empty($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_SKILL]))
		{
			return 0;
		}
		return $this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_SKILL];
	}
	
	/**
	 * 设置主角装备的名将技能
	 *
	 * @param int $sid
	 */
	public function setEquipSkill($sid)
	{
		$this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_SKILL] = $sid;
	}
	
	/**
	 * 重置主角装备的名将技能
	 *
	 */
	public function unsetEquipSkill()
	{
		unset($this->starModify[StarDef::STAR_VA_INFO][StarDef::STAR_SKILL]);
	}
	
	/**
	 * 获得某个名将的模板id
	 * 
	 * @param int $sid
	 */
	public function getStarStid($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_TID];
	}
	
	/**
	 * 获得名将的好感度值
	 *
	 * @param int $sid							名将id
	 */
	public function getStarExp($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_TOTAL_EXP];
	}
	
	/**
	 * 设置名将的好感度值
	 * 
	 * @param int $sid							名将id
	 * @param int $exp							好感度值
	 */
	public function setStarExp($sid, $exp)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_TOTAL_EXP] = $exp;
	}
	
	/**
	 * 获得名将的好感度等级
	 *
	 * @param int $sid							名将id
	 */
	public function getStarLevel($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_LEVEL];
	}

	/**
	 * 设置名将的好感度等级
	 * 
	 * @param int $sid							名将id
	 * @param int $level						好感度等级
	 */
	public function setStarLevel($sid, $level)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_LEVEL] = $level;
	}
	
	/**
	 * 获得名将的感悟技能
	 *
	 * @param int $sid							名将id
	 */
	public function getStarFeelSkill($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_SKILL];
	}
	
	/**
	 * 设置名将的感悟技能
	 *
	 * @param int $sid							名将id
	 * @param int $skill						技能id
	 */
	public function setStarFeelSkill($sid, $skill)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_SKILL] = $skill;
	}
	
	/**
	 * 获得名将的感悟值
	 *
	 * @param int $sid							名将id
	 */
	public function getStarFeelExp($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_TOTAL_EXP];
	}
	
	/**
	 * 设置名将的感悟值
	 *
	 * @param int $sid							名将id
	 * @param int $exp							感悟值
	 */
	public function setStarFeelExp($sid, $exp)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_TOTAL_EXP] = $exp;
	}
	
	/**
	 * 获得名将的感悟等级
	 *
	 * @param int $sid							名将id
	 */
	public function getStarFeelLevel($sid)
	{
		return $this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_LEVEL];
	}
	
	/**
	 * 设置名将的感悟等级
	 *
	 * @param int $sid							名将id
	 * @param int $level						感悟等级
	 */
	public function setStarFeelLevel($sid, $level)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_FEEL_LEVEL] = $level;
	}
	
	/**
	 * 设置名将的武将列传副本通关次数
	 * 
	 * @param int $sid
	 * @param int $num
	 */
	public function setStarPassHCopyNum($sid, $num)
	{
		$this->starModify[StarDef::STAR_LIST][$sid][StarDef::STAR_PASS_HCOPY_NUM] = $num;
	}
	
	/**
	 * 给用户的后宫里添加一位名将
	 * 
	 * @param int $stid 						名将模板id
	 * @return int $sid							名将id
	 */
	public function addNewStar($stid)
	{
		Logger::trace('MyStar::addNewStar Start.');
		
		if (!isset(btstore_get()->STAR[$stid])) 
		{
			throw new FakeException('star template id:%d is not exist!', $stid);
		}
		
		// 生成名将的id值
		$sid = IdGenerator::nextId(StarDef::STAR_ID);

		// 初始化一位名将的信息
		$star = array(
				StarDef::STAR_ID => $sid,
				StarDef::STAR_TID => $stid,
				StarDef::STAR_LEVEL => 0,
				StarDef::STAR_TOTAL_EXP => 0,
				StarDef::STAR_FEEL_SKILL => 0,
				StarDef::STAR_FEEL_LEVEL => 0,
				StarDef::STAR_FEEL_TOTAL_EXP => 0,
				StarDef::STAR_PASS_HCOPY_NUM => 0,
		);	 

		// 添加到用户的数据中
		$this->starModify[StarDef::STAR_LIST][$sid] = $star;
		
		Logger::trace('MyStar::addNewStar End.');
		return $sid;
	}

	/**
	 * 将数据保存至数据库
	 */
	public function update()
	{
		Logger::trace('MyStar::update Start.');
		
		//目前只能在自己的连接中改自己的数据
		$uid = RPCContext::getInstance()->getUid() ;
		if($uid != $this->uid)
		{
			throw new InterException('Cant update star in other connection. uid:%d session, uid:%d', $uid, $this->uid);
		}
		
		// 检查是否有差异
		$arrField = array();
		foreach ($this->starModify as $key => $value)
		{
			if (!isset($this->star[$key]) 
			|| $this->star[$key] != $value)
			{
				$arrField[$key] = $this->starModify[$key];
			}
		}
		
		if(!empty($arrField))
		{
			// 检查名将信息部分
			if (key_exists(StarDef::STAR_LIST, $arrField) == true)
			{
				foreach ($arrField[StarDef::STAR_LIST] as $sid => $starInfo)
				{
					if (!isset($this->star[StarDef::STAR_LIST][$sid]) 
					|| $this->star[StarDef::STAR_LIST][$sid] != $starInfo) 
					{
						// 更新到数据库,加上uid
						$starInfo[StarDef::STAR_USER_ID] = $uid;
						StarDao::insertOrUpdateStar($starInfo);
					}
				}
				unset($arrField[StarDef::STAR_LIST]);
			}
		}
			
		// 剩下的字段直接更新到allstar表
		if (!empty($arrField))
		{
			// 直接更新到数据库，加上uid
			$arrField = $this->starModify;
			unset($arrField[StarDef::STAR_LIST]);
			$arrField[StarDef::STAR_USER_ID] = $uid;
			StarDao::insertOrUpdateAllStar($arrField);
		}

		// 在自己的连接中，则更新到session中
		RPCContext::getInstance()->setSession(StarDef::STAR_SESSION_KEY, $this->starModify);
		
		// 同步数据
		$this->star = $this->starModify;
		
		Logger::trace('MyStar::update End.');
	}	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */