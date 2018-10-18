set names utf8;

create table if not exists t_shop
(
	uid 		  			int unsigned not null comment '用户id',
	point					int unsigned not null comment '积分',
	bronze_recruit_num		int unsigned not null comment '青铜招将累积次数',
	silver_recruit_num		int unsigned not null comment '白银招将累积次数',
	silver_recruit_time 	int unsigned not null comment '白银招将冷却时间',
	silver_recruit_status	tinyint unsigned not null comment '白银首刷的状态，0免费和金币都未使用，1免费使用但金币未使用，2金币使用但免费未使用，3免费金币都使用',
	gold_recruit_num		int unsigned not null comment '黄金招将累积次数',
	gold_recruit_time  	 	int unsigned not null comment '黄金招将冷却时间',
	gold_recruit_status		tinyint unsigned not null comment '黄金首刷的状态，0免费和金币都未使用，1免费使用但金币未使用，2金币使用但免费未使用，3免费金币都使用',
	va_shop 				blob not null comment '已购买vip礼包的标志数组，vip_gift => array(1,2,3,...), ten => $timestamp首次十连抽的时间',
	primary key(uid)
)default charset utf8 engine = InnoDb;