SET NAMES UTF8;

CREATE TABLE IF NOT EXISTS t_boss_atk
(
	uid					int(10) unsigned not null comment 'uid',
	uname				varchar(16) not null comment '用户uname',
	boss_id				int(10) unsigned not null comment 'boss ID',
	last_attack_time	int(10) unsigned not null comment '上次攻击时间',
	attack_hp			int(10) unsigned not null comment '总共攻击的HP',
	attack_num			int(10) unsigned not null comment '总共攻击的次数',
	inspire_time_silver	int(10)	unsigned not null comment '上次鼓舞时间,银币',
	inspire_time_gold 	int(10)	unsigned not null comment '上次鼓舞时间,金币',
	inspire				int(10) unsigned not null comment '银币和金币的总鼓舞次数',
	revive				int(10) unsigned not null comment '复活次数',
	flags				int(10) unsigned not null comment '标志量，0x0001表示本次攻击已经减少过cd',
	formation_switch	int(10) unsigned not null comment 'boss阵型开关 0 是关, 1是开',
	va_boss_atk			blob not null comment 'array(formation => array (bossId => array()) )',
	
	primary key( uid, boss_id),
	key attack_hp(attack_hp),
	key last_attack_time(last_attack_time)
)default charset utf8 engine = InnoDb;