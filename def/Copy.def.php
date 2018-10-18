<?php
class CopyDef
{
	const FORCE_ROUND = 1;						// 普通回合
	const NORMAL_ROUND = 0;						// 强制回合
	
	const FORCE_PASS = 1;
	const NORMAL_PASS = 0;
	
	const RESET_BASE_SPEND_TYPE_ITEM = 2;
	const RESET_BASE_SPEND_TYPE_GOLD = 1;
}
class EliteCopyStatus
{
    const NOTOPEN = -1;
	const CANSHOW = 0;
	const CANATTACK = 1;
	const PASS = 2;
}
class BaseStatus
{
	const CANSHOW = 0;
	const CANATTACK = 1;
	const NPCPASS = 2;
	const SIMPLEPASS = 3;
	const NORMALPASS = 4;
	const HARDPASS = 5;
}
/**
 * 据点难度级别
 * @author dell
 *
 */
class BaseLevel
{
	const NPC = 0;
	const SIMPLE = 1;
	const NORMAL = 2;
	const HARD = 3;
}
class CopyType
{
	const INVALIDTYPE = -1;
	const NORMAL = 0;
	const ELITE = 1;
	const ACTIVITY = 2;
	const TOWER = 3;
}
/**
 * 血量继承模式    策划确定的数值
 * @author dell
 *
 */
class HpModal
{
	const INHERIT		= 1;
	const NOTINHERIT	= 0;
}
/**
 * 复活模式   策划确定的数组
 * @author dell
 *
 */
class ReviveModal
{
	const CAN = 1;
	const CANNOT = 0;
}
class CopySessionName
{
	const COPYLIST = 'copy.copylist';//普通副本列表
	const COPYID   = 'global.copyid';//当前的普通副本
	const ATTACKINFO = 'base.attackinfo';	//据点攻击时记录各种信息(副本、爬塔通用）
	const ECOPYLIST	=	'copy.elite';//精英副本信息
	const ACOPYLIST	=	'copy.actlist';//活动副本信息
	const USERCOPY = 'copy.usercopy';//用户副本信息  如扫荡倒计时
	const NCOPYSCORE = 'copy.score';
}
/**
 *    'copy_id' => 2,
      'base_id' => 2007,
      'base_level' => 1,
      'hp_modal' => 0,
      'drop_hero' =>
      array (
        0 =>
        array (
          'htid' => 30002,
          'level' => 9,
          'mstId' => 1012131,
        ),
        1 =>
        array (
          'htid' => 30001,
          'level' => 9,
          'mstId' => 1012132,
        ),
        ),
      'base_progress' =>
      array (
        2013 => 41825,
        2014 => -1,
        2015 => -1,
        2016 => -1,
      ),
      'status' => 2,
      'card_info' =>
      array (
        'hp' =>
        array (
          10055297 =>
          array (
            'cur_hp' => 604,
            'max_hp' => 936,
          ),
          10055300 =>
          array (
            'cur_hp' => 1398,
            'max_hp' => 1575,
          ),
          10055144 =>
          array (
          'cur_hp' => 1584,
            'max_hp' => 1584,
          ),
        ),
      ),
      'save_time' => 1381826445,
    ),
 * 
 */
class ATK_INFO_FIELDS
{
    //当前攻击副本ID  对应爬塔系统中tower_level
	const COPYID	=	'copy_id';
	const BASEID	=	'base_id';
	const BASELEVEL	=	'base_level';
	//当前副本攻击进度 
	const BASEPRG	=	'base_progress';
	//当前副本攻击中掉落卡牌
	const DROPHERO  =   'drop_hero';
	//卡牌血量信息
	const CARDINFO	=	'card_info';
	//据点攻击血量模式
	const HPMODAL	=	'hp_modal';
	//据点复活模式
	const REVIVENUM	=	'revive_num';
	//此信息的保存时间
	const SAVETIME	=	'save_time';
	//据点攻击状态 有四个取值   START ATTACK PASS FAIL
	const STATUS    =    'status';
	//模块  取值如0（invalid)
	const MODULE = 'module';
	
	//爬塔系统使用的两个字段
	const FAILNUM   =   'fail_num';//失败次数
	const LOTTERYNUM = 'lottery_num';//抽奖次数
	
