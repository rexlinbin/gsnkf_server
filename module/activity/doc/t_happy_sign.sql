set names utf8;
create table if not exists t_happy_sign(
	uid 					int unsigned not null comment '用户uid',
	sign_time 				int unsigned not null comment '当天第一次登陆的时间,即签到时间',
	login_num 				int unsigned not null comment '活动期间内总的登录天数',
	va_reward			    blob not null comment '已经领取过的奖励id信息, array(rewardId => selectId)',
	primary key(uid)
)engine = InnoDb default charset utf8;