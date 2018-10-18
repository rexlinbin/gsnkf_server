set names utf8;
create table if not exists t_world_compete_cross_user_1
(
    team_id int unsigned not null comment "分组id",
	pid int unsigned not null comment "玩家pid",
	server_id int unsigned not null comment "服server_id",
	uid int unsigned not null comment "玩家uid",
	uname varchar(16) not null comment "玩家名称",
	vip	int unsigned not null comment "玩家vip",
	level int unsigned not null comment "玩家等级",
	htid int unsigned not null comment "玩家htid",
	title int unsigned not null comment "玩家title",
	fight_force int unsigned not null comment "战斗力",
	max_honor int unsigned not null comment "玩家的荣誉，用于跨服排行",
	update_time int unsigned not null comment "更新时间",
	va_extra blob not null comment "扩展信息dress = array()",
	va_battle_formation blob not null comment "战斗信息formation",
    primary key(team_id, pid, server_id),
    index max_honor(max_honor, update_time)
)default charset utf8 engine = InnoDb;

drop procedure if exists add_compete_cross;
delimiter //
create procedure add_compete_cross(begin_i int,table_num int)
begin
        declare i int;
        declare table_name varchar(50);
        set i = begin_i;
        while i <= table_num do
                set table_name = concat('t_world_compete_cross_user_',i);
                SET @STMT := CONCAT("CREATE TABLE IF NOT EXISTS ",table_name," LIKE t_world_compete_cross_user_1;");
                PREPARE STMT FROM @STMT;
                EXECUTE STMT;
                set i = i + 1;
        end while;
end //
delimiter ;
call add_compete_cross(2,300);
drop procedure if exists add_compete_cross;