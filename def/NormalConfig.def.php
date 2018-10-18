<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalConfig.def.php 258059 2016-08-24 03:39:06Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/NormalConfig.def.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-24 03:39:06 +0000 (Wed, 24 Aug 2016) $
 * @version $Revision: 258059 $
 * @brief
 *
 **/
class NormalConfigDef
{
	public static $GOLD_BOX_ARR = array(30003, 30013);
	public static $BOX_ARR = array(30001,30002,30003,30011,30012,30013);

    const CONFIG_ID_FIGHTSOUL_POSOPEN = 1;
    const CONFIG_ID_CHANGE_NAME = 2;
    const CONFIG_ID_CLEAR_SWEEPCD = 3;
    const CONFIG_ID_GOLDBOX_SERIAL = 4;
    const CONFIG_ID_GOLDBOX_DROP = 5;
    const CONFIG_ID_RESETBASENUM_NEED_ITEM = 6;
    const CONFIG_ID_RESETGOLDTREE_NEED_ITEM = 7;
    const CONFIG_ID_DIVINE_NEED_ITEM = 8;
    const CONFIG_ID_GUILD_FIGHTEACHOTHER_LIMITS = 9;
    const CONFIG_ID_HELPARMYINCOME_RATIO = 10; //协助军收入系数
    const CONFIG_ID_ONEHELPARMY_ENHANCE = 11;  //单个协助军收入增益
    const CONFIG_ID_LOOTHELPARMY_COSTEXCETION = 12;   //抢夺协助军消耗体力
    const CONFIG_ID_RESPLAYER_LV = 13;   //资源矿玩家等级修正
    const CONFIG_ID_RES_DELAY_DATA = 14;    //资源矿延迟时间金币时间组
    const CONFIG_ID_FRIEND_PK_NUM = 15;//好友相互切磋次数
    const CONFIG_ID_VIP_EFFECT = 16;//
    const CONFIG_ID_SECOND_PIT_NEED_VIP = 17;//
    const CONFIG_ID_PIT_HELPER_TIMELIMIT = 18;//
    const CONFIG_ID_CHAT_CHANGEFIGURE_NEEDVIP = 19;//聊天界面修改头像需要VIP等级
    const CONFIG_ID_GOLDPIT_NEED_GOLD = 20;//金币矿区占领消耗金币
    const CONFIG_ID_TALENT_INHERIT_NEEDGOLD = 21;//觉醒能力传承花费金币
    const CONFIG_ID_TRANSFER_COST = 24;//变身花费
    const CONFIG_ID_TRANSFER_COUNTRY_WEI = 25;//魏国变身
    const CONFIG_ID_TRANSFER_COUNTRY_SHU = 26;//蜀国变身
    const CONFIG_ID_TRANSFER_COUNTRY_WU = 27;//吴国变身
    const CONFIG_ID_TRANSFER_COUNTRY_QUN = 28;//群雄变身
    /**
        6星武将预览魏国英雄ID组29
        6星武将预览蜀国英雄ID组30
        6星武将预览吴国英雄ID组31
        6星武将预览群雄英雄ID组32
                    神秘层战斗开启跳过33
                    试练塔战斗开启跳过34
                    武将列传副本战斗开启跳过35
                    熊猫书马战斗开启跳过36
                    精英副本战斗开启跳过37
     * @var unknown_type
     */
    const CONFIG_ID_TALENT_GROUP_LIST_1 = 38;//武将可洗练天赋ID组1
    const CONFIG_ID_TALENT_GROUP_WEIGHT_1 = 39;//洗练天赋权重组1
    const CONFIG_ID_TALENT_GROUP_LIST_2 = 40;//武将可洗练天赋ID组2
    const CONFIG_ID_TALENT_GROUP_WEIGHT_2 = 41;//洗练天赋权重组2
    const CONFIG_ID_TALENT_GROUP_LIST_3 = 42;//武将可洗练天赋ID组3
    const CONFIG_ID_TALENT_GROUP_WEIGHT_3 = 43;//洗练天赋权重组3
    const CONFIG_ID_TALENT_GROUP_LIST_4 = 44;//武将可洗练天赋ID组4
    const CONFIG_ID_TALENT_GROUP_WEIGHT_4 = 45;//洗练天赋权重组4
    const CONFIG_ID_ORANGE_HEROFRAG_DROP = 46;//普通副本橙卡碎片掉落
    const CONFIG_ID_GOLD_TREE_EXP_TBL = 47;//摇钱树经验表
    const CONFIG_ID_ARR_DRESS_ROOM_ID = 48;//时装屋
    /**
                    军团副本战斗开启跳过49
                    阵法等级上限50
     */
    const CONFIG_ID_DRAGON_CONTRIBUTE_MAX_NUM = 51;//寻龙探宝捐献事件最大捐献次数
    const CONFIG_ID_ROB_GOLDPIT_MINCAPTURE = 52;//金币区域资源矿保底收益时间52
    const CONFIG_ID_OPEN_DRAGON_MIN_POINT = 53;//开启寻龙试炼要求寻龙积分
    const CONFIG_ID_GODWEAPON_POS_OPENLV = 54;//开启神兵栏位等级
    const CONFIG_ID_PASS_FREE_NUM = 55;
    const CONFIG_ID_GODWEAPON_LEGEND = 56;//神兵传承花费金币
    const CONFIG_ID_FORMULA = 57;//主角技能树合成公式
    const CONFIG_ID_FORMULA_ITEM = 58;//技能树合成目标物品
    const CONFIG_ID_FORMULA_GOLD = 59;//合成物品金币价格
    const CONFIG_ID_SWEEP_NOCD_NEEDLV = 60;//清除副本连战CD所需级别
    const CONFIG_ID_TREAS_DEVELOP_LEVEL = 62;//宝物升橙需求等级
    const CONFIG_ID_TREAS_INLAY	= 63;//开启宝物镶嵌第一个孔
   	const CONFIG_ID_TRANSFER_ASSIGN_COST = 65;//指定武将变身消耗
   	const CONFIG_ID_GUILD_CHANGE_NAME = 66;//军团改名价格
   	const CONFIG_ID_GUILD_JOIN_SHARE = 67;//分粮草入团时间限制
   	const CONFIG_ID_OPEN_GOLD_BOX = 68;//使用金箱子

