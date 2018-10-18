set names utf8;

create table if not exists t_tally_book(
	uid 			int unsigned not null comment 'tally属于用户id',
	va_book 		blob not null comment 'tally=>array(itemTplId1,itemTplId2)',
	primary key(uid)
)engine = InnoDb default charset utf8;