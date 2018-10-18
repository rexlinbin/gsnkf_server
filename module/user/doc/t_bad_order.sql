
set names utf8;
create table if not exists t_bad_order(
	order_id 			varchar(80) not null comment '订单id',
	uid 				bigint unsigned not null comment 'uid',
	gold_num 			int unsigned not null comment '金币数量',
	sub_num				int unsigned not null comment '需要扣的金币',
	set_time 			int unsigned not null comment '最后修改时间',
	status 				int unsigned not null comment '状态 1：有效， 2：忽略  ',
	primary key(order_id),
	index uid(uid)
)engine = InnoDb default charset utf8;