<?php
/***************************************************************************
 * 
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: AchieveObj.class.php 172253 2015-05-11 11:01:44Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/AchieveObj.class.php $
 * @author $Author: ShijieHan $(huangqiang@babeltime.com)
 * @date $Date: 2015-05-11 11:01:44 +0000 (Mon, 11 May 2015) $
 * @version $Revision: 172253 $
 * @brief 
 *  
 **/
 
class AchieveObj
{
	private $uid;
	
	private $data;
	private $originData;

	public function __construct($uid)
	{
		$this->uid = $uid;
		if(RPCContext::getInstance()->getUid() == $uid)
			$sdata = RPCContext::getInstance()->getSession(AchieveDef::KEY_ACHIEVE);
		if(isset($sdata))
		{
			Logger::trace("AchieveObj. uid:%d load some data:%s from session", $this->uid, $sdata);
			$this->data = $sdata;
			$this->originData = $sdata;
		}
		else 
		{
			$this->originData = $this->data = array( AchieveDef::VAR_TYPES => array());
		}
	}
	
	private static $objMap = array();
	public static function getObj($uid) {
		if(empty($uid)) throw new FakeException("Achieve.getObj. $uid can't be empty!");
		if(!empty(self::$objMap[$uid])) {
			return self::$objMap[$uid];
		} else {
			$obj = new AchieveObj($uid);
			self::$objMap[$uid] = $obj;
			Transaction::add("achieve.$uid", $obj);
			return $obj;
		}
	}
	
	public function save()
	{
		Logger::debug("AchieveObj save. uid:$this->uid");
		$diff = array();
		foreach($this->data[AchieveDef::VAR_TYPES] as $type => $infos)
		{
			if(!isset($this->originData[AchieveDef::VAR_TYPES][$type]))
			{
				$diff += $infos;
			} 
			else 
			{
				$originInfo = $this->originData[AchieveDef::VAR_TYPES][$type];
				if($originInfo != $infos)
				{
					foreach($infos as $id => $info)
					{
						if(!isset($originInfo[$id]) || $originInfo[$id] != $info)
							$diff[$id] = $info;
					}
				}
			}
		}
		AchieveDAO::put($this->uid, $diff);
		$this->originData = $this->data;
		if(RPCContext::getInstance()->getUid() == $this->uid)
			RPCContext::getInstance()->setSession(AchieveDef::KEY_ACHIEVE, $this->data);
		
		// 新完成的成就,通知给玩家
		foreach($diff as $id => $info) {
			if($info[AchieveDef::VAR_STATUS] != AchieveDef::STATUS_FINISH)
				unset($diff[$id]);
		}
		if( !empty($diff) )
		{
			RPCContext::getInstance()->sendMsg(array($this->uid), PushInterfaceDef::ACHIEVE_NEW_FINISH, array_keys($diff));
		}
	}
	
	public function commit()
	{
		$this->save();
	}
	
	private function loadTypeInfos($type)
	{
		$tdata = $this->getTypeData($type);
		if(isset($tdata))
		{
			Logger::debug("AchieveObj. loadTypeInfos.uid:%d type:%d has loaded", $this->uid, $type);
			return;
		}
		$typeInfos = array();
		$infos = AchieveDAO::getType($this->uid, $type);
		foreach($infos as $_ => $info)
		{
			$typeInfos[$info[AchieveDef::VAR_AID]] = $info;
		}
		$this->setTypeData($type, $typeInfos);
		$this->originData[AchieveDef::VAR_TYPES][$type] = $typeInfos;
		Logger::debug("AchieveObj.loadTypeInfos. type:$type end.");
	}
	
	private function loadInfos()
	{
		$conf = self::getConf();
		if(count($conf[AchieveDef::CONF_TYPES]) == count($this->data[AchieveDef::VAR_TYPES]))
		{
			Logger::debug("AchieveObj. loadInfos. uid:$this->uid has load all infos.");
			return;
		}
		$types = AchieveDef::$ALL_TYPES;
		foreach($types as $type => $tname)
		{
			$infos = $this->getTypeData($type);
			if(!isset($infos))
				$this->setTypeData($type, array());
			if(!isset($this->originData[AchieveDef::VAR_TYPES][$type]))
				$this->originData[AchieveDef::VAR_TYPES][$type] = array();
		}
		$all = AchieveDAO::getAll($this->uid);
		foreach($all as $_ => $info)
		{
			$aid = $info[AchieveDef::VAR_AID];
			$type = $conf[AchieveDef::CONF_IDS][$aid][AchieveDef::CONF_TYPE]; //配置里的typeid
			$infos = $this->getTypeData($type); //已经加载的info
			if(!isset($infos[$aid]))
			{
				$infos[$aid] = $info;
				$this->setTypeData($type, $infos);
				$this->originData[AchieveDef::VAR_TYPES][$type][$aid] = $info;
				Logger::debug("AchieveObj. initAllInfos. add from db:%s", $info);
			}
		}
		Logger::debug("AchieveObj.loadInfos. end.");
	}
	
