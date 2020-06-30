
delete from dicts where id > 41;
delete from dict_attributes where id > 41;

delete from layer_attributes where layer_id > 40015;
delete from layers where id > 40015;
delete from user_layers where layer_id > 40015;

drop table if exists dict_34;
drop table if exists layer_test;

