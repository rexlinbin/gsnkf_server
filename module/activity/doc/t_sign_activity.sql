set names utf8;
create table if not exists t_sign_activity(

	uid 						int unsigned not null comment '用户id',
	acti_sign_time 				int unsigned not null comment '最近一次活动签到时间',
	acti_sign_num 				int unsigned not null comment '签到天数',
	va_acti_sign			    blob not null comment 'array ( 1,2,3,4,6,5.... )已经领取的累积签到奖励',
	
	primary key(uid)
)engine = InnoDb default charset utf8;