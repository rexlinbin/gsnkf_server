
CREATE TABLE IF NOT EXISTS t_pay_back_user( 
    uid int unsigned not null comment '玩家的 uid',
    payback_id  int unsigned not null default 0 comment  '关联表t_pay_back_info中的payback_id',
	time_execute int unsigned not null default 0 comment  '执行赔偿操作的时间',
    primary key(uid,payback_id)
)default charset utf8 engine = InnoDb;