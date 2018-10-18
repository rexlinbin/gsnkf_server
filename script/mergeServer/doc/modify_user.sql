alter table t_user add column server_id int unsigned not null comment 'server id' after pid;
alter table t_user drop index pid;
alter table t_user add unique key(pid, server_id);
