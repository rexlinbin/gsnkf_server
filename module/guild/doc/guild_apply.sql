set names utf8;

create table if not exists t_guild_apply
(
	uid 		int unsigned not null comment '用户id',
	guild_id 	int unsigned not null comment '军团id',
	apply_time 	int unsigned not null comment '申请时间',
	status 		tinyint unsigned not null comment '申请状态, 1成功, 2取消, 3拒绝, 4同意',
	primary key(uid, guild_id),
	key guild_id(guild_id)
)engine = InnoDb default charset utf8;