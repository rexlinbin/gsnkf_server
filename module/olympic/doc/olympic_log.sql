create table t_olympic_log( 
    date_ymd int unsigned not null comment "年月日",
    log_type int unsigned not null comment "擂台赛记录类型",
    va_log_info blob not null comment '当前赛况信息: progress => array(stage => array(status, begintime, endtime)) || atkres => array(atker, defer, brid, res)',
    primary key(date_ymd, log_type)
)default charset utf8 engine = InnoDb;


#log_type:1.预选赛战报  3.16强战报  4.8强战报 5.4强战报  6.2强战报  7.冠军赛战报 8.比赛进度 9.奖池信息
#比赛进度信息存储哪些阶段的执行进度：1.16强赛  2.8强赛  3.4强赛  4.助威阶段  5.半决赛  6.决赛