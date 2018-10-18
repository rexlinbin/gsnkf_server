set names utf8;
create table if not exists t_tower(
	uid int unsigned not null comment "用户id",
	max_level int unsigned not null comment "当前爬塔最高级别 为了排名使用",
	max_level_time int unsigned not null comment "最高塔层开启时间",
	cur_level int unsigned not null comment "当前塔层",
	last_refresh_time int unsigned not null comment "上一次挑战的时间",
	reset_num tinyint unsigned not null comment "重置次数",
	can_fail_num tinyint unsigned not null comment "可以挑战失败次数",
	gold_buy_num tinyint unsigned not null comment "使用金币购买挑战失败的次数",
	buy_atk_num	tinyint unsigned not null comment "购买攻击次数",
	buy_special_num tinyint unsigned not null comment "购买神秘塔层次数",
	max_hell int unsigned not null comment "试炼噩梦的最高级别",
	cur_hell int unsigned not null comment "试炼噩梦的当前级别",
	reset_hell int unsigned not null comment "重置试炼噩梦的次数",
	can_fail_hell int unsigned not null comment "试炼噩梦的可失败次数",
	gold_buy_hell int unsigned not null comment "金币购买试炼噩梦的挑战失败次数",
	buy_hell_num  int unsigned not null comment "购买试炼噩梦的攻打次数",
	va_tower_info blob not null comment "爬塔系统的详细信息包括攻击塔层的进度",	
	primary key(uid)
)default charset utf8 engine = InnoDb;

#va_tower_info = array('progress'=>array(level=>level_status,'cur_level'=>level)
#level_status:0show 1attack 2pass（ 3通关两次 4通关三次。。。。现在pass的最大值是2，还没有用到其他的值，其他的值与last_pass_time联合使用，为了兼容，现在都没有使用）
#每次购买挑战次数的时候更新last_defeat_time
