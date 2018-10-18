<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Olympic.def.php 150556 2015-01-07 02:23:37Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Olympic.def.php $
 * @author $Author: ShijieHan $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-01-07 02:23:37 +0000 (Wed, 07 Jan 2015) $
 * @version $Revision: 150556 $
 * @brief 
 *  
 **/
class OlympicDef
{
    const MIN_SIGNUP_INDEX = 0;
    const MAX_SIGNUP_INDEX = 31;
    const CHALLENGE_DEFAULT_ID = 1;
    
    const OLYMPIC_LOCK_PREFIX = 'olympic';
    
    const RANK_32 = 32;
    const RANK_16 = 16;
    const RANK_8 = 8;
    const RANK_4 = 4;
    const RANK_2 = 2;
    const RANK_1 = 1;
    
    const MIN_DELAY_TIME = 1;
    const MAX_DELAY_TIME = 60;

    const LOCKER = "olympic";					// 锁的名字

    public static $step = array(				// 跳几个人获取战斗对象
        3 => 2,//16强赛
        4 => 4,
        5 => 8,
        6 => 16,
        7 => 32
    );

    public static $next = array(				// 下一名次
        3 => 16,
        4 => 8,
        5 => 4,
        6 => 2,
        7 => 1,
    );
    
    public static $next_rank = array(			// 下一名次
        32 => 16,
        16 => 8,
        8 => 4,
        4 => 2,
        2 => 1,
    );

    const NPC_LEVLE = 50;   //初始化奖池时，默认npc等级
    const SILVER_POOL_LOCK = 'silver_pool';
}

class UserOlympicDef
{
    const FIELD_UID = 'uid';
    const FIELD_CHALLENGE_TIME = 'challenge_time';
    const FIELD_CHALLENGE_CDTIME = 'challenge_cdtime';
    const FIELD_INTEGRAL = 'integral';
    const FIELD_CHEERUID = 'cheer_uid';
    const FIELD_CHEER_TIME = 'cheer_rfr_time';
    const FIELD_CHEER_NUM = 'cheer_num';
    const FIELD_BE_CHEER_NUM = 'be_cheer_num';
    const FIELD_CHEER_VALID_NUM = 'cheer_valid_num';
    const FIELD_WIN_ACCUMNUM = 'win_accum_num';
    const FIELD_WEEKLY_RFR_TIME = 'weekly_rfr_time';
    const FIELD_VA_OLYMPIC = 'va_olympic';
    const FIELD_STATUS = 'status';
    
    const SUBFIELD_INTEGRALRECORD = 'integral_record';
    
    public static $ALL_FIELD = array(
            self::FIELD_UID,
            self::FIELD_CHALLENGE_TIME,
            self::FIELD_CHALLENGE_CDTIME,
            self::FIELD_INTEGRAL,
            self::FIELD_CHEERUID,
            self::FIELD_CHEER_TIME,
            self::FIELD_CHEER_NUM,
            self::FIELD_BE_CHEER_NUM,
            self::FIELD_CHEER_VALID_NUM,
            self::FIELD_WIN_ACCUMNUM,
            self::FIELD_WEEKLY_RFR_TIME,
            self::FIELD_VA_OLYMPIC,
            self::FIELD_STATUS
            );
    
    public static $ARRFIELD_MODIFYBYOTHER_DELT = array(
            self::FIELD_CHEER_TIME,
            self::FIELD_BE_CHEER_NUM,
            self::FIELD_CHEER_VALID_NUM,
            self::FIELD_WIN_ACCUMNUM,
            self::FIELD_INTEGRAL
            );

    public static $ARRFIELD_OTHER_UPDATE_IGNORE = array(
        self::FIELD_CHEER_TIME,
        self::FIELD_CHEERUID,
        self::FIELD_CHEER_NUM,
        //self::FIELD_VA_OLYMPIC,
    );

    public static $ARRFIELD_OTHER_UPDATE_SET = array(

    );
}

class OlympicGlobalDef
{
    const ID = 'id';
    const SILVER_POOL = 'silver_pool'; //奖池
    const VA_DATA = 'va_data';  //VA

    const LAST_CHAMPION = 'last_champion'; //上届冠军
    const WIN_CONT = 'win_cont'; //连胜次数
    const AVG_LEVEL_OF_ARENA = 'avg_level_of_arean'; //竞技场排名前十的平均等级
    const SPECIAL_ID = 0;
    public static $REWARD_POOL_ARR_FIELD = array(
        self::SILVER_POOL,
        self::VA_DATA,
    );
}

