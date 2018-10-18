set names utf8;
create table if not exists t_moon
(
	uid int unsigned not null comment '玩家uid，主键',
	atk_num int unsigned not null comment '今天玩家的攻击次数',
	buy_num int unsigned not null comment '今天玩家的购买的攻击boss次数',
	nightmare_atk_num int unsigned not null comment '今天玩家已攻打梦魇次数',
	nightmare_buy_num int unsigned not null comment '今天玩家的购买的攻击梦魇boss次数',
	box_num int unsigned not null comment '今天玩家的购买的开宝箱次数',
	max_pass_copy int unsigned not null comment '玩家最大的通关的副本Id',
	max_nightmare_pass_copy int unsigned not null comment '玩家最大的通关梦魇的bossId',
	update_time int unsigned not null comment '更新时间',
	va_extra blob not null comment '
                                   grid => array                      当前攻打的副本的九宫格信息,1-9代表9个格子信息
                                   [
                                       index => status                index取值1-9,status取值1-3分别代表 锁定/解锁/已攻打或者已领取						
                                   ]
                                   ',
	primary key(uid)
)engine = InnoDb default charset utf8;