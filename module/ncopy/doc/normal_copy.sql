set names utf8;
create table if not exists t_copy(
	uid int unsigned not null comment "用户id",
	copy_id int unsigned not null comment "副本ID",
	score tinyint unsigned not null comment "当前副本得分",
    prized_num tinyint unsigned not null default '0' comment "已经领取的奖励个数",
    refresh_atknum_time int unsigned not null comment "更新此副本攻击次数的时间",
    va_copy_info blob not null comment "副本的攻击进度和据点攻击次数
	'progress'=>array('base_id'=>'base_status'),
	'defeat_num'=>array('base_id'=>defeat_num),
	'reset_atknum_times=>array('base_id'=>reset_num),'",	
	status tinyint unsigned not null comment "数据是否已经被删除， 0 被删除， 1 正常",
	primary key(uid, copy_id)
)default charset utf8 engine = InnoDb;

#base_status的取值：0可显示 1可攻击 2npc通关 3简单通关 4普通通关 5困难通关
#got_status的取值：位存储    第一个二进制位标识简单难度的领取状态  第二个二进制位标识普通难度的领取状态 第三个二进制位标识困难难度的领取状态