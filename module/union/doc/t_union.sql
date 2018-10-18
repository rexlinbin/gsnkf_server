set names utf8;

create table if not exists t_union
(
	uid int unsigned not null comment '用户id',
	va_fate blob not null comment '缘分堂,list=>{1=>{$htid,$htid,$htid}}',
	va_loyal blob not null comment '忠义堂,list=>{1=>{$htid,$htid,$htid}}',
	va_martial blob not null comment '演武堂,list=>{1=>{$htid,$htid,$htid}}',
	primary key(uid)
)default charset utf8 engine = InnoDb;