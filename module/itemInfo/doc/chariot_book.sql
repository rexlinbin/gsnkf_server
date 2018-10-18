set names utf8;
create table if not exists t_chariot_book(
	uid 			int unsigned not null comment '用户id',
	va_book 		blob not null comment 'chariot=>array(itemTplId1,itemTplId2)',
	primary key(uid)
)engine = InnoDb default charset utf8;