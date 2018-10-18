set names utf8;

CREATE TABLE IF NOT EXISTS t_user
(
	uid 				int unsigned not null comment '用户id',
	pid 				int unsigned not null unique comment '玩家id',	
	uname 				varchar(16) not null unique comment '用户名字',
    utid 				int unsigned not null comment '用户模版id',
	status 				int unsigned not null comment '用户状态，0：deleted，1：online, 2：offline, 3:suspend ',
	create_time 		int unsigned not null comment '用户创建时间',
	last_login_time 	int unsigned not null comment '上次登录的时间',
	last_logoff_time 	int unsigned not null comment '上次离线的时间',
    online_accum_time 	int unsigned not null comment '在线累计时间',
    ban_chat_time 		int unsigned not null comment '禁言时间',
    mute 				int unsigned not null comment '是否静音, 0 不静音， 1 静音',

    level			int unsigned not null comment '等级',	
    upgrade_time    int unsigned not null default 0 comment '最后一次升级时间',
	vip 			int unsigned not null comment 'vip等级',
	
	master_hid 		int unsigned not null comment '主角英雄的hid',
	guild_id        int unsigned not null default 0 comment '公会/军团',
	
	
	gold_num 		int unsigned not null comment '金币RMB',
	silver_num 		int unsigned not null comment '银两',
	exp_num 		int unsigned not null comment '经验',
	soul_num		int unsigned not null comment '将魂数',
	jewel_num		int unsigned not null comment '魂玉数', 
	prestige_num	int unsigned not null comment '声望',
	tg_num          int unsigned not null comment '天工令',
	wm_num			int unsigned not null comment '威名',
	fame_num		int unsigned not null comment '名望',
	book_num		int unsigned not null comment '书',
	fs_exp   		int unsigned not null comment '战魂经验',
	jh              int unsigned not null comment '武将精华',
	tally_point		int unsigned not null comment '兵符积分',
	user_item_gold  int unsigned not null comment '使用特殊物品获得的金币记录一下，需要计算在vip经验中',
	tower_num       int unsigned not null comment '试炼币',
	
	execution 				int unsigned not null comment '当前行动力',
	execution_max_num		int unsigned not null comment '体力上限值',
	execution_time 			int unsigned not null comment '上次恢复行动力时间，创建用户时值为time()',
	buy_execution_time 		int unsigned not null comment '上次购买行动力时间',
	buy_execution_accum 	int unsigned not null comment '购买行动力累计（一天内）',
	
	stamina					int unsigned not null comment '当前耐力',
	stamina_max_num			int unsigned not null comment '耐力上限值',
	stamina_time 			int unsigned not null comment '上次恢复行耐力时间，创建用户时值为time()',
	buy_stamina_time 		int unsigned not null comment '上次恢复行耐力时间',
	buy_stamina_accum 		int unsigned not null comment '恢复行耐力累计（一天内）',
		
	fight_cdtime 			int unsigned not null comment '打架后冻结时间',
	fight_force				int unsigned not null comment '战斗力',
	max_fight_force			int unsigned not null default 0 comment '历史最大战斗力',
	
	figure					int unsigned not null comment '头像',
	title					int unsigned not null comment '称号',
	
	base_goldnum			int unsigned not null comment '创建账号时的假金币',
	
	va_hero blob not null comment 'unused=>array(hid=>array(htid=>int,level=>int))  所有没有使用过的武将', 
    va_user blob not null comment 
    	'spend_gold => array(ymd=>num) 存N天的金币花费  
		 wallow => array()  防沉迷相关
		 ban => array() 封号相关
		 va_config => array()整存整取的配置 
		 arr_config => array() 可以单独设置自动的配置 取是整取
		 dress	=>	array		主角时装形象
		 charge_info => array( gold => time ) 首充充值记录
		', 
	va_charge_info blob not null comment '首充重置充值记录 charge_id => time',
    	
	primary key(uid),
	index level(level),
	index exp_num(exp_num),
	index fight_force(fight_force)
)engine = InnoDb default charset utf8;


