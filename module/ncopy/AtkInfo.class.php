<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AtkInfo.class.php 113054 2014-06-09 08:23:24Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/AtkInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-09 08:23:24 +0000 (Mon, 09 Jun 2014) $
 * @version $Revision: 113054 $
 * @brief 
 *  
 **/
class AtkInfo
{
	protected $atkInfo	=	array();
	/**
	 * 
	 * @var AtkInfo
	 */
	protected static $_instance	=	NULL;
	
	/**
	 * 战斗数据不存储在session中，这样可以减少战斗时返回的数据（多波部队，前面的部队攻击（除了最后一个部队）就不需要更改session了）
	 */
	protected function __construct()
	{
		$uid	=	RPCContext::getInstance()->getUid();
// 		$atkInfo=	RPCContext::getInstance()->getSession(CopySessionName::ATTACKINFO);
// 		if(empty($atkInfo))
// 		{
	    $atkInfo	=	self::getMcInfoOfAttackByUid($uid);
// 		}
		$this->atkInfo	=	$atkInfo;
	}
	
	/**
	 * 获取本类唯一实例
	 *
	 * @return AtkInfo
	 */
	public static function getInstance()
	{
	    if (!self::$_instance instanceof self)
	    {
	        self::$_instance = new self();
	    }
	    return self::$_instance;
	}
	
	/**
	 * 毁掉单例，单元测试对应
	 */
	public static function release()
	{
	    if (self::$_instance != NULL)
	    {
	        self::$_instance = NULL;
	    }
	}
	
	
	public function getAtkInfo()
	{
		return $this->atkInfo;
	}
	
