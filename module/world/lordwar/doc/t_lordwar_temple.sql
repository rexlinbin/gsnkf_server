
set names utf8;

CREATE TABLE IF NOT EXISTS t_lordwar_temple(
    sess 		int unsigned not null comment "第几届,本表只保存一届的",
    va_temple		blob not null comment '三条信息',
    primary key(sess)
)default charset utf8 engine = InnoDb;