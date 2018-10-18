<?php
class CopyConf
{
	public static $COPY_TBL_ALL_FIELD = array(
			'uid',
			'copy_id',
			'score',
			'prized_num',
	        'refresh_atknum_time',
			'va_copy_info'
			);
	public static $REPLAY_TBL_ALL_FIELD = array(
			'uid',
			'level',
			'base_id',
			'base_level',
			'va_fight_record'
			);
	public static $BASE_FDRANK_TBL_ALL_FIELD = array(
			'uid',
			'level',
			'base_id',
			'base_level',
			'rank',
			'va_fight_record'
			);
	public static $COPY_FDRAND_TBL_ALL_FIELD = array(
			'uid',
			'level',
			'copy_id',
			'rank'
			);
	public static $ELITE_COPY_TBL_ALL_FIELD = array(
			'uid',			
			'last_defeat_time',
			'can_defeat_num',
	        'buy_atk_num',
			'va_copy_info'
			);
	public static $ACTIVITY_TBL_ALL_FIELD = array(
			'uid',
			'copy_id',
			'last_defeat_time',
			'can_defeat_num',
	        'buy_atk_num',
			'va_copy_info'
			);
	public static $CASE_INDEX = array(
			1,
			2,
			4);
	
	public static $BASE_LEVEL_INDEX = array(
			0 => 'npc',
			1 => 'simple',
			2 => 'normal',
			3 => 'hard'
			);
	public static $COPY_TYPE_TO_NAME = array(
	        CopyType::NORMAL => 'normal',
	        CopyType::ELITE  => 'elite',
	        CopyType::ACTIVITY => 'activity',
	        );
	
	public static $WEEK_INDEX = array(
			1=>'Monday',
			2=>'Tuesday',
			3=>'Wednesday',
			4=>'Thursday',
			5=>'Friday',
			6=>'Saturday',
			7=>'Sunday'
			);
	public static $ACTIVITY_TYPE_INDEX = array(
			1=>'tree',
			2=>'actbase',
			3=>'holdon'
			);
	
	// 攻略显示数目
	public static $REPLAY_NUM = 1;						
	public static $FIGHT_CD_TIME = 1;	
	//Memcache中attack_info 与   replay_info 的过期时间  设置为两个小时
	public static $MC_EXPIRE_TIME = 7200;	
	//记录据点首杀的个数
	public static $BASE_PRE_NUM = 1;
	//记录副本首杀的个数
	public static $COPY_PRE_NUM = 1;
	//第一个精英副本id，第一个精英副本开启的时候就可以攻击   
	//其他精英副本可显示是由普通副本是否通关决定  而可攻击由精英副本是否通关决定
	public static $FIRST_ELITE_COPY_ID = 200001;
	public static $FIRST_NORMAL_COPY_ID = 1;
	//更新精英副本攻击次数的间隔时间    一般是一天
	public static $REFRESH_ELITE_TIME_GAP = 100;	
	public static $CHALLANGE_TIMES = 3;						// 每天刷新的挑战次数
	public static $GOLD_TREE_DEFEATNUM_REFRESH_TIME = 0;//摇钱树每天更新挑战次数的时间是0点
	public static $REFRESH_TIME = 0;					// 每天的刷新后开始时刻
	public static $ELITE_COPY_MAX_TIME = 25;				//精英副本最大挑战次数
	public static $BASE_DEFEAT_NUM_REFRESH_TIME = 0;//每天更新据点攻击次数的时间是0点
	
	
	public static $MAX_REVIVE_TIME = 10;        //复活次数限制
	public static $REVIVE_SPEND = 200; 		//单次复活费用
	public static $REVIVE_SPEND_INC = 200;  //复活消费增长
	
	public static $RESET_ATK_NUM_INIT_GOLD = 20;
	public static $RESET_ATK_NUM_GOLD_INC = 10;
	
	public static $COPY_NUM_IN_SESSION = 10;
	
	public static $SWEEP_GAP_TIME = 60;//扫荡间隔时间是一个小时
	
	public static $USER_LEVEL_CAN_SWEEP = 10;//30级才能扫荡
	
	public static $MAX_SWEEP_NUM = 20;//一个请求内最大扫荡次数
	
	public static $HURT_SILVER_RATIO = 10;//获得银币与伤害的比例  银币=伤害/10
	
}

class ACopyConf
{
    public static $HURT_TO_ADDTIONAL_SILVER = array(
            5000 =>  0,
            10000=>  15000,
            20000=>  35000,
            50000=>  55000,
            100000=> 85000,
            200000=> 135000,
            300000=> 180000,
            400000=> 235000,
            500000=> 285000,
            600000=> 335000,
            800000=> 385000,
            1000000=>435000,
            PHP_INT_MAX=>485000,
            );
}
/**
1.普通副本：
	副本的开启：1.通关前置副本 并且 2.玩家到达某个等级
	getCopyList会使用当前所有的副本checkOpenNewCopy
	doBattle如果导致副本通关，会使用此副本进行checkOpenByBasePass
	****************玩家升级不能保证及时开启普通副本********************
2.精英副本：
	副本的显示状态的开启：1、前置精英副本的可攻击状态开启；或者 2、前置普通副本通关
	副本的可攻击状态的开启：1、前置精英副本通关；并且 2、前置普通副本通关
	getEliteCopyInfo时会checkOpenNew（根据可攻击状态副本开启后置副本的显示状态，根据通关副本开启后置精英副本的可攻击状态）
	doBattle 会checkOpenNew
3.活动副本：
	副本的开启：1.据点（可能为普通或者精英）通关；并且 2.玩家到达某个等级
	acopy.getCopyList checkOpenNew
	acopy.atkActBase checkOpenNew
	************************玩家升级不能保证及时开启活动副本***********************************
 */