	public function getInfos()
	{
		$this->loadInfos();
		
		Logger::debug("Achieve.getInfo uid:%d", $this->uid);
		$infos = array();
		foreach(AchieveDef::$ALL_TYPES as $type => $_)
		{
			$info = $this->getTypeInfo($type);

			foreach($info as $id => $d)
			{
				// 去掉没必要的字段
				unset($info[$id][AchieveDef::VAR_UID]);
				unset($info[$id][AchieveDef::VAR_DATA]);
				
				// 排名成就比较特殊,是向上兼容(其他都是向下兼容),故用了点技巧,把$finish_num -> MAX_BOSS_RANK - $finish_num
				// 这样就能利用现有的规则了
				if(in_array($type ,AchieveDef::$DESC_TYPES))
				{
					$info[$id][AchieveDef::VAR_FINISH_NUM] = AchieveDef::MAX_BOSS_RANK - $d[AchieveDef::VAR_FINISH_NUM];
				}
			}
			$infos += $info;
		}
		Logger::debug("Nachieve.getInfo uid:%d contents:%s", $this->uid, $infos);
		return $infos;
	}
	
	public function getTypeData($type)
	{
		return isset($this->data[AchieveDef::VAR_TYPES][$type]) ? $this->data[AchieveDef::VAR_TYPES][$type] : null;
	}
	
	public function setTypeData($type, $d)
	{
		$this->data[AchieveDef::VAR_TYPES][$type] = $d;
	}

    private function setTypeDataOfOriginData($type, $d)
    {
        $this->originData[AchieveDef::VAR_TYPES][$type] = $d;
    }
	
	public function getTypeInfo($type)
	{
		$this->initType($type);
		return $this->getTypeData($type);
	}
	
	public static function getConf() 
	{
		return btstore_get()->ACHIEVE_SYSTEM;
	}
	
	public static function getTypeConf($type)
	{
		$conf = self::getConf();
		return $conf[AchieveDef::CONF_TYPES][$type];
	}
	
	public function isAccuType($type)
	{
		return in_array($type, AchieveDef::$ACCU_TYPES);
	}
	
