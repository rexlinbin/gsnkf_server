<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Achieve.def.php 240420 2016-04-27 07:28:43Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Achieve.def.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-04-27 07:28:43 +0000 (Wed, 27 Apr 2016) $
 * @version $Revision: 240420 $
 * @brief 
 *  
 **/

class AchieveDef
{
	//名将成就
	const STAR_ALL_FAVOR		=	10;	//名将总星数
	
	
	// 成就系统

	const PASS_NCOPY = 101;
	const NCOPY_STAR = 102;
	const PASS_ECOPY = 103;
	const GOLD_TREE	 = 104;
	const GUILD_COPY = 105;
	const ARENA_RANK = 106;
	const COMPETE_RANK = 107;
	
	const USER_LEVEL = 201;
	const DESTINY 	 = 202;
	const FIGHT_SOUL = 203;
	const FIGHT_SOUL_TYPES = 204;
	const SILVER 	 = 205;
	const PRESTIGE 	 = 206;
	const JEWEL 	 = 207;
	const FRIEND 	 = 208;
	const SEIZE 	 = 209;
	const ARENA 	= 210;
	const COMPETE 	 = 211;
	const PET_TYPES  = 212;
	const PET_SKILL  = 213;
	const BOSS 		 = 214;
	const BOSS_RANK  = 215;
	const TOWER 	 = 216;
	const FIGHT_FORCE= 217;
	const HERO_FORMATION = 218;
	const FRIEND_FORMATION = 219;
	const DIVINE	= 220;
	const GUILD_LEVEL = 221;
	const GUILD_CONTRIBUTION = 222;
	const CITY_WAR_BATTLE = 223;
	const CITY_CAPTURE = 224;
    const FRIENDS_PLAYWITH_EACHOTHER = 225; //切磋次数
    const DRAGON_POINT = 226;   //寻龙积分
    const OLYMPIC_NORMAL   = 227;   //擂台赛最好成绩
    const OLYMPIC_CHAMPION_NUM  = 228;  //擂台赛冠军次数
    const ACTOR_LEARN_SKILL = 229;  //主角学技能
    const ACTOR_INC_SKILL_LEV = 230;   //主角技能等级

	const HERO_COLOR 	= 301;
	const HERO_TYPES 	= 302;
	const MHERO_EVOLVE = 303;
	const HERO_EVOLVE  = 304;
	const HERO_FAVOR	= 305;
	const HERO_LEVEL	= 306;
	const HERO_FRAG		= 307;
    const ORANGE_CARD = 308;

	const EQUIP_COLOR 	= 401;
	const EQUIP_SUIT 	= 402;
	const EQUIP_TYPES 	= 403;
	const EQUIP_SUIT_TYPES = 404;
	const FIGHT_SOUL_LEVEL = 405;
	const TREASURE_LEVEL = 406;
	const TREASURE_EVOLVE_LEVEL = 407;
	const DRESS_NUM			= 408;
	const ARM_REINFORE_LEVEL = 409;
	const ARM_REFRESH_NUM = 410;
    const GOD_WEAPON_KIND = 411;    //收集的神兵种类
    const GOD_WEAPON_QUALITY = 412; //收集的神兵品质
    const BLUE_GOD_WEAPON_NUM = 413; //蓝色神兵数量
    const PURPLE_GOD_WEAPON_NUM = 414;   //紫色神兵数量

	public static $ALL_TYPES = array(
			 self::PASS_NCOPY => 'PassNCopy',
			 self::NCOPY_STAR => 'NCopyScore',
			 self::PASS_ECOPY => 'PassECopy',
			 self::GOLD_TREE	 => 'GoldTree',
			 self::GUILD_COPY => 'GuildCopy',
			 self::ARENA_RANK => 'ArenaRank',
			 self::COMPETE_RANK => 'CompeteRank',			
			 self::USER_LEVEL => 'UserLevel',
			 self::DESTINY 	 => 'Destiny',
			 self::FIGHT_SOUL => 'FightSoul',
			 self::FIGHT_SOUL_TYPES => 'FightSoulTypes',
			 self::SILVER 	 => 'Silver',
			 self::PRESTIGE 	 => 'Prestige',
			 self::JEWEL 	 => 'Jewel',
			 self::FRIEND 	 => 'Friend',
			 self::SEIZE 	 => 'Seize',
			 self::ARENA 	=> 'Arena',
			 self::COMPETE 	 => 'Compete',
			 self::PET_TYPES  => 'PetTypes',
			 self::PET_SKILL  => 'PetSkill',
			 self::BOSS 		 => 'Boss',
			 self::BOSS_RANK  => 'BossRank',
			 self::TOWER 	 => 'Tower',
			 self::FIGHT_FORCE=> 'FightForce',
			 self::HERO_FORMATION => 'HeroFormation',
			 self::FRIEND_FORMATION => 'FriendFormation',
			 self::DIVINE	=> 'Divine',
			 self::GUILD_LEVEL => 'GuildLevel',
			 self::GUILD_CONTRIBUTION => 'GuildContribution',
			 self::CITY_WAR_BATTLE => 'CityWarBattle',
			 self::CITY_CAPTURE => 'CityCapture',	
			 self::HERO_COLOR 	=> 'HeroColor',
			 self::HERO_TYPES 	=> 'HeroTypes',
			 self::MHERO_EVOLVE => 'MHeroEvolve',
			 self::HERO_EVOLVE  => 'HeroEvolve',
			 self::HERO_FAVOR	=> 'HeroFavor',
			 self::HERO_LEVEL	=> 'HeroLevel',
			 self::HERO_FRAG		=> 'HeroFrag',
			 self::EQUIP_COLOR 	=> 'EquipColor',
			 self::EQUIP_SUIT 	=> 'EquipSuit',
			 self::EQUIP_TYPES 	=> 'EquipTypes',
			 self::EQUIP_SUIT_TYPES => 'EquipSuitTypes',
			 self::FIGHT_SOUL_LEVEL => 'FightSoulLevel',
			 self::TREASURE_LEVEL => 'TreasureLevel',
			 self::TREASURE_EVOLVE_LEVEL => 'TreasureEvolveLevel',
			 self::DRESS_NUM			=> 'DressNum',
			 self::ARM_REINFORE_LEVEL => 'ArmReinforceLevel',
			 self::ARM_REFRESH_NUM => 'ArmRefreshNum',
             //9月18号新加 by hanshijie
             self::FRIENDS_PLAYWITH_EACHOTHER => 'FriendsPlayWithEachOther',
             self::DRAGON_POINT => 'DragonPoint',
             self::OLYMPIC_NORMAL  => 'OlympicNormal',
             self::OLYMPIC_CHAMPION_NUM => 'OlympicChampionNum',
             self::ACTOR_LEARN_SKILL    => 'ActorLearnSkill',
             self::ACTOR_INC_SKILL_LEV  => 'ActorIncSkillLev',
             self::ORANGE_CARD  => 'OrangeCard',
             self::GOD_WEAPON_KIND => 'GodWeaponNum',
             self::GOD_WEAPON_QUALITY => 'GodWeaponQuality',
             self::BLUE_GOD_WEAPON_NUM => 'BlueGodWeaponNum',
             self::PURPLE_GOD_WEAPON_NUM => 'PurpleGodWeaponNum',
	);
	
