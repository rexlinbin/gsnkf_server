<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Hero.def.php 251365 2016-07-13 05:36:38Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Hero.def.php $
 * @author $Author: QingYao $(lanhongyu@babeltime.com)
 * @date $Date: 2016-07-13 05:36:38 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251365 $
 * @brief
 *
 **/

class HeroDef
{
    
	//在session中存放所有阵型上的武将数据
	const SESSION_KEY_SQUAD = 'hero.squad';
	
	//武将总数
	const SESSION_KEY_ALL_NUM = 'hero.allNum';
	
     /**
     * 英雄表字段
     */
    public static $HERO_FIELDS = array(
    	'hid',
        'htid',
    	'uid',    		
    	'soul',    		  
        'level',
    	'destiny',
    	'evolve_level',
    	'upgrade_time',
    	'delete_time',    	
    	'va_hero',
        );

	/**
	 * 装备： 武装
	 */
	const EQUIP_ARMING = 'arming';
	
	/**
	 * 装备：技能书
	 */
	const EQUIP_SKILL_BOOK = 'skillBook';
	
	/**
	 * 装备：宝物
	 */
	const EQUIP_TREASURE = 'treasure';
	
	/**
	 * 装备：时装
	 */
	const EQUIP_DRESS = 'dress';
	
	/**
	 * 装备：战魂
	 */
	const EQUIP_FIGHTSOUL = 'fightSoul';

	/**
	 * 装备：神兵
	 */
	const EQUIP_GODWEAPON = 'godWeapon';
	
	/**
	 * 锦囊
	 */
	const EQUIP_POCKET = 'pocket';
	
	/**
	 * 兵符
	 */
	const EQUIP_TALLY = 'tally';
	
	/**
	 * 战车
	 */
	const EQUIP_CHARIOT = 'chariot';
	
	/**
	 * 装备：所有装备
	 */
	const EQUIP_ALL = 'all';
	
	const VA_FIELD_LOCK = 'lock';
	
	const VA_FIELD_TALENT = 'talent';
	const VA_SUBFIELD_TALENT_CONFIRMED = 'confirmed';
	const VA_SUBFIELD_TALENT_TO_CONFIRM = 'to_confirm';
	const VA_SUBFIELD_TALENT_SEALED = 'sealed';

    const VA_FIELD_PILL = 'pill';
	const VA_FIELD_TRANSFER = 'transfer';
	const VA_FIELD_DXTRANS = 'dxtrans';
    const VA_FIELD_MATER_TALENT = 'masterTalent';   //主角天赋
	
	//卸下装备时 将卸下的装备加入背包     需要判断相应的背包是否满
	static $EQUIPTYPE_TO_BAGNAME = array(
	        self::EQUIP_ARMING => BagDef::BAG_ARM,
	        self::EQUIP_TREASURE => BagDef::BAG_TREAS,
	        self::EQUIP_DRESS => BagDef::BAG_DRESS,
	        self::EQUIP_FIGHTSOUL => BagDef::BAG_FIGHT_SOUL,
	        self::EQUIP_GODWEAPON => BagDef::BAG_GOD_WEAPON,
	        self::EQUIP_POCKET => BagDef::BAG_POCKET,
			self::EQUIP_TALLY => BagDef::BAG_TALLY,
	        );
	
	//已经装备时 需要告诉背包模块   从背包中拉取最好的哪种装备
	static $EQUIPTYPE_TO_ITEMTYPE = array(
	        self::EQUIP_ARMING => ItemDef::ITEM_TYPE_ARM,
	        self::EQUIP_SKILL_BOOK => ItemDef::ITEM_TYPE_BOOK,
	        self::EQUIP_TREASURE => ItemDef::ITEM_TYPE_TREASURE,
	        self::EQUIP_DRESS => ItemDef::ITEM_TYPE_DRESS,
	        self::EQUIP_FIGHTSOUL => ItemDef::ITEM_TYPE_FIGHTSOUL,
	        self::EQUIP_GODWEAPON => ItemDef::ITEM_TYPE_GODWEAPON,
	        self::EQUIP_POCKET => ItemDef::ITEM_TYPE_POCKET,
			self::EQUIP_TALLY => ItemDef::ITEM_TYPE_TALLY,
	        );
	
	//时装暂时只有一个栏位
	static $VALID_DRESS_POS = array(1);
	/**
	 * 装备：所有（武器，技能书，宝物）
	 */
	static $ALL_EQUIP_TYPE = array(
	        self::EQUIP_ARMING,
	        self::EQUIP_SKILL_BOOK,
	        self::EQUIP_TREASURE,
	        self::EQUIP_DRESS,
	        self::EQUIP_FIGHTSOUL,
	        self::EQUIP_GODWEAPON,
	        self::EQUIP_POCKET,
			self::EQUIP_TALLY,
			self::EQUIP_CHARIOT,
	        );
	