	/**
	 * 
	 * @param unknown $type
	 * @desc  也可以通过为每个type定义一个handler来实现，但鄙人实在没空写50个函数,
	 *    这么写灵活性稍差,但代码量低啊！万一有什么改动，得改每个子类的handler,那
	 *    太痛苦了。这是权衡后的做法。
	 */
	public function initType($type)
	{
		$this->loadTypeInfos($type);
		$confs = self::getTypeConf($type);
		$infos = $this->getTypeData($type);
		
		if(count($infos) == count($confs))
		{
			Logger::debug("AchieveObj. initType type:$type has inited.");
			return;
		}
		Logger::debug("AchieveObj.initType. before init uid:%d type:%d", $this->uid, $type);

		$finish_num = 0;
		$set = array();
        $bag = null;
        $uid = $this->uid;
        $guildInfo = null;

        /**
		switch($type)
		{
           // case AchieveDef::ARENA_RANK:
			//	$finish_num = EnArena::getPosition($uid);
			//	break;
          //  case AchieveDef::COMPETE_RANK:
			//	$finish_num = EnCompete::getRank($uid);
			//	break;
			case AchieveDef::NCOPY_STAR:
				$finish_num = MyNCopy::getInstance()->getScore();
				break;
			case AchieveDef::USER_LEVEL:
				$finish_num = EnUser::getUserObj($uid)->getLevel();
				break;
			case AchieveDef::DESTINY:
				$finish_num = EnDestiny::getActiveDestinyNum($uid);
				break;
			case AchieveDef::FIGHT_SOUL:
                $f1 = HeroLogic::getEquipNumOnHero($uid, HeroDef::EQUIP_FIGHTSOUL);
                $bag = isset($bag) ? $bag : new Bag();
				$f2 = count($bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_FIGHTSOUL));
				$finish_num = $f1 + $f2;;
				break;
			case AchieveDef::SILVER:
				$finish_num = EnUser::getUserObj($uid)->getSilver();
				break;
			case AchieveDef::PRESTIGE:
				$finish_num = EnUser::getUserObj($uid)->getPrestige();
				break;
			case AchieveDef::JEWEL:
				$finish_num = EnUser::getUserObj($uid)->getJewel();
				break;
			case AchieveDef::FRIEND:
				$finish_num = EnFriend::getFriendNum($uid);
				break;
			case AchieveDef::PET_SKILL:
				$finish_num = EnPet::getAdvPetSkillNum($uid);
				break;
			case AchieveDef::TOWER:
				$finish_num = MyTower::getInstance($uid)->getMaxLevel();
				break;
			case AchieveDef::FIGHT_FORCE:
				$finish_num = EnUser::getUserObj($uid)->getFightForce();
				break;
			case AchieveDef::HERO_FORMATION:
				$finish_num = sizeof(EnFormation::getFormationObj($uid)->getFormation());
				break;
            case AchieveDef::FRIEND_FORMATION:
				$finish_num = count(EnFormation::getFormationObj($uid)->getExtra());
				break;
          //  case AchieveDef::DIVINE:
		  //      if(EnSwitch::isSwitchOpen(SwitchDef::DIVINE))
			//	    $finish_num = DivineObj::getInstance($uid)->getIntegral();
			//	break;
            case AchieveDef::GUILD_LEVEL:
                $guildInfo = isset($guildInfo) ? $guildInfo : EnGuild::getGuildInfo($uid);
				$finish_num = isset($guildInfo[GuildDef::GUILD_LEVEL]) ? $guildInfo[GuildDef::GUILD_LEVEL] : 0;
				break;
          //  case AchieveDef::GUILD_CONTRIBUTION:
         //       $memInfo = isset($memInfo) ? $memInfo : EnGuild::getMemberInfo($uid);
			//	$finish_num = empty($memInfo['contri_total']) ? 0 : $memInfo['contri_total'];
		//		break;
            case AchieveDef::CITY_CAPTURE:
                $memInfo = isset($memInfo) ? $memInfo : EnGuild::getMemberInfo($uid);
				$finish_num = empty($memInfo['city_id']) ? 0 : 1;
				break;
			case AchieveDef::HERO_COLOR:
				$finish_num = HeroLogic::getBestHeroQuality($uid);
				break;
            case AchieveDef::HERO_TYPES:
                $finish_num = HeroLogic::getHeroNumInBook($uid);
                break;
			case AchieveDef::MHERO_EVOLVE:
				$finish_num = EnUser::getUserObj($uid)->getHeroManager()->getMasterHeroObj()->getEvolveLv();
				break;
			case AchieveDef::HERO_EVOLVE:
				$finish_num = HeroLogic::getMaxHeroEvolveLv($uid);
				break;
            case AchieveDef::HERO_FAVOR:
                $finish_num = EnStar::getAllStarFavor($uid);
                break;
            case AchieveDef::HERO_LEVEL:
                $finish_num = HeroLogic::getMaxHeroLevel($uid);
                break;
			case AchieveDef::EQUIP_COLOR:
				$finish_num1 = HeroLogic::getBestEquipQualityOnHero($uid, HeroDef::EQUIP_ARMING);
                $bag = isset($bag) ? $bag : new Bag();
                $finish_num2 = $bag->getBestQuality(ItemDef::ITEM_TYPE_ARM);
                $finish_num = $finish_num1 > $finish_num2 ? $finish_num1 : $finish_num2;
				break;
			case AchieveDef::EQUIP_SUIT:
				$finish_num1 = HeroLogic::getBestEquipQualityOnHero($uid, HeroDef::EQUIP_TREASURE);
                $bag = isset($bag) ? $bag : new Bag();
                $finish_num2 = $bag->getBestQuality(ItemDef::ITEM_TYPE_TREASURE);
                $finish_num = $finish_num1 > $finish_num2 ? $finish_num1 : $finish_num2;
				break;
            case AchieveDef::EQUIP_TYPES:
                $finish_num = EnItemInfo::getArmBookNum($uid);
                break;
            case AchieveDef::EQUIP_SUIT_TYPES:
                $finish_num = EnItemInfo::getTreasBookNum($uid);
                break;
            case AchieveDef::FIGHT_SOUL_LEVEL:
                $bag = isset($bag) ? $bag : new Bag();
                $f1 = $bag->getBestLevel(ItemDef::ITEM_TYPE_FIGHTSOUL);
                $f2 = HeroLogic::getMaxHeroEquipLevel($uid, HeroDef::EQUIP_FIGHTSOUL);
                $finish_num = $f1 > $f2 ? $f1 : $f2;
                break;
            case AchieveDef::TREASURE_LEVEL:
                $bag = isset($bag) ? $bag : new Bag();
                $f1 = $bag->getBestLevel(ItemDef::ITEM_TYPE_TREASURE);
                $f2 = HeroLogic::getMaxHeroEquipLevel($uid, HeroDef::EQUIP_TREASURE);
                $finish_num = $f1 > $f2 ? $f1 : $f2;
                break;
            case AchieveDef::TREASURE_EVOLVE_LEVEL:
                $bag = isset($bag) ? $bag : new Bag();
                $finish_num = $bag->getBestEvolve();
                break;
            case AchieveDef::DRESS_NUM:
                $finish_num = 0;
                $set = HeroLogic::getAllEquipTmplIdOnHero($uid, HeroDef::EQUIP_DRESS);
                if(!empty($set))
                {
                    $finish_num = 1;
                }
                else
                {
                    $bag = isset($bag) ? $bag : new Bag();
                    $set = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_DRESS);
                    $finish_num = empty($set) ? 0 : 1;
                }
                break;
            case AchieveDef::ARM_REINFORE_LEVEL:
                $bag = isset($bag) ? $bag : new Bag();
                $f1 = $bag->getBestLevel(ItemDef::ITEM_TYPE_ARM);
                $f2 = HeroLogic::getMaxHeroEquipLevel($uid, HeroDef::EQUIP_ARMING);
                $finish_num = $f1 > $f2 ? $f1 : $f2;
                break;
			case AchieveDef::FIGHT_SOUL_TYPES:
				{
                    $set1 = HeroLogic::getAllEquipTmplIdOnHero($uid, HeroDef::EQUIP_FIGHTSOUL);
                    $bag = isset($bag) ? $bag : new Bag();
                    $set2 = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_FIGHTSOUL);
                    $set = array_unique($set1 + $set2);
				}
				break;
			case AchieveDef::PET_TYPES:
				{
                    $set = EnPet::getPetType($uid);
				}
				break;
			case AchieveDef::HERO_TYPES:
				{
					$set = HeroLogic::getAllHeroTmplId($uid);
				}
				break;

			case AchieveDef::EQUIP_TYPES:
				{
                    $set1 = HeroLogic::getAllEquipTmplIdOnHero($uid, HeroDef::EQUIP_ARMING);
                    $bag = isset($bag) ? $bag : new Bag();
                    $set2 = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_ARM);
                    $set = array_unique($set1 + $set2);
				}
				break;
			case AchieveDef::EQUIP_SUIT_TYPES:
                $set1 = HeroLogic::getAllEquipTmplIdOnHero($uid, HeroDef::EQUIP_TREASURE);
                $bag = isset($bag) ? $bag : new Bag();
                $set2 = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_TREASURE);
                $set = array_unique($set1 + $set2);
				break;
            //9月19号新加 by hanshijie
            case AchieveDef::DRAGON_POINT:
                if((EnSwitch::isSwitchOpen(SwitchDef::DRAGON) == false))
                {
                    $finish_num = 0;
                }
                else{
                    $dragonMap = DragonManager::getInstance($uid)->getMap();
                    $finish_num = $dragonMap['total_point'];
                }
                break;

		}
        **/
		
		switch($type)
		{
			case AchieveDef::PASS_ECOPY:
			case AchieveDef::GOLD_TREE:
            case AchieveDef::GUILD_COPY:
			case AchieveDef::SEIZE:
			case AchieveDef::ARENA:
			case AchieveDef::COMPETE:
			case AchieveDef::BOSS:
			case AchieveDef::BOSS_RANK:
            case AchieveDef::CITY_WAR_BATTLE:
            case AchieveDef::HERO_FRAG:
            case AchieveDef::ARM_REFRESH_NUM:
            case AchieveDef::FRIENDS_PLAYWITH_EACHOTHER:
            case AchieveDef::OLYMPIC_NORMAL:
            case AchieveDef::OLYMPIC_CHAMPION_NUM:
            case AchieveDef::ACTOR_LEARN_SKILL:
            case AchieveDef::ACTOR_INC_SKILL_LEV:
            case AchieveDef::ORANGE_CARD:
            case AchieveDef::BLUE_GOD_WEAPON_NUM:
            case AchieveDef::PURPLE_GOD_WEAPON_NUM:
				$this->initNone($type, $confs, $infos);
				break;
			case AchieveDef::NCOPY_STAR:
            case AchieveDef::ARENA_RANK:
            case AchieveDef::COMPETE_RANK:
			case AchieveDef::USER_LEVEL:
			case AchieveDef::DESTINY:
			case AchieveDef::FIGHT_SOUL:
			case AchieveDef::SILVER:
			case AchieveDef::PRESTIGE:
			case AchieveDef::JEWEL:
			case AchieveDef::FRIEND:
			case AchieveDef::TOWER:
			case AchieveDef::FIGHT_FORCE:
			case AchieveDef::HERO_FORMATION:
            case AchieveDef::FRIEND_FORMATION:
            case AchieveDef::DIVINE:
            case AchieveDef::GUILD_LEVEL:
            case AchieveDef::GUILD_CONTRIBUTION:
            case AchieveDef::CITY_CAPTURE:
			case AchieveDef::PET_SKILL:
			case AchieveDef::BOSS_RANK:
			case AchieveDef::HERO_COLOR:
			case AchieveDef::HERO_TYPES:
			case AchieveDef::MHERO_EVOLVE:
			case AchieveDef::HERO_EVOLVE:
            case AchieveDef::HERO_FAVOR:
            case AchieveDef::HERO_LEVEL:
			case AchieveDef::EQUIP_COLOR:
			case AchieveDef::EQUIP_SUIT:
			case AchieveDef::EQUIP_TYPES:
			case AchieveDef::EQUIP_SUIT_TYPES:
            case AchieveDef::FIGHT_SOUL_LEVEL:
            case AchieveDef::TREASURE_LEVEL:
            case AchieveDef::TREASURE_EVOLVE_LEVEL:
            case AchieveDef::DRESS_NUM:
            case AchieveDef::ARM_REINFORE_LEVEL:
            case AchieveDef::DRAGON_POINT:
            case AchieveDef::GOD_WEAPON_QUALITY:
				$this->initCommon($type, $confs, $infos, $finish_num);
				break;
			case AchieveDef::FIGHT_SOUL_TYPES:
			case AchieveDef::PET_TYPES:
            case AchieveDef::GOD_WEAPON_KIND:
				$this->initSet($type, $confs, $infos, $set);
				break;
			default:
				// initPassNCopy
				$typeName = AchieveDef::$ALL_TYPES[$type];
				$method = "init" . $typeName;
				$this->$method($type, $confs, $infos);
		}
		Logger::trace("AchieveObj.initType. after init uid:%d type:%d",	$uid, $type);
	}
	
