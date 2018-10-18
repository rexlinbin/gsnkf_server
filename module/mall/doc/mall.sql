set names utf8;

create table if not exists t_mall 
(
	uid int unsigned not null comment '用户id',
	mall_type int unsigned not null comment '商城类型',
	va_mall blob not null comment '兑换的详细信息，all => array{$exchangeId(兑换id) => array{$num(兑换次数), $time(兑换时间)}}',
	primary key(uid, mall_type)
)engine = InnoDb default charset utf8;