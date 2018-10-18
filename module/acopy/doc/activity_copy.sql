set names utf8;
create table if not exists t_activity_copy(
	uid int unsigned not null comment "用户id",
	copy_id int unsigned not null comment "活动id",
	last_defeat_time int unsigned not null comment "上次攻击时间",
	can_defeat_num tinyint not null comment "当前可以攻击的次数",
	buy_atk_num int unsigned not null comment "购买攻击次数的次数",
    va_copy_info blob not null comment "活动的其他信息",
    status tinyint unsigned not null comment "数据是否已经被删除， 0 被删除， 1 正常",
    primary key(uid, copy_id)
)default charset utf8 engine = InnoDb;
