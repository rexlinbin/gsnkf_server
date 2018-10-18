set names utf8;

create table if not exists t_all_star(
	uid 	 	 int unsigned not null  comment '用户id',
	send_num  	 int unsigned not null  comment '每天使用的金币赠送次数',
	send_time    int unsigned not null  comment '上次刷新时间',
	draw_num	 int unsigned not null  comment '每天使用的翻牌次数',
	va_act_info  blob not null comment '所有行为信息:{act{actId=>actNum}, draw{sid{0(花型),1-5(牌id)}}}, skill{sid}',
    primary key(uid)
)engine = InnoDb default charset utf8;