class OlympicLogDef
{
    const FIELD_LOG_DATE_YMD = 'date_ymd';
    const FIELD_LOG_TYPE = 'log_type';
    const FIELD_LOG_INFO = 'va_log_info';

    const VA_INFO_PROGRESS = 'progress';
    const VA_INFO_ATKRES = 'atkres';
    const VA_INFO_REWAED_POOL = 'rewardpool';

    const VA_INFO_PROGRESS_STATUS = 'status';
    const VA_INFO_PROGRESS_UPDATETIME = 'updatetime';
    const VA_INFO_PROGRESS_ENDTIME = 'endtime';

    const VA_INFO_ATKRES_ATKER = 'atker';
    const VA_INFO_ATKRES_DEFER = 'defer';
    const VA_INFO_ATKRES_BRID = 'brid';
    const VA_INFO_ATKRES_RES = 'res';

    const VA_INFO_REWAED_POOL_SILVER_POOL = 'silver_pool';
    const VA_INFO_REWAED_POOL_LAST_CAMPION = 'last_campion';

    const SPECIAL_UID = 5;
}

class OlympicStage
{
    const PRE_OLYMPIC = 0;//比赛前阶段
    const PRELIMINARY_MATCH = 1;//预选赛阶段
    const OLYMPIC_GROUP = 2;    //分组阶段
    const SIXTEEN_FINAL = 3;//16强赛
    const EIGHTH_FINAL = 4;//8强赛
    const QUARTER_FINAL = 5;//4强赛
    const SEMI_FINAL = 6;//半决赛
    const FINAL_MATCH = 7;//决赛
    const AFTER_OLYMPIC = 8;//比赛后阶段 持续时间比较长
    
    public static $PRE_STAGE = array(
    		self::SIXTEEN_FINAL=>self::OLYMPIC_GROUP,
            self::EIGHTH_FINAL=>self::SIXTEEN_FINAL,
            self::QUARTER_FINAL => self::EIGHTH_FINAL,
            self::SEMI_FINAL => self::QUARTER_FINAL,
            self::FINAL_MATCH => self::SEMI_FINAL,
            self::AFTER_OLYMPIC => self::FINAL_MATCH
            );
    
    const PRE_OLYMPIC_START = 39600;//比赛前阶段开始时间       11:00开始报名前准备
    const PRELIMINARY_MATCH_START = 43200;//预选赛开始时间   12:00开始报名
    const PRELIMINARY_MATCH_TIME = 300;//报名持续时间  报名结束之后就开始分组  12:35开始分组
    const PRELIMINARY_FIGHT_GAP = 60;//分组预留时间   12:26开始晋级赛（即16强赛）
    //各个阶段持续时间   由于是半途改的，没有把上面两个配置弄进来  
    public static $ARR_FIGHT_DURATION = array(		
    		self::SIXTEEN_FINAL => 60,
    		self::EIGHTH_FINAL => 60,
    		self::QUARTER_FINAL => 120,
    		self::SEMI_FINAL => 120,
    		self::FINAL_MATCH => 120,
    );

    public static $ALL_STAGE = array(
            self::PRE_OLYMPIC,
            self::PRELIMINARY_MATCH,
            self::OLYMPIC_GROUP,
            self::SIXTEEN_FINAL,
            self::EIGHTH_FINAL,
            self::QUARTER_FINAL,
            self::SEMI_FINAL,
            self::FINAL_MATCH,
            self::AFTER_OLYMPIC,
            );
    
}
class OlympicStageStatus
{
    const PREPARE = 0;
    const START = 1;
    const DELAY = 2;//延时
    const ERR = 3;
    const END = 4;
}

class OlympicLogType
{
    const PRELIMINARY_MATCH = OlympicStage::PRELIMINARY_MATCH;//预赛
    const OLYMPIC_GROUP = OlympicStage::OLYMPIC_GROUP;  //分组
    const SIXTEEN_FINAL = OlympicStage::SIXTEEN_FINAL;//16强赛
    const EIGHTH_FINAL = OlympicStage::EIGHTH_FINAL;//8强赛
    const QUARTER_FINAL = OlympicStage::QUARTER_FINAL;//4强赛
    const SEMI_FINAL = OlympicStage::SEMI_FINAL;//半决赛 
    const FINAL_MATCH = OlympicStage::FINAL_MATCH;//决赛 
    const BATTLE_PROGRESS = 8;//比赛进度
    const REWARD_POOL = 9;//奖池
}

