set names utf8;

create table if not exists t_treas_book(
	uid 			int unsigned not null comment 'treas属于用户id',
	va_book 		blob not null comment 'treas=>array(itemTplId1,itemTplId2)',
	primary key(uid)
)engine = InnoDb default charset utf8;