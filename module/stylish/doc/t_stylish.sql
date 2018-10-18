set names utf8;
create table if not exists t_stylish(
	uid				int unsigned not null comment '用户id',
	va_title blob not null comment '用户已激活的称号,title{$id=>{$num(次数), $time(激活时间)}',
	primary key(uid)
)engine = InnoDb default charset utf8;