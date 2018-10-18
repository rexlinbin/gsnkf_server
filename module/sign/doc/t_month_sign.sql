set names utf8;
create table if not exists t_month_sign(

	uid 					int unsigned not null comment '用户uid',
	sign_time 				int unsigned not null comment '最近一次签到时间',
	reward_vip				int unsigned not null comment '今天领奖时的vip',
	sign_num 				int unsigned not null comment '本月总的签到次数',
	
	primary key(uid)
)engine = InnoDb default charset utf8;