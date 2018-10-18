set names utf8;

create table if not exists t_god_weapon_book(
	uid 			int unsigned not null comment 'godweapon属于用户id',
	va_book 		blob not null comment 'godweapon=>array(itemTplId1,itemTplId2)',
	primary key(uid)
)engine = InnoDb default charset utf8;