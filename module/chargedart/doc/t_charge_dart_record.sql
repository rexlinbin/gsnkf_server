set names utf8;

create table if not exists t_charge_dart_record
(
	record_id int unsigned not null comment '记录id',
	stage_id int unsigned not null comment '区域id',
	uid int unsigned not null comment '动作者',
	time int unsigned not null comment '动作发生的时间',
	be_uid int unsigned not null comment '被动作者',
	be_robbed_num int unsigned not null comment '被抢次数',
	type int unsigned not null comment '动作类型',
	success int unsigned not null comment '动作成功与否',
	va_info blob not null comment "战报之类的
		array('brid1'=>brid1,
			  'brid2'=>brid2)",

    primary key(record_id),
	index(time)
)default charset utf8 engine = InnoDb;