	private function initNone($type, $confs, $infos)
	{
		$infos = isset($infos) ? $infos : array();
        foreach($confs as $id => $c)
        {
        	if(!isset($infos[$id]))
        	{
	            $infos[$id] = array(
	            		AchieveDef::VAR_AID => $id,
	            		AchieveDef::VAR_UID => $this->uid,
	                    AchieveDef::VAR_FINISH_NUM => 0,
	                    AchieveDef::VAR_STATUS =>  AchieveDef::STATUS_WAIT,
	            		AchieveDef::VAR_DATA => array(),
	            );
	            Logger::debug("AchieveObj. initCommon. modify:%s", $infos[$id]);
        	}
        }
		$this->setTypeData($type, $infos);
        $this->setTypeDataOfOriginData($type, $infos);
	}
	
	private function initCommon($type, $confs, $infos, $finish_num)
	{
		$infos = isset($infos) ? $infos : array();
        foreach($confs as $id => $c)
        {
            $need_num = $c[AchieveDef::VAR_FINISH_NUM];
            $d = isset($infos[$id]) ? $infos[$id] : null;
                
            if(empty($d) || ($d[AchieveDef::VAR_STATUS] == AchieveDef::STATUS_WAIT && $d[AchieveDef::VAR_FINISH_NUM] < $finish_num))
            {
                $infos[$id] = array(
                		AchieveDef::VAR_AID => $id,
                		AchieveDef::VAR_UID => $this->uid,
                		AchieveDef::VAR_DATA => array(),
                        AchieveDef::VAR_FINISH_NUM => $finish_num,
                        AchieveDef::VAR_STATUS =>
                        ($finish_num < $need_num ? AchieveDef::STATUS_WAIT : AchieveDef::STATUS_FINISH)
                );
                Logger::debug("AchieveObj. initCommon. modify:%s", $infos[$id]);
            }
		}
		$this->setTypeData($type, $infos);
        $this->setTypeDataOfOriginData($type, $infos);
	}

