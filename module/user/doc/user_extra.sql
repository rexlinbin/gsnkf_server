set names utf8;
create table if not exists t_user_extra(
	uid int unsigned not null comment '用户id',
	execution_time int unsigned not null comment '最后一次领取体力次数',
	last_share_time	int unsigned not null comment '最后一次分享时间',
	open_gold_num int unsigned not null comment '开金箱子的次数',
	va_user blob not null comment '非经常使用的用户信息',
	primary key(uid)
)engine = InnoDb default charset utf8;