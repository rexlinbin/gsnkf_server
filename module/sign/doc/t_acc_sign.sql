set names utf8;
create table if not exists t_acc_sign(

	uid 					int unsigned not null comment '用户id',
	sign_time 				int unsigned not null comment '最近一次签到时间',
	sign_num 				int unsigned not null comment '总的签到天数',
	va_sign			        blob not null comment 'array ( 1,2,3,4,6,5.... )已经领取的累积签到奖励',
	
	primary key(uid)
)engine = InnoDb default charset utf8;