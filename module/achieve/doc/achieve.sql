CREATE TABLE IF NOT EXISTS `t_achieve` 
(
	`aid` int unsigned not null comment '成就ID',
	`uid` int(10) unsigned not null comment '用户ID',
	`status` int(8) unsigned not null comment '状态',
	`finish_num` int unsigned not null comment '完成次数',
	`va_data` blob comment '数据' not null,
	primary key(`uid`, `aid`)
)default charset utf8 engine = InnoDb;

