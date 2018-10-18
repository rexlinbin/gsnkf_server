

CREATE TABLE IF NOT EXISTS `t_mail_0`(
	mid int unsigned not null comment '邮件id',
	mail_type tinyint unsigned not null comment '邮件类型，1表示玩家邮件，2表示系统邮件，3表示系统物品，4表示战报',
	sender_uid int unsigned not null comment '邮件发送者，0表示系统邮件',
	reciever_uid int unsigned not null comment '邮件接收者',
	template_id int unsigned not null comment '邮件模板id',
	subject varchar(32) not null comment '邮件标题',
	content varchar(512) not null comment '邮件内容',
	read_time int unsigned not null comment '阅读时间，0表示未阅读',
	recv_time int unsigned not null comment '接收时间',
	va_extra blob not null comment '一些额外信息',
	deleted int unsigned not null comment '是否删除',
	primary key(mid),
	index reciever_uid_mail_type_recv_time(reciever_uid, mail_type, recv_time),
	index recv_time(recv_time)
)engine = InnoDb default charset utf8;



CREATE TABLE IF NOT EXISTS `t_mail_1` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_2` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_3` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_4` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_5` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_6` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_7` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_8` LIKE `t_mail_0`;



CREATE TABLE IF NOT EXISTS `t_mail_9` LIKE `t_mail_0`;