	public function initSet($type, $confs, $infos, $set)
	{
		$infos = isset($infos) ? $infos : array();
		$finish_num = count($set);
        foreach($confs as $id => $c)
        {
            $need_num = $c[AchieveDef::VAR_FINISH_NUM];
            $d = isset($infos[$id]) ? $infos[$id] : null;
                
            if(empty($d) || ($d[AchieveDef::VAR_STATUS] == AchieveDef::STATUS_WAIT && $d[AchieveDef::VAR_FINISH_NUM] < $finish_num))
            {
                $infos[$id] = array(
                	AchieveDef::VAR_AID => $id,
                	AchieveDef::VAR_UID => $this->uid,
                	AchieveDef::VAR_DATA => (isset($d) ? $d[AchieveDef::VAR_DATA] : $set),
                    AchieveDef::VAR_FINISH_NUM => $finish_num,
                    AchieveDef::VAR_STATUS => ($finish_num < $need_num ? AchieveDef::STATUS_WAIT :AchieveDef::STATUS_FINISH)
                );
                Logger::debug("AchieveObj. initCommon. modify:%s", $infos[$id]);
            }
        }
		$this->setTypeData($type, $infos);
        $this->setTypeDataOfOriginData($type, $infos);
	}
	
	function updateSet($type, $confs, $infos, $add)
	{
		foreach($confs as $id => $c)
		{
			$need_num = $c[AchieveDef::VAR_FINISH_NUM];
			$d = $infos[$id];
			if($d[AchieveDef::VAR_STATUS] == AchieveDef::STATUS_WAIT)
			{
				$set = $d[AchieveDef::VAR_DATA];
				if(in_array($add, $set))
				{
                    Logger::debug("uid:%s aid:%s type:%s set:%s exists:%s", $this->uid, $id, $type, $set, $add);
					continue;
				}
				$set[] = $add;			
				$finish_num = count($set);
				$infos[$id] = array(
						AchieveDef::VAR_AID => $id,
						AchieveDef::VAR_UID => $this->uid,
						AchieveDef::VAR_DATA => $set,
						AchieveDef::VAR_FINISH_NUM => $finish_num,
						AchieveDef::VAR_STATUS =>
							($finish_num < $need_num ? AchieveDef::STATUS_WAIT : AchieveDef::STATUS_FINISH)
				);
				Logger::debug("AchieveObj. updateSet:%s", $infos[$id]);
			}
		}
		$this->setTypeData($type, $infos);
	}
	