	public function delAtkInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		//清除session中的信息
		RPCContext::getInstance()->unsetSession(CopySessionName::ATTACKINFO);
		//清除memcache中的信息
		self::delMcAttackInfo($uid);
	}
	
	
	public function initAtkInfo($copyId,$baseId,$baseLv,$module=0)
	{
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$hpModal = HpModal::NOTINHERIT;
		if($baseLv != BaseLevel::NPC)
		{
			$hpModal = intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_hp_modal']);
		}
		$atkInfo = array(
				ATK_INFO_FIELDS::COPYID=>$copyId,
				ATK_INFO_FIELDS::BASEID=>$baseId,
				ATK_INFO_FIELDS::BASELEVEL=>$baseLv,
				ATK_INFO_FIELDS::HPMODAL=>$hpModal,
		        ATK_INFO_FIELDS::DROPHERO=>array(),
		        ATK_INFO_FIELDS::BASEPRG=>array(),
		        ATK_INFO_FIELDS::MODULE => $module,
		        
		);
		$armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
		foreach($armies as $army)
		{
			$atkInfo[ATK_INFO_FIELDS::BASEPRG][$army] = ATK_INFO_ARMY_STATUS::NOT_DEFEAT;
		}
		$atkInfo[ATK_INFO_FIELDS::STATUS]    =    ATK_INFO_STATUS::START;
		$this->atkInfo	=	$atkInfo;
	}
	
	public function setAtkInfoStatus($status)
	{
	    $this->atkInfo[ATK_INFO_FIELDS::STATUS]    =    $status;
	}
	
	public function getAtkInfoStatus()
	{
	    if(isset($this->atkInfo[ATK_INFO_FIELDS::STATUS]))
	    {
	        return $this->atkInfo[ATK_INFO_FIELDS::STATUS];
	    }
	    return false;
	}
	
	public function addDropHero($arrHero)
	{
	    $dropHero    =    $this->getDropHero();
	    $dropHero    =    array_merge($dropHero,$arrHero);
	    $this->atkInfo[ATK_INFO_FIELDS::DROPHERO]    =    $dropHero;
	}
	
	public function getDropHero()
	{
	    if(!isset($this->atkInfo[ATK_INFO_FIELDS::DROPHERO]))
	    {
	        $this->atkInfo[ATK_INFO_FIELDS::DROPHERO] = array();
	    }
	    return $this->atkInfo[ATK_INFO_FIELDS::DROPHERO];
	}
	
	
	public function setToMaxHp($hid)
	{
		$maxHP = $this->getMaxHpofHero($hid);
		$this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD][$hid][ATK_INFO_FIELDS::CARDINFO_CUR_HP]= $maxHP;
	}
	
	public function getMaxHpofHero($hid)
	{
		return $this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD][$hid][ATK_INFO_FIELDS::CARDINFO_MAX_HP];
	}
	/**
	 * 战斗完成   将战斗结果中的血量信息加入到atkinfo的hpinfo血量信息中
	 * @param array $atkHpInfo
	 */
	public function refreshHpInfo2Attackinfo($atkHpInfo)
	{
		foreach($atkHpInfo as $cardHp)
		{
			$hid = $cardHp['hid'];
			$this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD]
			                [$hid][ATK_INFO_FIELDS::CARDINFO_CUR_HP]=$cardHp['hp'];
		}
	}
	
	/**
	 * 战斗之前  将memcache中atkinfo中的血量信息加入到武将的战斗数据中
	 * @param array $fmt
	 */
	public function addHpInfo2Formation($fmt)
	{		
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::CARDINFO]))
		{
			$this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD] = array();
		}
		$hpinfo		=	$this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD];
		if(isset($this->atkInfo[ATK_INFO_FIELDS::HPMODAL]) == FALSE)
		{
		    throw new FakeException('no hpmodal in atkInfo');
		}
		$hpModal	=	$this->atkInfo[ATK_INFO_FIELDS::HPMODAL];
		//将attackinfo中的卡牌的血量加入到阵型formation中
		for($i = 0; $i < FormationDef::FORMATION_SIZD ; ++ $i)
		{
			if(isset($fmt[$i]))
			{
				$hid = $fmt[$i][PropertyKey::HID];
				//如果attackinfo中没有卡牌血量信息  那么使用阵型中的最大血量初始化
				if(isset($hpinfo[$hid]))
				{
					if($hpModal == HpModal::INHERIT || ($hpinfo[$hid][ATK_INFO_FIELDS::CARDINFO_CUR_HP] <= 0))
					{
						$fmt[$i][PropertyKey::CURR_HP] = $hpinfo[$hid][ATK_INFO_FIELDS::CARDINFO_CUR_HP];
					}
				}
				else //如果上一场战斗中没有此hid   加入到atkinfo的hpinfo中
				{
					$hpinfo[$hid][ATK_INFO_FIELDS::CARDINFO_CUR_HP]	=	$fmt[$i][PropertyKey::MAX_HP];
					$hpinfo[$hid][ATK_INFO_FIELDS::CARDINFO_MAX_HP]	=	$fmt[$i][PropertyKey::MAX_HP];
				}
			}
		}
		$this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD]	=	$hpinfo;
		return $fmt;
	}
	
	public function getBasePrg()
	{
		return $this->atkInfo[ATK_INFO_FIELDS::BASEPRG];
	}
	
	public function getReplayInfo()
	{
	    return $this->atkInfo[ATK_INFO_FIELDS::BASEPRG];
	}
	
	public function setBasePrgOnDefeatArmy($armyId,$brid)
	{
		$this->atkInfo[ATK_INFO_FIELDS::BASEPRG][$armyId]	=	$brid;		
	}
	public function saveAtkInfo()
	{
// 		RPCContext::getInstance()->setSession(CopySessionName::ATTACKINFO, $this->atkInfo);
		$uid	=	RPCContext::getInstance()->getUid();
		$atkInfo	=	$this->atkInfo;
		$atkInfo[ATK_INFO_FIELDS::SAVETIME] = Util::getTime();
		self::setMcInfoOfAttack($uid, $atkInfo);
	}
	
	public function getHpInfo()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::CARDINFO]))
		{
			return array();
		}
		return $this->atkInfo[ATK_INFO_FIELDS::CARDINFO][ATK_INFO_FIELDS::CARDINFO_HP_FIELD];
	}
	public function getCopyId()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::COPYID]))
		{
			return false;
		}
		return $this->atkInfo[ATK_INFO_FIELDS::COPYID];
	}
	public function getBaseId()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::BASEID]))
		{
			return false;
		}
		return $this->atkInfo[ATK_INFO_FIELDS::BASEID];
	}
	public function getBaseLv()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::BASELEVEL]))
		{
			return -1;
		}
		return $this->atkInfo[ATK_INFO_FIELDS::BASELEVEL];
	}
	public function clearAtkInfoOnRefight()
	{
		unset($this->atkInfo[ATK_INFO_FIELDS::CARDINFO]);
		unset($this->atkInfo[ATK_INFO_FIELDS::SAVETIME]);
		foreach($this->atkInfo[ATK_INFO_FIELDS::BASEPRG] as $armyId => $army_status)
		{
			$this->atkInfo[ATK_INFO_FIELDS::BASEPRG][$armyId] = 0;
		}
	}
	public function getReviveNum()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::REVIVENUM]))
		{
			$this->atkInfo[ATK_INFO_FIELDS::REVIVENUM] = 0;
		}
		return $this->atkInfo[ATK_INFO_FIELDS::REVIVENUM];
	}
	public function addReviveNum()
	{
		if(!isset($this->atkInfo[ATK_INFO_FIELDS::REVIVENUM]))
		{
			$this->atkInfo[ATK_INFO_FIELDS::REVIVENUM] = 0;
		}
		$this->atkInfo[ATK_INFO_FIELDS::REVIVENUM]++;
	}
	
	private function getMcInfoOfAttackByUid($uid)
	{
		$key = self::getAttackInfoMcKey($uid);
		$ret = McClient::get($key);
		if(empty($ret))
		{
		    Logger::trace('attackinfo in mc is null.');
			return array();
		}
		//atkinfo过期了
// 		if(Util::getTime() - $ret[ATK_INFO_FIELDS::SAVETIME] > CopyConf::$MC_EXPIRE_TIME)
// 		{
// 		    Logger::info('the attackinfo in mc expired.save time % now time %s',$ret[ATK_INFO_FIELDS::SAVETIME],
// 		            Util::getTime());
// 			return array();
// 		}
		return $ret;
	}
	/**
	 * 将据点的战斗信息写入到Memcache中
	 * @param int $uid
	 * @param array $base_info
	 */
	private function setMcInfoOfAttack($uid,$atkInfo)
	{
		$key = self::getAttackInfoMcKey($uid);
		if(empty($atkInfo))
		{
			throw new FakeException('the attack_info to set to mc is null');
		}
		if(McClient::set($key, $atkInfo)!='STORED')
        {
            throw new FakeException('setMcInfoOfAttack failed.');
        }
		return 'ok';
	}
	
	public static function delMcAttackInfo($uid)
	{
		$key = self::getAttackInfoMcKey($uid);
		McClient::del($key);
		return 'ok';
	}
	
	private static function getAttackInfoMcKey($uid)
	{
		$key = $uid . '.base.attack';
		return $key;
	}
	
	public function getBattleType()
	{
	    if(isset($this->atkInfo[ATK_INFO_FIELDS::MODULE]))
	    {
	        return $this->atkInfo[ATK_INFO_FIELDS::MODULE];
	    }
	    return 0;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */