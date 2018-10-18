set names utf8;
create table if not exists t_elite_copy(
	uid int unsigned not null comment "用户id",	
	last_defeat_time int unsigned not null comment "上一次攻击此副本的时间",
	can_defeat_num tinyint unsigned not null comment "攻击次数",
	buy_atk_num int unsigned not null comment "购买攻击次数的次数",
    va_copy_info blob not null comment "记录精英副本模块中副本的攻击状态，副本有三个状态0可显示 1可攻击 2已通关",	
	status tinyint unsigned not null comment "数据是否已经被删除， 0 被删除， 1 正常",
	primary key(uid)
)default charset utf8 engine = InnoDb;

#va_copy_info:array('progress'=>array(copyid=>copy_status),......)