set names utf8;
create table if not exists t_user_world_0
(
        pid int unsigned not null comment 'pid',
        server_id int unsigned not null comment 'server_id',
        base_goldnum int unsigned not null comment '创建用户所需要参考的基础金币数量',

        primary key(pid,server_id)
)engine = InnoDb default charset utf8;

drop procedure if exists add_user_world;
delimiter //
create procedure add_user_world(begin_i int,table_num int)
begin
        declare i int;
        declare table_name varchar(50);
        set i = begin_i;
        while i <= table_num do
                set table_name = concat('t_user_world_',i);
                SET @STMT := CONCAT("CREATE TABLE IF NOT EXISTS ",table_name," LIKE t_user_world_0;");
                PREPARE STMT FROM @STMT;
                EXECUTE STMT;
                set i = i + 1;
        end while;
end //
delimiter ;

call add_user_world(1,200);