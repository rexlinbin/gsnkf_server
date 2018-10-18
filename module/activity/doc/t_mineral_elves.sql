CREATE TABLE IF NOT EXISTS `t_mineral_elves`
(
	`domain_id`						int(10) unsigned not null comment '资源区ID',
	`uid`							int(10) unsigned not null comment '用户ID',
	`start_time`   int(10) unsigned not null comment '开始时间',
	`end_time`    int(10) unsigned not null comment '结束时间',
	primary key(`domain_id`),
	key uid(`uid`)
)default charset utf8 engine = InnoDb;