	/*
	   initRound 啥也没做,实际上它的功能已经直接由initNone代替
	public function initRound($type, $confs, $infos)
	{
		$infos = isset($infos) ? $infos : array();
		$finish_num = 0;
		foreach($confs as $id => $c)
		{
			$need_num = $c[AchieveDef::VAR_FINISH_NUM];
			$d = isset($infos[$id]) ? $infos[$id] : null;
		
			if(empty($infos[$id]))
			{
				$infos[$id] = array(
						AchieveDef::VAR_AID => $id,
						AchieveDef::VAR_UID => $this->uid,
						AchieveDef::VAR_DATA => array(AchieveDef::VAR_TIME => 0),
						AchieveDef::VAR_FINISH_NUM => $finish_num,
						AchieveDef::VAR_STATUS => AchieveDef::STATUS_WAIT
				);
				Logger::debug("AchieveObj. initRound. modify:%s", $infos[$id]);
			}
		}
		$this->setTypeData($type, $infos);
	}
	*/
	
	function updateRound($type, $confs, $infos, $timestamp)
	{
		foreach($confs as $id => $c)
		{
			$need_num = $c[AchieveDef::VAR_FINISH_NUM];
			$d = $infos[$id];
			if($d[AchieveDef::VAR_STATUS] == AchieveDef::STATUS_WAIT)
			{
				$lastTimestamp = isset($d[AchieveDef::VAR_DATA][AchieveDef::VAR_TIME])
					? $d[AchieveDef::VAR_DATA][AchieveDef::VAR_TIME] : 0;
				if($timestamp == $lastTimestamp)
				{
					Logger::debug("uid:$this->uid aid:$id type:$type timestamp:$timestamp repeat");
					continue;
				}
				$finish_num = $d[AchieveDef::VAR_FINISH_NUM] + 1;
				$infos[$id] = array(
						AchieveDef::VAR_AID => $id,
						AchieveDef::VAR_UID => $this->uid,
						AchieveDef::VAR_DATA => array(AchieveDef::VAR_TIME => $timestamp),
						AchieveDef::VAR_FINISH_NUM => $finish_num,
						AchieveDef::VAR_STATUS =>
						($finish_num < $need_num ? AchieveDef::STATUS_WAIT : AchieveDef::STATUS_FINISH)
				);
				Logger::debug("AchieveObj. updateRound:%s", $infos[$id]);
			}
		}
		$this->setTypeData($type, $infos);
	}
	