	/**
	 * 技能书开启的三个条件
	 */
	const SKILL_BOOK_NEED_LEVEL		=	0;
	const SKILL_BOOK_NEED_EVOLVELV	=	1;
	const SKILL_BOOK_NEED_ITEM		=	2;
	const INIT_HERO_LIMIT_NUM    =    100;
	const INIT_HERO_GRID_NEED_GOLD = 5;
	const PRE_HERO_LIMIT_ADD    =    5;//一次开启5个格子
	const PRE_HERO_LIMIT_NEED_GOLD    =    5;//一个格子需要5个金币
	
	
	//各个属性加成
	const ADD_ATTR_BY_BASELV   =    'addAttrByBaseLv';
	const ADD_ATTR_BY_EQUIP    =    'addAttrByEquip';
	const ADD_ATTR_BY_AWAIK    =    'addAttrByAwaik';
	const ADD_ATTR_BY_STAR     =    'addAttrByStar';
	const ADD_ATTR_BY_DESTINY  =    'addAttrByDestiny';
	const ADD_ATTR_BY_TALENT   =    'addAttrByTalent';
	const ADD_ATTR_BY_CRAFT     =    'addAttrByCraft'; 
	const ADD_ATTR_BY_UNIONPROFIT	=   'addAttrByUnion';
	const ADD_ATTR_BY_ATTR_EXTRA	= 	'addAttrByAttrExtra';
	const ADD_ATTR_BY_ATTR_EXTRA_POS	= 	'addAttrByAttrExtraPos';
	const ADD_ATTR_BY_ATHENA = 'addAttrByAthena';
	const ADD_ATTR_BY_AWAIK_FOR_FMT = 'addAttrByAwaikForFmt';//区别于addAttrByAwaik给通过觉醒给自己加成，这个是通过觉醒给别人加成
    const ADD_ATTR_BY_MASTER_TALENT = 'addAttrByMasterTalent';
    const ADD_ATTR_BY_STYLISH = 'addAttrByStylish';//称号提供的加成
    const ADD_ATTR_BY_HERO_DESTINY = 'addAttrByHeroDestiny';//武将天命提供的加成
	
	//需要做缓存的属性加成  所有武将的加成都一样
	const ADD_ATTR_BY_PET      		=	'addAttrByPet';
	const ADD_ATTR_BY_ACHIEVE  		=	'addAttrByAchieve';
	const ADD_ATTR_BY_DRESSROOM     =   'addAttrByDressRoom';
	const ADD_ATTR_BY_GODWEAPONBOOK =   'addAttrByGodWeaponBook';
	const ADD_ATTR_BY_TALLYBOOK 	=   'addAttrByTallyBook';
	const ADD_ATTR_BY_GUILDSKILL 	=   'addAttrByGuildSkill';
    const ADD_ATTR_BY_PILL          =   'addAttrByPill';
    const ADD_ATTR_BY_UNIONEXTRA = 'addAttrByExtraUnion';
    const ADD_ATTR_BY_CHARIOT     ='addAttrByChariot';
		
	//天命星座是否可突破主角，转换形象
	const CONSTELLATION_CAN_TRANSFORM = 1;
	const CONSTELLATION_CAN_NOT_TRANSFORM = 2;
	
	
	const INVALID_EQUIP_POSITION = 0;
	
	const DEVELOP_NEED_EVOLVELV = 7;
	
	const OPEN_HEROGRID_TYPE_GOLD = 1;
	const OPEN_HEROGRID_TYPE_ITEM = 2;
};

class HeroBookDef
{
    const SESSION_NAME = 'hero.book';
    
}

class REMOVE_SB_NEED_TYPE
{
    const SILVER = 0;
    const GOLD = 1;
    const ITEM = 2;
}

class AWAKEABILITY_GROW_TYPE
{
    const GROW_BY_LEVEL = 1;
    const GROW_BY_EVOLVELV = 2;
}

class HERO_QUALITY
{
    const GRAY_HERO_QUALITY = 1;//灰色武将
    const WHITE_HERO_QUALITY = 2;//白色武将
    const GREEN_HERO_QUALITY = 3;//绿色武将
    const BLUE_HERO_QUALITY = 4;//蓝色武将
    const PURPLE_HERO_QUALITY = 5;//紫色武将
    const ORANGE_HERO_QUALITY = 6;//橙色武将
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */