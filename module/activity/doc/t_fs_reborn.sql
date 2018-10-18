set names utf8;
create table if not exists t_fs_reborn
(
	uid int unsigned not null comment '用户id',
	num int unsigned not null comment '次数',
	refresh_time int unsigned not null comment '刷新时间',
	primary key(uid)
)engine = InnoDb default charset utf8;