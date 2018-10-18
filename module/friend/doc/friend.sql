set names utf8;
CREATE TABLE IF NOT EXISTS t_friend(
	uid 		int unsigned not null comment '好友A的uid',
	fuid 		int unsigned not null comment '好友B的uid',
	status 		tinyint unsigned not null comment '好友关系状态，1表示断绝，2表示好友',
	alove_time 		int unsigned not null default 0 comment '好友B对A的体力赠送时间 default是必须的',
	blove_time 		int unsigned not null default 0 comment '好友A对B的体力赠送时间 default是必须的',
	
	reftime_apk 		int unsigned not null default 0 comment 'pk相关数据的刷新时间',
	reftime_bpk 		int unsigned not null default 0 comment 'pk相关数据的刷新时间',
	apk_num	int unsigned not null default 0 comment 'B对A的切磋次数',
	bpk_num	int unsigned not null default 0 comment 'A对B的切磋次数',
	
	primary key(uid, fuid),
	index fuid_uid(fuid, uid)
)engine = InnoDb default charset utf8;
