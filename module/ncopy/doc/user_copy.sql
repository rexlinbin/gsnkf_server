set names utf8;
create table if not exists t_user_copy(
	uid int unsigned not null comment '用户id',
	copy_id int unsigned not null comment '此用户最远副本的ID',
	last_copy_time int unsigned not null comment '最远副本的开启时间',
	score int unsigned not null comment '副本得分',		
	last_score_time int unsigned not null comment '最近得星的时间',
	sweep_cd int unsigned not null comment '扫荡冷却时间',
	clear_sweep_num int unsigned not null comment '消除扫荡的次数',
	last_rfr_time int unsigned not null comment '刷新用户副本的时间',
	primary key(uid)
)engine = InnoDb default charset utf8;