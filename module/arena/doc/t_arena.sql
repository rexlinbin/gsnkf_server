set names utf8;

create table if not exists t_arena
(
	uid 					int unsigned not null comment '用户id',
	position 				int unsigned not null comment '位置排名',
	challenge_num 			int unsigned not null comment '当天剩余的挑战次数， 每天0点重置',
	challenge_time 			int unsigned not null comment '上次挑战时间',
	cur_suc 				int unsigned not null comment '当前连胜场次',
	max_suc 				int unsigned not null comment '历史最大连胜场次',
	min_position 			int unsigned not null comment '历史最小（好）的排名',
	upgrade_continue 		int unsigned not null comment '连续上升了多少名',
	va_opponents 			blob not null comment 'array(10) 对手的位置排名',
	reward_time 			int unsigned not null comment '发奖励时间， 用来做重做的标记。也用来做领奖的时候做判断。设置的发奖竞技场锁定时间',
	va_reward				blob not null comment '奖励：array(soul=>, silver=>, items=>, his=>array(date(日期)=>array(pos(排名),status(状态:0无1未发2已发))))',
	primary key(uid),
	unique index `position`(`position`)
)engine = InnoDb default charset utf8;