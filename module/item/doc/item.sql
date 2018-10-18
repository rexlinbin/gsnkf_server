
CREATE TABLE IF NOT EXISTS `t_item_0`
(
	`item_id`				int(10) unsigned not null comment '物品ID',
	`item_template_id`		int(10) unsigned not null default 0 comment '物品模板ID',
	`item_time`				int(10) unsigned not null default 0 comment '物品生成时间',
	`item_num`				int(10) unsigned not null default 0 comment '物品数量',
	`item_deltime`			int(10) unsigned not null default 0 comment '物品删除时间,0未删除',
	`va_item_text`			blob not null comment '物品附加信息, lock',
	primary key(`item_id`)
)default charset utf8 engine = InnoDb;



CREATE TABLE IF NOT EXISTS `t_item_1` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_2` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_3` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_4` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_5` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_6` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_7` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_8` LIKE `t_item_0`;



CREATE TABLE IF NOT EXISTS `t_item_9` LIKE `t_item_0`;

