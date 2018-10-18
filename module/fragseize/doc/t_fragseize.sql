
set names utf8;

CREATE TABLE IF NOT EXISTS t_fragseize(

	uid 				int unsigned not null comment 'uid',
	frag_id 			int unsigned not null comment '碎片模板Id',
	frag_num 			int unsigned not null default 0 comment '碎片数量',
	seize_num			int unsigned not null default 0 comment '连续多少次都没有夺到这个碎片了，default是必须的',

	primary key(uid, frag_id),
    index frag_id_num(frag_id, frag_num)
)engine = InnoDb default charset utf8;