	public function updateType($type, $finish_type, $finish_num, $force = false)
	{
		if($this->uid != RPCContext::getInstance()->getUid() && !$force)
		{
			Logger::debug("AchieveObj.updateType. reforward uid:$this->uid, type:$type, finish_type:$finish_type finish_num:$finish_num");
			RPCContext::getInstance()->executeTask($this->uid, 'achieve.updateTypeByOther',
				array($this->uid, $type, $finish_type, $finish_num));
			return;
		}
		
		Logger::debug("type:%d finish_type:%d finish_num:%d", $type, $finish_type, $finish_num);
		$this->initType($type);
		$isAccuType = $this->isAccuType($type);
		$confs = self::getTypeConf($type);
		$infos = $this->getTypeData($type);
		$method = "updateType" . AchieveDef::$ALL_TYPES[$type];
		if(method_exists($this,	 $method))
		{
			Logger::debug("exist special method:$method, call it");
			$this->$method($type, $confs, $infos, $finish_type, $finish_num);
		}
		else if(in_array($type, AchieveDef::$ACCU_SET_TYPES))
		{
			Logger::debug("$type in ACCU_SET_TYPE, call updateSet");
			$this->updateSet($type, $confs, $infos, $finish_num);
		}
		else if(in_array($type, AchieveDef::$ACCU_ROUND_TYPES))
		{
			Logger::debug("$type in ACCU_ROUND_TYPE, call updateRound");
			$this->updateRound($type, $confs, $infos, $finish_num);
		}
		else 
		{
			foreach($confs as $id => $c)
			{
				$need_type = $c[AchieveDef::VAR_FINISH_TYPE];
				$need_num = $c[AchieveDef::VAR_FINISH_NUM];
				Logger::debug("id:%d finish_type:%d finish_num:%d",	$id, $need_type, $need_num);
				if($need_type != $finish_type) continue;
				$d = isset($infos[$id]) ? $infos[$id] : 
					 array(
						AchieveDef::VAR_AID => $id,
						AchieveDef::VAR_UID => $this->uid,
						AchieveDef::VAR_DATA => array(),
						AchieveDef::VAR_FINISH_NUM => $finish_num,
						AchieveDef::VAR_STATUS =>
						($finish_num < $need_num ? AchieveDef::STATUS_WAIT : AchieveDef::STATUS_FINISH)
				);
				if($d[AchieveDef::VAR_STATUS] >= AchieveDef::STATUS_FINISH) continue;
				$has_finish_num = $d[AchieveDef::VAR_FINISH_NUM];
				Logger::debug("has_finish_num:%d", $has_finish_num);
				if($isAccuType)
					$has_finish_num += $finish_num;
				else 
				{
					if($has_finish_num >= $finish_num) continue;
					$has_finish_num = $finish_num;
				}
				
				if($has_finish_num >= $need_num)
				{
					$has_finish_num = $need_num;
					$d[AchieveDef::VAR_STATUS] = AchieveDef::STATUS_FINISH;
				}
				$d[AchieveDef::VAR_FINISH_NUM] = $has_finish_num;
				$infos[$id] = $d;
				Logger::trace("Achieve.updateType. uid:%d type:%s finish_type:%d finish_num:%d status:%d",
					$this->uid, $type, $finish_type, $has_finish_num, $d[AchieveDef::VAR_STATUS]);
			}
			$this->setTypeData($type, $infos);
		}
	}

	// 通关普通副本 PassNCopy 101
	public function initPassNCopy($type, $conf, $infos)
	{
		$infos = isset($infos) ? $infos : array();
		
		$ncopyMan = MyNCopy::getInstance();
		$ncopys = $ncopyMan->getAllCopies();
        foreach($ncopys as $_ => $copy)
        {
            $copyId = $copy["copy_id"];
            if(!$ncopyMan->getCopyObj($copyId)->isCopyPassed())
                unset($ncopys[$copyId]);
        }
		
		foreach($conf as $id => $c)
		{
			$need_type = $c[AchieveDef::VAR_FINISH_TYPE];
			$finish_num = isset($ncopys[$need_type]) ? 1 : 0;
			$need_num = $c[AchieveDef::VAR_FINISH_NUM];
			if(empty($infos[$id]))
			{
				$infos[$id] = array(
					AchieveDef::VAR_AID => $id,
					AchieveDef::VAR_UID => $this->uid,
					AchieveDef::VAR_DATA => array(),
					AchieveDef::VAR_FINISH_NUM => $finish_num,
					AchieveDef::VAR_STATUS => ($finish_num < $need_num ? AchieveDef::STATUS_WAIT :AchieveDef::STATUS_FINISH));
			}
		}
		$this->setTypeData($type, $infos);
	}
	
