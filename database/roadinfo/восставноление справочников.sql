SELECT * FROM amstrad_roadinfo.layers;

select * from layer_attributes where layer_id in (176, 30501, 325);


select distinct table_ref_name from layer_attributes where table_ref_name is not null and table_ref_name not in (select table_name from dicts)

select * from dicts where table_name in ('dgDict_539_540_541','dgDict_542_543_544','dgDict_545_546_547','dgDict_548_549_550')

dgDict_591_592_593
dgDict_156_157_158
dgDict_539_540_541


select * from dict_attributes where dict_id not in (select id from dicts)


select * from dgDict_539_540_541

select * from dict_attributes where dict_id = 16

insert into dicts(table_name, name) values('dgDict_548_549_550', 'dgDict_548_549_550');
select id from dicts where table_name = 'dgDict_539_540_541';

insert into dict_attributes(dict_id, field_name, display_name, type_name, table_ref_name, isHidden, isDisabled, maxLength, isRequired)
select 19 as dict_id, field_name, display_name, type_name, table_ref_name, isHidden, isDisabled, maxLength, isRequired from dict_attributes where dict_id = 9;