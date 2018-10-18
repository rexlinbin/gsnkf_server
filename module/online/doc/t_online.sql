set names utf8;

CREATE TABLE IF NOT EXISTS t_online(
	uid 				int unsigned not null comment 'uid',
	step 				int unsigned not null comment '完成到哪一步(完成了几步 初始为0)',
	begin_time 			int unsigned not null comment '开始计时时间',
	end_time 			int unsigned not null comment '结束计时时间',
	accumulate_time 	int unsigned not null comment '累计时间',
	primary key( uid )
)engine = InnoDb default charset utf8;