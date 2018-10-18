set names utf8;
create table if not exists t_normal_sign(

	uid 					int unsigned not null comment '用户uid',
	sign_time 				int unsigned not null comment '最近一次签到时间 也就是领奖时间',
	sign_num 				int unsigned not null comment '总的签到次数也就是奖励已经领取的次数',
	
	primary key(uid)
)engine = InnoDb default charset utf8;