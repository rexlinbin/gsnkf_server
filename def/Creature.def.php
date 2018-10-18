<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Creature.def.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Creature.def.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : 
 * Description : 配置表属性定义
 * Inherit     : 
 **********************************************************************************************************************/


class CreatureAttr
{
			
/****************************************************
 *  配置表所有属性及对应表中的字段位置 TODO：下面的数组需要整理一下
 *****************************************************/	
    const HTID  						=  0;//英雄模版ID
    const NAME 							=  1;//英雄名称
    const LEVEL 						=  2;//英雄基础等级
    const EXP_ID  						=  3;//经验表ID
    const VOCATION  					=  4;//英雄职业
    const RAGE_GET_BASE 				=  5;//怒气获得基础值
    const RAGE_GET_AMEND 				=  6;//怒气获得修正值
    const RAGE_GET_RATIO 				=  7;// 怒气获得倍率
    const PARRY_SKILL 					=  8;//格挡技能id
    const CHARM_SKILL 					=  9;//魅惑技能id
    const CHAOS_SKILL 					=  10;//混乱技能id
    const DODGE_SKILL 					=  11;//闪避技能
    const DEATH_SKILL					=  12;//死亡后施放技能
    const ROUND_BEGIN_SKILL				=  13;//行动前施放技能
    const ROUND_END_SKILL				=  14;//行动后施放技能
    const ARR_IMMUNED_BUFF 				=  15;//状态效果ID
    const ARR_IMMUNED_SKILL_TYPE		=  16;
    const ARR_IMMUNED_TARGET_TYPE		=  17;
    const ATTACK_SKILL 					=  18;//默认普通技能
    const RANDOM_ATTACK_SKILL			=  19;//随机普通技能
    const RAGE_SKILL 					=  20;//怒气攻击技能
    const SKILLBOOK_POS_OPEN_NEED		=  21;//heroes表中的技能书栏位开启条件
    const PRICE 						=  22;//招募价格
    const HP							=  23;//英雄基础生命
    const RAGE							=  24;//英雄基础怒气
    const REIGN_INIT					=  25;//英雄基础统帅
    const STRENGTH_INIT					=  26;//英雄基础力量
    const INTELLIGENCE_INIT 			=  27;//英雄基础智慧
    const PHYSIC_ATK_INIT				=  28;//英雄基础物理攻击
    const MAGIC_ATK_INIT 				=  29;//英雄基础魔法攻击
    const PHYSIC_DEF_INIT				=  30;//英雄基础物理防御
    const MAGIC_DEF_INIT 				=  31;//英雄基础魔法防御
    const PHYSICAL_ATK_RATIO_INIT 		=  32;//英雄固定物理伤害倍率
    const MAGIC_ATK_RATIO_INIT 			=  33;//英雄固定魔法伤害倍率
    const PHYSICAL_IGNORE_RATIO_INIT 	=  34;//英雄固定物理免伤倍率
    const MAGIC_IGNORE_RATIO_INIT 		=  35;//英雄固定魔法免伤倍率
    const ABSOLUTE_ATTACK 				=  36;//英雄基础最终伤害
    const ABSOLUTE_DEFEND 				=  37;//英雄基础最终免伤
    const HP_INC 						=  38;//英雄生命成长
    const PHYSIC_ATK_INC				=  39;//英雄物理攻击成长
    const MAGIC_ATK_INC 				=  40;//英雄魔法攻击成长
    const PHYSIC_DEF_INC 				=  41;//英雄物理防御成长
    const MAGIC_DEF_INC					=  42;//英雄魔法防御成长
    const BOOK_SKILL					=  43;//monster的技能书技能数组
    const STAR_LEVEL 					=  44;//英雄星级
    const QUALITY 						=  45;//英雄品质
    const BASE_HTID						=  46;//英雄原型ID
    const COUNTRY						=  47;//所属国家
    const FATAL_INIT					=  48;//英雄基础暴击率
    const FATAL_RATIO				    =  49;//暴击伤害倍数
    const HIT_INIT 						=  50;//英雄基础命中
    const DODGE_INIT					=  51;//英雄基础闪避
    const PARRY_INIT 					=  52;//英雄基础格挡率
    const AWAKE_ABILITY_INIT			=  53;	//初始能力
    const AWAKE_ABILITY_GROW			=  54; //英雄觉醒能力成长
    const CAN_BE_RESOLVED				=  55; //能否被分解
    const SOUL							=  56;//分解获得的初始将魂
    const UNION_PROFIT 					=  57;//连携加成组 id 属性  数值（10组）
    const CANBE_STAR					=  58;//能否转化为名将
    const STAR_ID						=  59;//名将ID
    const STAR_EXPID					=  60;//名将经验表ID
    const MAX_ENFORCE_LV				=  61;//英雄强化等级上限
    const EVOLVE_TBLID					=  62;//进化经验表ID
    const LVLUP_RATIIO					=  63;//强化系数
    const DROP_HERO_PRB                 =  64;//卡牌掉落概率
    const EVOLVE_BASE_RATIO             =  65;//进阶基础值系数
    const EVOLVE_INIT_LEVEL             =  66;//进阶初始等级
    const EVOLVE_GAP_LEVEL              =  67;//进阶间隔等级
    const GENDER                        =  68;//性别
    const GENERAL_ATTACK_INIT           =  69;//通关攻击基础值
    const GENERAL_ATTACK_INC            =  70;//通用攻击成长
//     const ROLE_TRANSFER_TBL             =  71;//主角晋阶表ID
//     const TRANSFER_TBL                  =  72;//进化表ID
    const ENFORCE_GAP_LEVEL             =  73;//强化间隔等级
    const JEWEL_NUM                     =  74;//分解获得魂玉
    const REBORN_GOLD_BASE              =  75;//武将重生消耗金币基础值
    const EVOLVE_LEVEL                  =  76;//monster进阶次数  
    const TALENT_ACTIVATE_NEED          =  77;//武将天赋消耗
    const TALENT_ARR_COPY              =  78;//武将列传副本
    const TALENT_GROUP_LIST_1           =  79;//武将可洗练天赋ID组1
    const TALENT_GROUP_WEIGHT_1           =  80;//洗练天赋权重组1
    const TALENT_GROUP_LIST_2             =  81;//武将可洗练天赋ID组2
    const TALENT_GROUP_WEIGHT_2           =  82;//洗练天赋权重组2
    const TALENT_GROUP_LIST_3             =  83;//武将可洗练天赋ID组3
    const TALENT_GROUP_WEIGHT_3           =  84;//洗练天赋权重组3
    const TALENT_GROUP_LIST_4             =  85;//武将可洗练天赋ID组4
    const TALENT_GROUP_WEIGHT_4           =  86;//洗练天赋权重组4
    const TALENT_MAX_STAR                 =  87;//天赋洗练星级上限组
    const DEVELOP_TBL_ID                  =  88;//橙卡进化表ID
    const QUALIFICATION                   =  89;//武将资质
    const UNDEVELOP_TBL_ID                =  90;//橙卡重生需要的进化表
    const UNDEVELOP_NEED_EXTRA_GOLD       =  91;//武将重生附加消耗金币
    const GODWEAPON_UNITPROFIG            =  92;//神兵连携
    const FATE_ATTR						  =  93;//武将缘分堂属性
    const LOYAL_ATTR					  =  94;//武将忠义堂属性
    const RESOLVE_2_SOUL_FRAG_INFO		  =  95;//武将化魂后的碎片模板id
    const DESTINY_SUM					  =  96;//总天命数
    const DESTINY_AWAKE					  =  97;//天命觉醒能力
    const DESTINY_COST					  =  98;//特定ID额外消耗
    const LAUGH_SKILL 				      =  99;//嘲讽技能
    const BIG_ROUND_BEGIN_SKILL			  =  100;//大回合行动前施放技能
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */