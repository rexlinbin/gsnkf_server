set names utf8;
create table if not exists t_bbpay_gold(
	order_id 			varchar(80) not null comment '订单id',
	uid 				bigint unsigned not null comment 'uid',
	gold_num 			int unsigned not null comment '金币数量',
	gold_ext 			int unsigned not null comment '赠送的金币',
	status 				int unsigned not null comment '状态 1：成功  ',
	mtime 				int unsigned not null comment '最后修改时间',
	qid 				varchar(32) default '' comment 'qid, 运营商用户唯一标识',
	order_type 			int not null default 0 comment '订单类型， 0：普通订单， 1：在线赠送金币',
	level 				int unsigned not null default 0 comment '充值的时候此用户的等级',
	primary key(order_id),
	index uid(uid)
)engine = InnoDb default charset utf8;