class OlympicRankDef
{
    const FIELD_UID = 'uid';
    const FIELD_SIGNUP_INDEX = 'sign_up_index';
    const FIELD_OLYMPICINDEX = 'olympic_index';
    const FIELD_FINAL_RANK = 'final_rank';
    
    public static $ALL_FIELD = array(
            self::FIELD_FINAL_RANK,
            self::FIELD_OLYMPICINDEX,
            self::FIELD_SIGNUP_INDEX,
            self::FIELD_UID
            );
}
/**
 * 1）	恭喜您助威的 玩家名称 成为了2014年7月5日的擂台冠军，您获得了XXX积分
2）	恭喜您在2014年7月5日的擂台中被抽中幸运奖，获得xx积分
3）	恭喜您被抽中本轮的超级幸运奖，获得XXX积分
4）	恭喜您成为XX强，获得XX强奖励，XXX积分
5）	恭喜您累计助威成功达到XX次，额外获得XX积分

 * @author dell
 *
 */
class OlympicIntegralGetType
{
    const CHEER_CHAMPION = 1;
    const LUCKY_BOY = 2;
    const SUPER_LUCKY_BOY = 3;
    const RANK_TOP = 4;
    const VALID_ACCUM_CHEERNUM = 5;
    
    public static $ALL_TYPE = array(
            self::CHEER_CHAMPION,
            self::LUCKY_BOY,
            self::SUPER_LUCKY_BOY,
            self::RANK_TOP,
            self::VALID_ACCUM_CHEERNUM
            );
}

class ChallengeCsvDef
{
    const ID = 'id';    //id
    const START_TIME = 'startTime'; //擂台赛开始时间
    const CHALLENGE_EVENT = 'challengeEvent'; //擂台赛周期事件
    const LAST_TIME_ARR = 'lastTimeArr'; //比赛持续时间数组
    const PRIZE_ID = 'prizeID'; //奖励ID数组
    const PRIZE_POINT = 'prizePoint'; //奖励积分数组

    const CDTIME = 'cdTime'; //挑战CD时间
    const CLEAR_CD_COST_GOLD = 'clearCDCostGold'; //秒挑战CD时间每10秒需要金币
    const JOIN_COST_BELLY = 'joinCostBelly'; //参赛花费游戏币基础值
    const INFO_LEN = 'infoLen'; //战报条数
    const CHEER_COST_BELLY = 'cheerCostBelly'; //助威花费游戏币基础值

    const CHEER_PRIZE_ID = 'cheerPrizeID'; //助威奖励ID
    const POINT_EXCHANGE = 'pointExchange'; //每1积分总奖金百分比
    const LUCKY_NUM = 'luckyNum'; //助威幸运奖人数
    const CHEER_LUCKY_POINT = 'cheerLuckyPoint'; //助威幸运奖获得积分
    const CHEER_LUCKY_PRIZE_ID = 'cheerLuckyPrizeID'; //助威幸运奖获得奖励ID

    const FINAL_PRIZE_POINT = 'finalPrizePoint'; //最终幸运大奖获得积分
    const FINAL_PRIZE_ID = 'finalPrizeID'; //最终幸运大奖获得奖励ID
    const MIN_PRIZE = 'minPrize'; //奖池游戏币基础值下限
    const MAX_PRIZE = 'maxPrize'; //奖池游戏币基础值上限
    const CONTINUE_REWARD = 'continueReward'; //累计助威成功次数

    const CHALLENGE_COST = 'challengeCost'; //挑战花费银币

    //下面是奖池新加的
    const EFFECTIVE_CHANGE = 'effectiveChange';  //战斗力改变属性ID组
    const REDUCE_EFFECTIVE = 'reduceEffective'; //连胜对应减少战斗力值
    const CHAMPION_RATE = 'championrate';   //冠军分成百分比
    const TERMINATOR_RATE = 'terminatorrate';   //终结者分成百分比
    const OTHER_RATE = 'otherrate'; //其他参与者分成百分比
    const CHEER_MULTIPLE = 'cheerMultiple'; //助威倍数
}

class ChallengeRewardCsvDef
{
    const ID = 'id'; //id
    const TIPS = 'tips'; //奖励描述
    const REWARD = 'reward'; //奖励数组
}

class OlympicWeekStatus
{
    const NOTHING_DAY = 0;
    const COMPETE_DAY = 1;
    const REWARD_DAY = 2;
    const COMPETE_REWARD_DAY = 3;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */