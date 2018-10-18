<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(hoping@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class DragonDef
{
    const HAVE_COVER = 0;  //该点已走过
    const HAVE_NOT_COVER = 1;   //该点没走过
    const INIT_POINT = 0;   //初始化积分
    const INIT_FLOOR = 1;
    const TRIAL_FLOOR = 5;    //试炼对应的层数
    const INIT_RESETNUM = 0;    //已重置次数
    const INIT_FREERESETNUM = 1;    //初始免费重置次数
    const INIT_BUYACTNUM = 0;   //已购买行动力次数
    const INIT_BUYHPNUM = 0;    //已购买hp次数
    const INIT_TOTAL_POINT = 0;
    const ARRHP = 'arrhp';
    const CUREVENT = 'cur_event';
    const ARRADDTION = 'arraddtion';
    const MAP = 'map';
    const MAX_POSID = 83;
    const MIN_POSID = 0;
    const EVENT_ID_AFTER_BOMB = -1;
    const BOMB_NUM_1 = 1;
    const BOMB_NUM_2 = 2;
    const BOMB_NUM_3 = 3;
    const BOMB_NUM_4 = 4;
    const MAX_FLOOR = 4;
    const BOXNUM = 11;
    const EVENT_EXIT_POSID = 80;

    const EVENT_TYPE_XB = 1;  //寻宝
    const EVENT_TYPE_SJ = 2; //守军
    const EVENT_TYPE_FB = 3;    //伏兵
    const EVENT_TYPE_HX = 4;    //回血
    const EVENT_TYPE_XY = 5; //险遇
    const EVENT_TYPE_DJ = 6;    //遁甲
    const EVENT_TYPE_DW = 7;    //毒雾
    const EVENT_TYPE_DT = 8;    //答题
    const EVENT_TYPE_ZL = 9;    //指路
    const EVENT_TYPE_HW = 10;   //换位
    const EVENT_TYPE_ZD = 11;   //炸弹
    const EVENT_TYPE_HH = 12;   //横祸
    const EVENT_TYPE_JM = 13;   //旧梦
    const EVENT_TYPE_TL = 14;   //天怜
    const EVENT_TYPE_TQ = 15;   //天谴
    const EVENT_TYPE_RK = 16;   //入口标识
    const EVENT_TYPE_XC = 17;   //下一层表似乎
    const EVENT_TYPE_EXIT = 18; //终点
    const EVENT_TYPE_WALL = 19; //墙
    const EVENT_TYPE_SR = 20;   //商人
    const EVENT_TYPE_JX = 21;   //捐献
    const EVENT_TYPE_SL = 22;   //试炼

    public static $EVENT_TYPE_NO_IMMEDIATE_POINT = array(
        self::EVENT_TYPE_DT,
        self::EVENT_TYPE_FB,
        self::EVENT_TYPE_SJ,
    );

    const EVENT_STATUS_ISPASS = 0;  //地图点是否走过
    const EVENT_STATUS_ISBOMB = 1;  //地图点是否被炸过
    const EVENT_STATUS_ISFOG = 2;   //雾是否被驱散过
    const EVENT_STATUS_ISTRIGGER = 3;   //事件是否被触发

    const HASMOVEYES = 1;
    const HASMOVENO = 0;

    public static $INIT_EVENT_STATUS = array(
        self::EVENT_STATUS_ISPASS => 0,
        self::EVENT_STATUS_ISBOMB => 0,
        self::EVENT_STATUS_ISFOG => 0,
        self::EVENT_STATUS_ISTRIGGER => 0,
    );

    public static $FB_LEVELS = array(
        array(1, 3),
        array(4, 10),
        array(11, 20),
        array(21, 30),
        array(31, 50),

        array(51, 70),
        array(71, 100),
        array(101, 150),
        array(151, 200),
        array(201, 300),

        array(301, 400),
        array(401, 500),
        array(501, 600),
        array(601, 700),
        array(701, 1000),

        array(1001, 1500),
        array(1501, 2000),
        array(2001, 3000),
        array(3001, 5000),
        array(5001, 999999)
    );

    const DEFAULT_MODE = 0;    //普通寻龙模式
    const TRIAL_MODE = 1;    //试炼模式
    const TRIAL_EVENT_ID_SL = 21000;
}

class TblDragonDef
{
    const TABELDRAGON = 't_dragon';

    const UID = 'uid';  //玩家uid
    const MODE = 'mode';    //模式，普通寻龙探宝或者寻龙试炼
    const LASTTIME = 'last_time';    //玩家上次寻龙探宝时间
    const ACT = 'act';  //行动力
    const RESETNUM = 'resetnum';    //当天已重置次数
    const FREERESETNUM = 'free_reset_num';  //累积免费重置次数
    const BUY_ACT_NUM = 'buy_act_num';  //当天购买行动力次数
    const FREE_AI_NUM = 'free_ai_num';  //当天一键寻龙免行动力次数
    const BUY_HP_NUM = 'buy_hp_num';    //当天购买血池次数
    const HP_POOL = 'hp_pool';  //血池
    const POINT = 'point';  //积分
    const TOTAL_POINT = 'total_point';  //总积分
    const ONCE_MAX_POINT = 'once_max_point';    //历史单次最高积分
    const FLOOR = 'floor';  //当前所在层
    const POSID = 'posid';  //当前玩家位置坐标
    const HASMOVE = 'hasmove';  //是否移动过（能否一键寻龙)
    const VA_DATA = 'va_data';
    const VA_BF = 'va_bf';  //战斗相关 阵型数据
    public static $DRAGON_FIELDS = array(
        self::UID,
        self::MODE,
        self::LASTTIME,
        self::ACT,
        self::RESETNUM,
        self::FREERESETNUM,
        self::BUY_ACT_NUM,
        self::BUY_HP_NUM,
        self::FREE_AI_NUM,
        self::HP_POOL,
        self::POINT,
        self::TOTAL_POINT,
        self::ONCE_MAX_POINT,
        self::FLOOR,
        self::POSID,
        self::HASMOVE,
        self::VA_DATA,
        self::VA_BF,
    );
}

class DragonCsvDef
{
    const ID = 'id'; //id 相当于层
    const INITACT = 'initact';    //初始行动力
    const RESETPAY = 'resetpay';    //重置花费
    const ITEMPANDECT = 'itempandect';  //宝物总览（前端用 不解析）
    const RENEWNUM = 'renewnum';    //每日免费重置次数
    const ADDPAY = 'addpay';    //重置递增上限组
    const ACTPAY = 'actpay';    //购买行动力价格
    const ADDACT = 'addact';    //行动力递增上限组
    const INITHP = 'inithp';    //初始血池血量
    const HPPAY = 'hppay';  //血槽购买花费

    const ADDHP = 'addhp';  //血槽购买递增

    const HEIGHT = 'height';    //寻龙探宝高度
    const WIDTH = 'width';  //寻龙探宝宽度
    const ACTERPOS = 'acterpos';    //主角出现位置
    const BOX1POS = 'box1pos';    //宝箱1出现位置
    const BOX2POS = 'box2pos';    //宝箱2出现位置
    const BOX3POS = 'box3pos';    //宝箱3出现位置
    const BOX4POS = 'box4pos';    //宝箱4出现位置

    const BOX5POS = 'box5pos';    //宝箱5出现位置
    const BOX6POS = 'box6pos';    //宝箱6出现位置
    const BOX7POS = 'box7pos';    //宝箱7出现位置
    const BOX8POS = 'box8pos';    //宝箱8出现位置
    const BOX9POS = 'box9pos';    //宝箱9出现位置

    const BOX10POS = 'box10pos';    //宝箱10出现位置
    const BOX11POS = 'box11pos';    //宝箱11出现位置

    const POS1EVENT = 'pos1event';  //位置1事件
    const POS2EVENT = 'pos2event';  //位置2事件
    const POS3EVENT = 'pos3event';  //位置2事件
    const POS4EVENT = 'pos4event';  //位置2事件
    const POS5EVENT = 'pos5event';  //位置2事件
    const POS6EVENT = 'pos6event';  //位置2事件
    const POS7EVENT = 'pos7event';  //位置2事件

    const POS8EVENT = 'pos8event';  //位置1事件
    const POS9EVENT = 'pos9event';  //位置2事件
    const POS10EVENT = 'pos10event';  //位置2事件
    const POS11EVENT = 'pos11event';  //位置2事件
    const POS12EVENT = 'pos12event';  //位置2事件
    const POS13EVENT = 'pos13event';  //位置2事件
    const POS14EVENT = 'pos14event';  //位置2事件

    const POS15EVENT = 'pos15event';  //位置1事件
    const POS16EVENT = 'pos16event';  //位置2事件
    const POS17EVENT = 'pos17event';  //位置2事件
    const POS18EVENT = 'pos18event';  //位置2事件
    const POS19EVENT = 'pos19event';  //位置2事件
    const POS20EVENT = 'pos20event';  //位置2事件
    const POS21EVENT = 'pos21event';  //位置2事件

    const POS22EVENT = 'pos22event';  //位置1事件
    const POS23EVENT = 'pos23event';  //位置2事件
    const POS24EVENT = 'pos24event';  //位置2事件
    const POS25EVENT = 'pos25event';  //位置2事件
    const POS26EVENT = 'pos26event';  //位置2事件
    const POS27EVENT = 'pos27event';  //位置2事件
    const POS28EVENT = 'pos28event';  //位置2事件

    const POS29EVENT = 'pos29event';  //位置1事件
    const POS30EVENT = 'pos30event';  //位置2事件
    const POS31EVENT = 'pos31event';  //位置2事件
    const POS32EVENT = 'pos32event';  //位置2事件
    const POS33EVENT = 'pos33event';  //位置2事件
    const POS34EVENT = 'pos34event';  //位置2事件
    const POS35EVENT = 'pos35event';  //位置2事件

    const POS36EVENT = 'pos36event';  //位置1事件
    const POS37EVENT = 'pos37event';  //位置2事件
    const POS38EVENT = 'pos38event';  //位置2事件
    const POS39EVENT = 'pos39event';  //位置2事件
    const POS40EVENT = 'pos40event';  //位置2事件
    const POS41EVENT = 'pos41event';  //位置2事件
    const POS42EVENT = 'pos42event';  //位置2事件

    const POS43EVENT = 'pos43event';  //位置1事件
    const POS44EVENT = 'pos44event';  //位置2事件
    const POS45EVENT = 'pos45event';  //位置2事件
    const POS46EVENT = 'pos46event';  //位置2事件
    const POS47EVENT = 'pos47event';  //位置2事件
    const POS48EVENT = 'pos48event';  //位置2事件
    const POS49EVENT = 'pos49event';  //位置2事件

    const POS50EVENT = 'pos50event';  //位置1事件
    const POS51EVENT = 'pos51event';  //位置2事件
    const POS52EVENT = 'pos52event';  //位置2事件
    const POS53EVENT = 'pos53event';  //位置2事件
    const POS54EVENT = 'pos54event';  //位置2事件
    const POS55EVENT = 'pos55event';  //位置2事件
    const POS56EVENT = 'pos56event';  //位置2事件

    const POS57EVENT = 'pos57event';  //位置2事件
    const POS58EVENT = 'pos58event';  //位置1事件
    const POS59EVENT = 'pos59event';  //位置2事件
    const POS60EVENT = 'pos60event';  //位置2事件
    const POS61EVENT = 'pos61event';  //位置2事件
    const POS62EVENT = 'pos62event';  //位置2事件
    const POS63EVENT = 'pos63event';  //位置2事件

    const POS64EVENT = 'pos64event';  //位置2事件
    const POS65EVENT = 'pos65event';  //位置1事件
    const POS66EVENT = 'pos66event';  //位置2事件
    const POS67EVENT = 'pos67event';  //位置2事件
    const POS68EVENT = 'pos68event';  //位置2事件
    const POS69EVENT = 'pos69event';  //位置2事件
    const POS70EVENT = 'pos70event';  //位置2事件

    const POS71EVENT = 'pos71event';  //位置2事件
    const POS72EVENT = 'pos72event';  //位置1事件
    const POS73EVENT = 'pos73event';  //位置2事件
    const POS74EVENT = 'pos74event';  //位置2事件
    const POS75EVENT = 'pos75event';  //位置2事件
    const POS76EVENT = 'pos76event';  //位置2事件
    const POS77EVENT = 'pos77event';  //位置2事件

    const POS78EVENT = 'pos78event';  //位置2事件
    const POS79EVENT = 'pos79event';  //位置1事件
    const POS80EVENT = 'pos80event';  //位置2事件
    const POS81EVENT = 'pos81event';  //位置2事件
    const POS82EVENT = 'pos82event';  //位置2事件
    const POS83EVENT = 'pos83event';  //位置2事件
    const POS84EVENT = 'pos84event';  //位置2事件

    const EXPLOREMUSIC = 'exploreMusic';    //寻龙探宝声音
    const AIEXPLORECOSTACT = 'aiExploreCostAct';    //自动探宝消耗
    const AIEXPLOREEVENT = 'aiExploreEvent';    //自动探宝事件集
    const AIEXPLOREREWARD = 'aiExploreReward';  //自动探宝奖励
    const AIEXPLORETIPS = 'aiExploreTips';  //自动探宝奖励描述
    const AIEXPLOREPAY = 'aiExplorePay';    //自动探宝消耗金币
    const AIEXPLOREREWARDPOINT = 'aiExploreRewardPoint';    //自动探宝奖励积分

    const AIEXTRAEVENT = 'aiextraevent';    //自动探宝额外事件集

}

class DragonEventCsvDef
{
    const ID = 'id';
    const TYPE = 'type';    //事件类型
    const CONDITION = 'condition';  //事件条件
    const COSTACT = 'costact';  //消耗探宝行动力
    const WEIGHT = 'weight';    //权重
    const EXPLAIN = 'explain';  //事件描述 (前端用)
    const SHOW = 'show';    //是否强制显示 (前端用)
    const ICON = 'icon';    //时间图标 (前端用)
    const DOUBLEPAY = 'doublepay';  //双倍领取花费
    const ONKEYPAY = 'onkeypay';    //一键完成花费
    const REWARD = 'reward';    //奖励数组
    const POINT = 'point';  //获得积分

    const POINTTIPS = 'pointTips';  //点击提示
    const AITIPS = 'aiTips';    //自动战斗提示
    const AIEXPLOREPOINT = 'aiExplorePoint';    //自动寻龙积分

    const GOODSID = 'goodsId';    //商人事件和捐献事件对应的商品id
    const ARMYID = 'armyId';    //试炼事件boss的id
    const BOSSCOSTACT = 'bossCostAct';    //boss行动力消耗
    const BOSSSCORE = 'bossScore';    //boss积分
    const BOSSDROP = 'bossDrop';    //boss掉落
    const GOLDBOSS = 'goldBoss';    //金币挑战boss价格
}

class DragonAnswerCsvDef
{
    const ID = 'id';
    const QUESTION = 'question';
    const ANSWERA = 'answera';
    const ANSWERB = 'answerb';
    const ANSWER = 'answer';
}

class DragonEventShopCsvDef
{
    const GOODID = 'goodId';    //物品id
    const ITEM = 'item';    //物品
    const EACHPOINT = 'eachPoint';    //奖励积分
    const ORIGINALCOST = 'originalCost';    //原价
    const NOWCOST = 'nowCost';    //现价
    const ADD = 'add';    //递增值
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */