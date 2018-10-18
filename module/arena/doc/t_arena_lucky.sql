set names utf8;

create table if not exists t_arena_lucky
(
	begin_date 	int unsigned not null comment '开始日期 20110918',
	active_rate int unsigned not null comment '竞技场奖励倍数',
	va_lucky 	blob not null comment 'array(array("positoin"=>pos, "gold"=>num, "uid"=>uid, "utid"=>utid, "uname"=>uname)))',
	primary key(begin_date)
)engine = InnoDb default charset utf8;