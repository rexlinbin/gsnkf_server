
CREATE TABLE IF NOT EXISTS t_pay_back_info( 
    payback_id int unsigned  not null comment '补偿id， 由dataproxy生成，< 一百万，与rid不重复',
    time_start int unsigned not null default 0 comment  '赔偿的开始时间',
	time_end   int unsigned not null default 0 comment  '赔偿的结束时间',
	isopen tinyint unsigned not null comment '赔偿功能是否开启， 0 关闭， 1 开启',
	va_payback_info blob not null  comment '具体补偿内容 array(type=>,message=>,silver=>, experience=>, soul=>,gold=>,execution=>,stamina =>, item_id=>,item_num=>)',
   	primary key(payback_id),
	unique index time_start_time_end(time_start, time_end)
)default charset utf8 engine = InnoDb;