   	const CONFIG_ID_COPY_DOUBLE_EXP_LEVEL = 70;//新服福利活动双倍经验等级限制
    const CONFIG_ID_DRAGON_RESET_ITEM = 71;//寻龙探宝道具重置
    const CONFIG_ID_POCKET_POS_OPENLV = 72;//锦囊开启等级限时
    const CONFIG_ID_POCKET_REBORN = 73;//锦囊重生消耗
    const CONFIG_ID_AUTO_ATTACK_BOSS = 74;//自动攻击boss所需主角级别
    const CONFIG_ID_HUNT_DROP_SWITCH_LEVEL = 75;//猎魂掉落变更等级
    const CONFIG_ID_HERO_2_SOUL_COST = 76;//武将化魂消耗
    const CONFIG_ID_DROP_HERO_2_SOUL_NEED_LEVEL = 77;//副本掉落武将转化为武魂需要的玩家等级
    const CONFIG_ID_REMOVE_PILL_COST_SILVER = 82;//卸下1个丹药银币花费
    const CONFIG_ID_RAPID_HUNT_SOUL = 85;//极速猎魂
    const CONFIG_ID_RAPID_HUNT_SOUL_TYPE = 86;//极速猎魂银币
    const CONFIG_ID_REINFORCE_GOD_WEAPON_LEVEL = 87;//神兵强化添加10个材料需要的玩家等级
    const CONFIG_ID_ONE_KEY_SEIZE = 88;//一键夺宝相关
    const CONFIG_ID_RESOLVE_HERO_JH_COST_SILVE = 89;//分解一个武将精华消耗的银币
    const CONFIG_ID_RAPID_HUNT_QUALITY = 90;//取消5星猎魂限制
    const CONFIG_ID_TALLY_POS_OPENLV = 91;//兵符开启等级限制
    const CONFIG_ID_TREASURE_TRANSFER_NEED_LEVEL = 92;//宝物转换开启等级
    const CONFIG_ID_TREASURE_TRANSFER_ARR = 93;//宝物转换数组
    const CONFIG_ID_TREASURE_TRANSFER_COST = 94;//宝物转换花费
    const CONFIG_ID_GODWEAPON_TRANSFER_NEED_LEVEL = 95;//神兵转换开启等级
    const CONFIG_ID_GODWEAPON_TRANSFER_ARR = 96;//神兵转换数组
    const CONFIG_ID_MINERAL_GUILD_EXTRA_RES=98;//资源矿军团加成
    const CONFIG_ID_GODWEAPON_TRANSFER_COST = 97;//神兵转换花费
    const CONFIG_ID_MASTER_HERO_AWAKE_ABILITY = 99;//主角初始觉醒技能
    const CONFIG_ID_FS_2_RED = 102;//战魂升红需人物等级
    const CONFIG_ID_UNION_OPEN_LEVEL_ARR = 103;//聚义厅开启等级
    const CONFIG_ID_ADESACT_BUY_LIMIT = 104; //活动副本之天命副本购买次数限制
    const CONFIG_ID_PILL_PORMULA         =105;//丹药合成消耗
    const CONFIG_ID_PILL_RESULT         = 106;//丹药合成结果
    const CONFIG_ID_RESET_HERO_DESTINY = 107;//武将天命重生金币花费
    const CONFIG_ID_CHARIOT_POS_TYPE_LV =108;//战车位置对应的类型和需求等级等级
    const CONFIG_ID_CHANGE_SEX = 110;//主角变性消耗变性卡
    const CONFIG_ID_MAX_SYS_REWARD_EVERYDAY = 112;//web端每日发系统奖励的个数
    const CONFIG_ID_TALLY_TRANSFER_NEED_LEVEL = 113;//兵符转换开启等级
    const CONFIG_ID_TALLY_TRANSFER_ARR = 114;//兵符转换数组
    const CONFIG_ID_TALLY_TRANSFER_COST = 115;//兵符转换花费
    const CONFIG_ID_GUILDROB_OFFLINE_NEED_LEVEL = 116;//离线抢粮开启等级

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */