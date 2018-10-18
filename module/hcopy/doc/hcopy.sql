create table if not exists t_hcopy
(
	uid 		int unsigned not null comment '用户id',
	copyid 	int unsigned not null comment '副本id',
	level 	int unsigned not null comment '副本难度',
	finish_num 		int unsigned not null comment '完成次数',
	va_copy_info blob not null comment '进度',
	primary key(uid, copyid, level)
)engine = InnoDb default charset utf8;