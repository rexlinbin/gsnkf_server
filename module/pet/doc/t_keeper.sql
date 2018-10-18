set names utf8;

CREATE TABLE IF NOT EXISTS t_keeper
(	
	uid						int(10) unsigned not null comment '用户ID',
	keeper_slot				int(10) unsigned not null comment '宠物仓库栏位',
	pet_fightforce			int(10) unsigned not null default 0  comment '上阵宠物战斗力，排行榜使用',
	va_keeper 				blob not null comment 'setpet => array(0 => array(petid => int, status => int[0未出战1已出战], producttime => int))',
	primary key(uid ),
	index pet_fightforce(pet_fightforce)
)default charset utf8 engine = InnoDb;