	public function checkObtain($aid)
	{
		$conf = self::getConf();
		if(!isset($conf[AchieveDef::CONF_IDS][$aid]))
			return 'invalidid';
		$type = $conf[AchieveDef::CONF_IDS][$aid][AchieveDef::CONF_TYPE];
		$tc = self::getTypeData($type);
		if(empty($tc[$aid]) || $tc[$aid][AchieveDef::VAR_STATUS] == AchieveDef::STATUS_WAIT)
			return 'unfinished'; 
		if($tc[$aid][AchieveDef::VAR_STATUS] == AchieveDef::STATUS_OBTAINED)
			return 'obtained';
		return 'ok';
	}
	
	public function getConfReward($achieveId)
	{
		$conf = self::getConf();
		/*
		return array(
			array(1, 0, 100),
			array(2, 0, 200),
			array(3, 0, 300),
			array(4, 0, 100),
			array(5, 0, 100),
			array(6, 30012,10),
			array(7, 30012,10),
			array(8, 0, 10),
			array(9, 0, 10),
			array(10, 10008, 3),
			array(11, 0, 1000),
			array(12, 0, 2000),
			array(13, 10008, 10),
			array(14, 5025011, 7),
		);
		*/
		return $conf[AchieveDef::CONF_IDS][$achieveId][AchieveDef::CONF_REWARD];
	}
	
	public function  setStatus($achieveId, $status)
	{
		$conf = self::getConf();
		$type = $conf[AchieveDef::CONF_IDS][$achieveId][AchieveDef::CONF_TYPE];
		$tc = self::getTypeData($type);
		$tc[$achieveId][AchieveDef::VAR_STATUS] = $status;
		$this->setTypeData($type, $tc);
	}
	
	public function addReward($achieveId)
	{
		$user = Enuser::getUserObj($this->uid);
		$heroMan = EnUser::getUserObj()->getHeroManager();
		$bag = BagManager::getInstance()->getBag($this->uid);
		$level = $user->getLevel();
		foreach($this->getConfReward($achieveId) as $index => $reward)
		{
			list($rtype, $rid, $num) = $reward;
			switch($rtype)
			{
				case AchieveDef::REWARD_TYPE_SILVER : 
					$user->addSilver($num);
					break;
				case AchieveDef::REWARD_TYPE_HERO_SOUL :
					$user->addSoul($num);
					break;
				case AchieveDef::REWARD_TYPE_GOLD 	:
					$user->addGold($num, StatisticsDef::ST_FUNCKEY_ACHIEVE_REWARD);
					break;
				case AchieveDef::REWARD_TYPE_EXECUTE :
					$user->addExecution($num);
					break;
				case AchieveDef::REWARD_TYPE_STAMINA :
					$user->addStamina($num);
					break;
				case AchieveDef::REWARD_TYPE_ITEM 	:
				case AchieveDef::REWARD_TYPE_ITEMS :
					if(!$bag->addItemByTemplateID($rid, $num))
						throw new FakeException("uid:$this->uid, fail add item templateid:$rid num:$num");
					break;
				case AchieveDef::REWARD_TYPE_TREASURES :					
					EnFragseize::addTreaFrag($this->uid, array($rid => $num));
					break;
				case AchieveDef::REWARD_TYPE_LEVEL_SILVER :
					$user->addSilver($num * $level);
					break;
				case AchieveDef::REWARD_TYPE_LEVEL_HERO_SOUL :
					$user->addSoul($num * $level);
					break;
				case AchieveDef::REWARD_TYPE_HERO 	:
				case AchieveDef::REWARD_TYPE_HEROS :
					for($i = 0 ; $i < $num ; $i++)
						$heroMan->addNewHero($rid);
					break;
				case AchieveDef::REWARD_TYPE_JEWEL :
					$user->addJewel($num);
					break;
				case AchieveDef::REWARD_TYPE_PRESTIGE :
					$user->addPrestige($num);
					break;
				default:
					throw new FakeException("AchieveObj.addReward user:$this->uid unknown rtype:$rtype");	
			}
			Logger::trace("achieve.obtainReward uid:$this->uid achieveId:$achieveId reward[$index] = ($rtype, $rid, $num)");
		}
		$user->update();
		$bag->update();
	}
	
	public function obtainReward($achieveId)
	{
		$ret = $this->checkObtain($achieveId);
		if($ret != 'ok')
			return $ret;
		$this->setStatus($achieveId, AchieveDef::STATUS_OBTAINED);
		$this->addReward($achieveId);
		return 'ok';
	}
}

 
