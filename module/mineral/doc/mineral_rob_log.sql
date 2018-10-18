set names utf8;

create table if not exists t_mineral_roblog
(
	id int unsigned not null comment 'ID',	
	va_log blob not null comment 'array(array(rob_time,old_capture,new_capture,domain_id,pit_id)',
	primary key(id)
)engine = InnoDb default charset utf8;
