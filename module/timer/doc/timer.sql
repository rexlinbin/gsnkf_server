

CREATE TABLE IF NOT EXISTS  t_timer(
	tid int unsigned not null comment '任务id',
	uid int unsigned not null comment '任务用户uid',
	status tinyint unsigned not null comment '状态',
	execute_count tinyint unsigned not null comment '执行次数',
	execute_time int unsigned not null comment '执行时间',
	execute_method varchar(64) not null comment '执行函数',
	va_args blob not null comment '回调参数',
	primary key(tid),
	index status_execute_time(status, execute_time)
)engine = InnoDb default charset utf8;

#const UNDO = 1;const FINISH = 2;const FAILED = 3;const RETRY = 4;const CANCEL = 5;