set names utf8;

CREATE TABLE IF NOT EXISTS t_switch(
	uid int unsigned not null comment 'user id',
	group0 int unsigned not null comment '第一组功能节点，存储ID为1-25的功能节点的状态',
	group1 int unsigned not null comment '第二组功能节点，存储ID为26-50的功能节点的状',
	group2 int unsigned not null comment '第三组功能节点，存储ID为51-75的功能节点的状',
	primary key(uid)
)engine = InnoDb default charset utf8;