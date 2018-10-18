set names utf8;

create table if not exists t_arm_book(
	uid 			int unsigned not null comment 'arm属于用户id',
	va_book 		blob not null comment 'arm=>array(itemTplId1,itemTplId2)',
	primary key(uid)
)engine = InnoDb default charset utf8;