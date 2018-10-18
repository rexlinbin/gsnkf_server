<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeapon.def.php 181660 2015-06-30 09:34:19Z MingTian $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/GodWeapon.def.php $$
 * @author $$Author: MingTian $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-06-30 09:34:19 +0000 (Tue, 30 Jun 2015) $$
 * @version $$Revision: 181660 $$
 * @brief 
 *  
 **/
class GodWeaponDef
{
    //item_godarm.csv
    const ITEM_ATTR_NAME_GOD_WEAPON_TYPE = 'godWeaponType';//神兵类型
    const ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM = 'originalEvolveNum';//神兵初始进化次数
    const ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_QUALITY = 'evolveQuality';//神兵进化次数对应品质阶别
    const ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_REINFORCE_LEVEL_LIMIT = 'reinForceLevelLimit';//进化次数对应强化等级上限
    const ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID = 'evolveId';//神兵进化表ID组
    const ITEM_ATTR_NAME_GOD_WEAPON_REINFORCE_EXP_ID = 'reinForceExpId';//强化升级经验表ID
    const ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP = 'giveExp';//提供经验值
    const ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID1 = 'baseAbilityId1';//基础属性ID组1
    const ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID2 = 'baseAbilityId2';//基础属性ID组2
    const ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID3 = 'baseAbilityId3';//基础属性ID组3
    const ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID4 = 'baseAbilityId4';//基础属性ID组4
    const ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID1 = 'growAbilityId1';//成长属性ID组1
    const ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID2 = 'growAbilityId2';//成长属性ID组2
    const ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID3 = 'growAbilityId3';//成长属性ID组3
    const ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID4 = 'growAbilityId4';//成长属性ID组4
    //神兵洗练相关
    const ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY = 'awakeOpenQuality';//神兵觉醒开启需要品质组
    const ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST = 'normalWashCost';//普通神兵洗练消耗
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY1 = 'washAbility1';//神兵觉醒1洗练属性ID
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT1 = 'washWeight1';//神兵觉醒1洗练权重组
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY2 = 'washAbility2';//神兵觉醒2洗练属性ID
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT2 = 'washWeight2';//神兵觉醒2洗练权重组
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY3 = 'washAbility3';//神兵觉醒3洗练属性ID
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT3 = 'washWeight3';//神兵觉醒3洗练权重组
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY4 = 'washAbility4';//神兵觉醒4洗练属性ID
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT4 = 'washWeight4';//神兵觉醒4洗练权重组
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY5 = 'washAbility5';//神兵觉醒5洗练属性ID
    const ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT5 = 'washWeight5';//神兵觉醒5洗练权重组

    const ITEM_ATTR_NAME_GOD_WEAPON_FRIEND_ID = 'godFriendId';//神兵羁绊ID组
    const ITEM_ATTR_NAME_GOD_WEAPON_RESOLVE_ID = 'resolveId';//炼化获得物品ID组
    const ITEM_ATTR_NAME_GOD_WEAPON_OPEN_EFFECT_EVOLVE_LEVEL = 'openEffectEvolveLevel';//开启特效进化等级
    const ITEM_ATTR_NAME_GOD_WEAPON_CONSUME_RATIO = 'consumeRatio';//吞噬经验消耗银币系数
    const ITEM_ATTR_NAME_GOD_WEAPON_INIT_REINFORCE_LEVEL = 'initReinForceLv';//初始强化等级
    const ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP = 'isGodExp';//是否是经验物品
    const ITEM_ATTR_NAME_GOD_WEAPON_REBORN_COST = 'rebornCost';//重生花费
    const ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_LIMIT = 'evolveLimit';//进化次数限制 (这个不是对应csv的字段， 是自己为了取数据方便添加的)
    const ITEM_ATTR_NAME_FRIEND_OPEN = 'friendOpen';//羁绊生效进化次数要求
    //神兵录相关
    const ITEM_ATTR_NAME_GOLD_WASH_COST = 'goldWashCost';//金币洗练花费
    const ITEM_ATTR_NAME_DICT_EXTRA_ABILITY = 'extraAbility';//激活神兵录额外属性
    const ITEM_ATTR_NAME_SCORE = 'score';//神兵品级
    const ITEM_ATTR_NAME_IS_STRENGTHEN = 'isStrengthen';//是否可强化

    const NEED_ITEM = 'needItem';//神兵洗练消耗(不对应csv字段)
    const NEED_GOLD = 'needGold';//神兵消耗金币(不对应csv字段)
    const NEED_SILVER = 'needSilver';//神兵消耗银币(不对应csv字段)

    static $arrBaseAbility = array(
        self::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID1,
        self::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID2,
        self::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID3,
        self::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID4,
    );

    static $arrGropAbility = array(
        self::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID1,
        self::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID2,
        self::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID3,
        self::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID4,
    );

    //item_godarm_fragment.csv
    const ITEM_ATTR_NAME_GOD_WEAPON_FRAG_NEED_FRAG_NUM = 'needFragNum';//所需碎片数量
    const ITEM_ATTR_NAME_GOD_WEAPON_FRAG_AIM_ID = 'aimId';//合成神兵ID

    //godarm_transfer.csv
    const GOD_WEAPON_TRANSFER_RESOLVE_ID = 'resolveID';//神兵进化表ID
    const GOD_WEAPON_TRANSFER_NEED_RESOLVE_GOD_LEVEL = 'NeedResolveGodLevel';//进化需要神兵等级
    const GOD_WEAPON_TRANSFER_NEED_ACTOR_LV = 'needActorLv';//进化需要主角等级
    const GOD_WEAPON_TRANSFER_COST_SILVER = 'costSilver';//进化消耗银币
    const GOD_WEAPON_TRANSFER_COST_GOD_AMY = 'costGodAmy';//进化消耗神兵
    const GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID = 'resolveItemId';//进化消耗物品ID组

    //godarm_affix.csv 神兵洗练属性表
    const GOD_WEAPON_WASH_ID = 'id';//神兵属性ID
    const GOD_WEAPON_WASH_ATTR = 'attrIds';//对应属性
    const GOD_WEAPON_WASH_WEIGHT = 'weight';//对应权重

    //godWeaponDef
    const REINFORCE_LEVEL = 'reinForceLevel';//强化等级
    const REINFORCE_COST = 'reinForceCost';//强化费用银币
    const REINFORCE_EXP = 'reinForceExp';//强化经验
    const EVOLVE_NUM = 'evolveNum';//进化次数
    const CONFIREMED = 'confirmed';//确认过的洗练属性
    const TOCONFIRM = 'toConfirm';//等待确认的
    const BATCHTOCONFIRM = 'btc'; //批量洗练出等待确认的

    const INIT_REINFORCE_LEVEL = 0;
    const INIT_EVOLVE_NUM = 0;
    const BATCH_WASH_NUM = 10;

    const REBORN_RETURN_ITEM_ID = 600001;    //(炼化存经验专用物品）完整神兵石

    const CAN_STRENGTHEN = 1;
    const CAN_NOT_STRENGTHEN = 0;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */