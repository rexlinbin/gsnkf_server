set names utf8;

create table t_hunt(
	uid int unsigned not null comment 'uid',	
	place int unsigned not null comment '当前场景id',
	point int unsigned not null comment '总积分值',
	va_hunt blob not null comment 'array(all($id(场景)=>$count(探索次数)), change(变更次数))',
	primary key(uid)
)engine = InnoDb default charset utf8;
