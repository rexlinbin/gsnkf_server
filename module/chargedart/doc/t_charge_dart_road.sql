set names utf8;

create table if not exists t_charge_dart_road
(
	stage_id int unsigned not null comment '区域id',
	page_id int unsigned not null comment '页id',
	road_id int unsigned not null comment '道路id',
	previous_time int unsigned not null comment '最后在这条路上出发的镖车的出发时间',
	primary key(stage_id,page_id,road_id)
)default charset utf8 engine = InnoDb;