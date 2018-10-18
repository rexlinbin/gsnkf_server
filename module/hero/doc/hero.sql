set names utf8;

CREATE TABLE IF NOT EXISTS t_hero(
	hid 			int unsigned not null comment 'hero id',
	htid 			int unsigned not null comment 'hero 模版id',
	uid 			int unsigned not null comment 'hero属于用户id',
	
	soul 			int unsigned not null comment '总将魂数',
	level 			smallint unsigned not null comment '英雄等级',
	destiny 		int unsigned not null comment '天命数',
	evolve_level	smallint unsigned not null comment '武将进化次数',
	upgrade_time 	int unsigned not null comment '最后一次升级时间',
	delete_time 	int unsigned not null comment '武将被删除（卖出）的时间',
		
	va_hero blob not null comment 'convert_from => array() 记录该hero进阶过程
					arming=>array()	武装
					skillBook=>array() 技能书
					dress => array()
					treasure => array()
					fightSoul=>array()
					figure=>array()
					pill=>array(
					  $index => array[$itemTplId => num,...]
					) 丹药
				 ',
	
	primary key(hid),
	index uid_delete_time(uid,delete_time),
	index htid_uid(htid,uid)
)engine = InnoDb default charset utf8;
