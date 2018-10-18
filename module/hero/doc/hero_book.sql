set names utf8;

CREATE TABLE IF NOT EXISTS t_hero_book(
	uid 			int unsigned not null comment 'hero属于用户id',
	va_book 		blob not null comment 'hero=>array(htid1,htid2)',
	primary key(uid)
)engine = InnoDb default charset utf8;