	// 累积型成就 调用时候传参数是累加值 -- 普通类型传绝对值
	public static $ACCU_TYPES = array(
			self::PASS_NCOPY ,
			self::PASS_ECOPY ,
			self::GUILD_COPY ,
			self::FIGHT_SOUL_TYPES ,
			self::SEIZE  ,
			self::ARENA ,
			self::COMPETE ,
			self::PET_TYPES ,
			self::BOSS ,
			self::CITY_WAR_BATTLE ,
			self::CITY_CAPTURE ,
			//self::HERO_TYPES ,
			self::HERO_FRAG ,
			//self::EQUIP_TYPES ,
			//self::EQUIP_SUIT_TYPES ,
			self::DRESS_NUM ,
			self::ARM_REFRESH_NUM ,
            self::FRIENDS_PLAYWITH_EACHOTHER,
            self::ORANGE_CARD,
            self::BLUE_GOD_WEAPON_NUM,
            self::PURPLE_GOD_WEAPON_NUM,
	);

    //收集型
	public static $ACCU_SET_TYPES = array(
		self::FIGHT_SOUL_TYPES,
		self::PET_TYPES,
        self::GOD_WEAPON_KIND,
	);

    //次数累积型 一段时间内算一次
	public static $ACCU_ROUND_TYPES = array(
		self::BOSS,
		self::CITY_WAR_BATTLE,
	);

    //排名型
	public static $DESC_TYPES = array(
			self::BOSS_RANK,
			self::ARENA_RANK,
			self::COMPETE_RANK,
            self::OLYMPIC_NORMAL,
	);
	
	const STATUS_WAIT = 0;
	const STATUS_FINISH = 1;
	const STATUS_OBTAINED = 2;
	
	const VAR_UID = 'uid';
	const VAR_AID = 'aid';
	const VAR_TYPE = 'type';
	const VAR_FINISH_NUM = 'finish_num';
	const VAR_FINISH_TYPE= 'finish_type';
	const VAR_STATUS = 'status';
	const VAR_SETS 	= 'sets';
	const VAR_TYPES = 'types';
	const VAR_DATA = 'va_data';
	const VAR_TIME = 'time';
	
	// 配置字段
	const CONF_ID = 'id';
	const CONF_MTYPE = 'mtype';
	const CONF_TYPE = self::VAR_TYPE;
	const CONF_TNAME = 'tname';
	const CONF_FINISH_TYPE = self::VAR_FINISH_TYPE;
	const CONF_FINISH_NUM = self::VAR_FINISH_NUM;
	const CONF_REWARD = 'rewards';
	
	const CONF_TYPES = self::VAR_TYPES;
	const CONF_IDS = 'ids';
	
	const MAX_BOSS_RANK = 100000000;
	
	const REWARD_TYPE_SILVER = 1;
	const REWARD_TYPE_HERO_SOUL = 2;
	const REWARD_TYPE_GOLD 	= 3;
	const REWARD_TYPE_EXECUTE = 4;
	const REWARD_TYPE_STAMINA = 5;
	const REWARD_TYPE_ITEM 	= 6;
	const REWARD_TYPE_ITEMS = 7;
	const REWARD_TYPE_LEVEL_SILVER = 8;
	const REWARD_TYPE_LEVEL_HERO_SOUL = 9;
	const REWARD_TYPE_HERO 	= 10;
	const REWARD_TYPE_JEWEL = 11;
	const REWARD_TYPE_PRESTIGE = 12;
	const REWARD_TYPE_HEROS = 13;
	const REWARD_TYPE_TREASURES = 14;
	
	const KEY_ACHIEVE = 'achieve.user';
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
