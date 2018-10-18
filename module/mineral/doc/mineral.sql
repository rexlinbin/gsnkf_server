CREATE TABLE IF NOT EXISTS `t_mineral`
(
	`domain_id`						int(10) unsigned not null comment '资源区ID',
	`pit_id`						int(10) unsigned not null comment '矿坑ID',
	`domain_type`					tinyint unsigned not null comment '资源区，有高级和普通',
	`pit_type`                      tinyint unsigned not null comment '矿坑，有金银铜',
	`uid`							int(10) unsigned not null comment '用户ID',
	`occupy_time`					int(10) unsigned not null comment '占领时间',
	`due_timer`						int(10) unsigned not null comment '到期的timer',
	`delay_times`         tinyint unsigned not null comment '资源矿延时次数',
	`total_guards_time`             int(10) unsigned not null comment '协助军协助时间总和',
	`guildId`         int(10) unsigned not null comment '军团ID',
	`va_info`    blob not null comment 'array(guild_info=>array(guildid=>array(0=>starttime,1=>endtime)))',
	primary key(`domain_id`, `pit_id`),
	key uid(`uid`)
)default charset utf8 engine = InnoDb;

#domain_id与配置表中的第一个字段《资源ID》相对应             而不是前端的分页
#uid=0表示没有用户占领此矿坑
#domain_type:1是高级矿，2是普通矿