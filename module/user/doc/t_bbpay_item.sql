set names utf8;
create table if not exists t_bbpay_item(
	order_id varchar(80) not null comment '订单id',
	uid int unsigned not null comment 'uid',
	item_type  int unsigned not null comment '商品类型',
	item_tpl_id int unsigned not null comment '商品id',
	item_num int unsigned not null comment '商品数量',
	gold_num int unsigned not null comment '花费金币数目',
	status int unsigned not null comment '状态 1：成功  ',
	mtime int unsigned not null comment '最后修改时间',
	primary key(order_id),
	index(uid)
)engine = InnoDb default charset utf8;