	//card_info字段内部的子字段
	const CARDINFO_HP_FIELD	=	'hp';
	const CARDINFO_CUR_HP = 'cur_hp';
	const CARDINFO_MAX_HP = 'max_hp';
	
	//普通副本得分条件相关的字段
	const ROUND_NUM = 'round_num';
	const DEAD_CARD_NUM = 'dead_num';
	const COST_HP = 'cost_hp';
}

class ATK_INFO_ARMY_STATUS
{
    const NOT_DEFEAT = -1;
    const DEFEAT_FAIL = 0;
}
class ATK_INFO_STATUS
{
    const START    =    0;
    const FAIL    =    1;
    const ATTACK    =    2;
    const PASS    =    3;
}
class USER_COPY_FIELD
{
    const UID = 'uid';
    const LEVEL = 'level';
    const UTID = 'utid';
    const UNAME = 'uname';
    const COPY_ID = 'copy_id';
    const LAST_COPY_TIME = 'last_copy_time';
    const SCORE = 'score';
    const LAST_SCORE_TIME = 'last_score_time';
    const SWEEP_CD = 'sweep_cd';
    const CLEAR_SWEEP_NUM = 'clear_sweep_num';
    const LAST_RFRTIME = 'last_rfr_time';
    static $ALL_FIELD = array(
            self::UID,
            self::SWEEP_CD,
            self::COPY_ID,
            self::LAST_COPY_TIME,
            self::SCORE,
            self::LAST_SCORE_TIME,
            self::CLEAR_SWEEP_NUM,
            self::LAST_RFRTIME
            );
}
/**
 * 副本箱子奖励类型（物品、金币、银币、将魂、武将）   此数值由策划确定   
 * @author dell
 */
class CASE_REWARD_TYPE
{
    const REWARD_ITEM = 0;
    const REWARD_GOLD = 1;
    const REWARD_SILVER = 2;
    const REWARD_SOUL = 3;
    const REWARD_HERO = 4;
}
/**
 * 活动副本类型（摇钱树、活动据点、守关）  此数值由策划确定
 * @author dell
 */
class ACT_COPY_TYPE
{
    const GOLDTREE = 1;    //摇钱树
    const EXPTREASURE = 2; //经验宝物副本
    const EXPHERO = 3;
    const EXPUSER = 4;     //主角经验副本
    const DESTINY = 5;     //天命副本
    
    const GOLDTREE_COPYID = 300001;
    const EXPTREAS_COPYID = 300002;
    const EXPUSER_COPYID  = 300005;
    const DESTINY_COPYID  = 300006;
}

class NORMAL_COPY_FIELD 
{
    const UID = 'uid';
    const COPYID = 'copy_id';
    const SCORE = 'score';
    const PRIZEDNUM = 'prized_num';
    const REFRESH_ATKNUM_TIME = 'refresh_atknum_time';
    const VA_COPY_INFO = 'va_copy_info';
    const VA_PROGRESS = 'progress';
    const VA_DEFEAT_NUM = 'defeat_num';
    const VA_RESET_NUM = 'reset_num';
    
    const BTSTORE_FIELD_FREE_ATK_NUM = 'free_defeat_num';
}

/**
 * 获取据点得分的条件限制类型   此数值由策划确定
 * @author dell
 */
class GET_SCORE_COND_TYPE
{
    const ROUND_NUM = 1;
    const COST_HP = 2;
    const REVIVE_NUM = 3;
    const DEAD_NUM = 4;
    const THREE_STAR_HERONUM = 5;
    const FOUR_STAR_HERONUM = 6;
}

class ACT_COPY_FIELD
{
    const VA_GOLD_TREE_GOLD_ATKTIME = 'gold_atk_time';
    const VA_GOLD_TREE_GOLD_ATKNUM  = 'gold_atk_num';
    const VA_GOLD_TREE_EXP = 'gold_tree_exp';
    const VA_GOLD_TREE_LEVEL = 'gold_tree_level';
    const VA_GOLD_TREE_BATTLEINFO = 'battle_info';
    const VA_GOLD_TREE_BATTLEINFO_VALID = 'fmt_valid';
    const VA_EXP_USER_BASE_ID = 'base_id';
}

class EXP_USER_FIELD
{
	const BASE_ID = 'base_id';
	const ARMY_ID = 'army_id';
	const BASE_LEVEL = 'level';
	const DROP_ID = 'drop_id';
}