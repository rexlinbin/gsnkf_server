<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Switch.def.php 255094 2016-08-08 09:50:28Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Switch.def.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-08 09:50:28 +0000 (Mon, 08 Aug 2016) $
 * @version $Revision: 255094 $
 * @brief
 *
 **/

class SwitchDef
{
    /**
     * 
     * 阵容1
强化所2
商店3
精英副本4
活动5
名将6
比武7
竞技场8
活动副本9
宠物10
资源矿11
占星12
签到13
等级礼包14
铁匠铺15
装备强化16
武将强化17
武将转生18
                        
                    默认开启：
                    好友
                    签到系统
     */
	const SQUAD    =    1;//阵型
	const HEROFORGE=    2;//强化所
	const SHOP     =    3;//商店
	const ELITECOPY =   4;//精英副本//在getEliteCopyInfo接口加的
	const ACTIVITY  =   5;//活动
	const STAR      =   6;//名将
	const ROB       =   7;//比武
	const ARENA     =   8;//竞技场
	const ACTCOPY   =   9;//活动副本//在getCopyList接口加的
	const PET       =   10;//宠物
	const MINERAL   =   11;//资源矿//在getPitsByDomain接口加的
	const DIVINE    =   12;//占星
	const SIGN      =   13;//签到
	const LEVELGIFT =   14;//等级礼包
	const FORGE     =   15;//铁匠铺
	const ITEMENFORCE = 16;//装备强化
	const HEROENFORCE = 17;//武将强化
	const HEROEVOLVE = 18;//武将进化 
	const TREASUREENFORCE = 19;//宝物强化
	const ROBTREASURE = 20;//夺宝
	const REFINEFURNACE = 21;//炼化炉
	const DESTINY = 22;//天命系统
	const GUILD = 23;//军团系统
	const ARMREFRESH = 24;//装备洗练
	const TREASUREEVOLVE = 25;//宝物突破
	const TOWER = 26;//爬塔系统
	const BOSS = 27;//世界boss
	const FIGHTSOUL = 28; //战魂系统
	const DRESS = 29;    //时装系统
	const ACTIVE = 30; //每日任务
	const ENFORCEDRESS = 31; //时装强化
	const HEROCOPY = 32;//武将列传
	const DRAGON = 33;//寻龙探宝
	const OLYMPIC = 34;//擂台赛
	const MASTERSKILL = 35;//主角技能
	const HEROTRANSFER = 36;//武将变身
	const HERODEVELOP = 37;//武将进化 橙卡
    const WEEKENDSHOP = 38;//周末商人
    const MONTHLYSIGN = 39;//月签到
    const WARCRAFT = 40;//阵法
    const RETRIEVE = 41;//资源追回
    const PASS = 42;//关斩将副本
    const ATTREXTRA = 43;//助战军
    const EXPUSER = 44;//经验副本
    const ATHENA = 45;//主角星魂
    const MOON = 46;//水月之境
    const WORLDPASS = 47;//跨服闯关大赛
    const PILL = 48;//丹药
    const UNION = 49;//聚义堂
    const POCKET = 50;//锦囊
    const HERODEVELOP_2_RED = 51;//武将进化红卡
    const WORLDCOMPETE = 52;//跨服比武
    const ARMDEVELOP_2_RED = 53;//红装进阶
    const TALLY = 54;//兵符
    const PETEVOLVE = 55;//宠物进阶
    const CHARGEDART = 56;//木牛流马
    const STYLISH = 57;//称号系统
    const CHARIOT = 58;//军工坊系统（战车）
    const SEVENSLOTTERY = 59;//七星台（精华招募）
}

class BtTblSwitchField
{
    const ID    =    'id';
    const OPENLV    =    'openLv';
    const OPEN_NEED_BASE    =    'openNeedBase';
    const OPEN_SWITCH    =    'openSwitch';
    const SWITCH_INDEX    =    'swithIndex';
}
class TblSwitchField
{
    const UID    =    'uid';
    const GROUP0 =    'group0';
    const GROUP1 =    'group1';
    const GROUP2 =    'group2';
}

class SwitchSession
{
    const SWITCHSESSION    =    'switch.info';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */