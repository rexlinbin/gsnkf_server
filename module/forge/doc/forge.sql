set names utf8;

create table if not exists t_forge 
(
	uid 			int unsigned not null comment '用户id',
	transfer_num	int unsigned not null comment '潜能转移次数',
	transfer_time	int unsigned not null comment '潜能转移重置时间',
	primary key(uid)
)default charset utf8 engine = InnoDb;