set names utf8;
create table if not exists t_travel_shop
(
	id int unsigned not null default 1 comment 'id',
	sum int unsigned not null comment '总次数',
	refresh_time int unsigned not null comment '刷新时间',
	primary key(id)
)engine = InnoDb default charset utf8;