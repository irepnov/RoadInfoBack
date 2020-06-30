select * from [dbo].[dgLayers] where layer_id = 325

----------- 1. справочники !!!!!!!!!!!!!!!!
select * 
from [dbo].[dgLayerAttributes] 
where [layer_id] = 325
  and sp_id is not null

select distinct 'dgDict_' + cast(sp_id as varchar) + '_' + cast(sp_key as varchar) + '_' + cast(sp_value as varchar) from [dbo].[dgLayerAttributes] where [layer_id] = 325 and sp_id is not null

select * from dict.dgDict_539_540_541
select max(len(code)), max(len(name)) from dict.dgDict_542_543_544

select 'insert into dgDict_539_540_541(code, name, created_at) values("' + trim(convert(nvarchar(50), code)) + '", "' + trim(name) + '", NOW());' from dict.dgDict_539_540_541 order by code


------------- 1. справочникик, результат MySql script!!!!
select @dict_name := 'dgDict_539_540_541';
delete from dicts where table_name = @dict_name;
insert into dicts(table_name, name) values(@dict_name, concat('справочник ', @dict_name));   
select @dict_id := id from dicts where table_name = @dict_name;
-- подставить длину поля
delete from dict_attributes where dict_id = @dict_id;
insert into dict_attributes(dict_id, field_name, display_name, type_name, isHidden, maxLength) values(@dict_id, 'id', 'ИД', 10, 1, NULL);
insert into dict_attributes(dict_id, field_name, display_name, type_name, isHidden, maxLength) values(@dict_id, 'code', 'Код', 9, 0, 1);   
insert into dict_attributes(dict_id, field_name, display_name, type_name, isHidden, maxLength) values(@dict_id, 'name', 'Наименование', 9, 0, 6);



-- 2. подставить длину поля
drop table if exists dgDict_539_540_541;
create table dgDict_539_540_541
(
   id int not null auto_increment,
   code varchar(1) not null,
   name varchar(6) not null,
   `created_at` DATETIME NULL DEFAULT NULL,
   `updated_at` DATETIME NULL DEFAULT NULL,
   `deleted_at` DATETIME NULL DEFAULT NULL,
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (code),
   unique key id_unique3 (name)
) engine=innodb;



---- 3. данные
insert into dgDict_539_540_541(code, name, created_at) values("1", "Слева", NOW());
insert into dgDict_539_540_541(code, name, created_at) values("2", "Справа", NOW());





----------  2. данные
truncate table dgStructToMySQL_2

insert into dgStructToMySQL_2
select layer_id, 
	   1 as type_layer, 
	   replace(fieldname, 'road_code', 'object_id') as field_name, 
	   displayname as display_name, 
	   ptype as type_name, -- 9 
	   case when replace(fieldname, 'road_code', 'object_id') = 'object_id' then 'amstrad_routes.objects' else 'dgDict_' + cast(sp_id as varchar) + '_' + cast(sp_key as varchar) + '_' + cast(sp_value as varchar) end as table_ref_name,
	   0 as isHidden,
	   0 as isDisabled,
	   null as maxLength
from [dbo].[dgLayerAttributes] 
where [layer_id] = 176
  and fieldname not in ('roadid')

insert into dgStructToMySQL_2(layer_id, type_layer, field_name, display_name, type_name, isHidden, isDisabled, maxLength)
values(176, 1, 'idd', 'id', 10, 1, 1, null)

DECLARE tc CURSOR FORWARD_ONLY READ_ONLY FOR
    select field_name from dgStructToMySQL_2 where type_name in (9, 13, 5)
DECLARE @tname varchar(255);
DECLARE @sql varchar(255);
OPEN tc;
FETCH NEXT FROM tc INTO @tname;
WHILE @@FETCH_STATUS = 0 BEGIN
    SET @sql = 'update dgStructToMySQL_2 set maxLength = m.l from #desc d inner join (select max(len(' + @tname + ')) as l from [layer].[dgLayer_176]) m on 1 = 1 where d.field_name = ''' + @tname + '''';
    exec(@sql);
	print @sql;
    FETCH NEXT FROM tc INTO @tname;
END;
CLOSE tc;
DEALLOCATE tc;



declare @layer_id int;
set @layer_id = 176

declare @nameLayer nvarchar(50)
set @nameLayer = 'dgLayer_' + cast(@layer_id as varchar)
declare @sql_create varchar(max), @nameF varchar(max), @typeF varchar(max), @stringMax varchar(max)
set @sql_create = 'drop table if exists ' + @nameLayer + ';' + char(13) + 'create table ' + @nameLayer + char(13) + '(' + char(13)
declare @curs_meta cursor
set @curs_meta  = cursor scroll for 	
								select field_name, type_name, maxLength from dgStructToMySQL_2
open @curs_meta
fetch next from @curs_meta into @nameF, @typeF, @stringMax
while @@fetch_status = 0
begin
		if (@typef in ('14','15','16')) set @typef = 'datetime null'
		if (@typef in ('7', '10')) set @typef = 'int null'
		if (@typef in ('13', '9', '5')) set @typef = 'varchar(' + @stringMax + ') null'
		if (@typef in ('8', '11')) set @typef = 'decimal(8,3) null'
		if (@typef in ('12')) set @typef = 'int(1) null'
		if (@nameF = 'idd') set @typef = 'int not null auto_increment'

		set @sql_create = @sql_create + '`' + @namef + '`' + ' ' + @typef
	fetch next from @curs_meta into @nameF, @typeF, @stringMax
		set @sql_create = @sql_create + case when @@fetch_status != -1 then ',' else ', _road_id int(5) null, primary key (id) ) engine=innodb;' end + char(13)
end
close @curs_meta
deallocate @curs_meta

select @sql_create as 'sql_create'


--- перекачаю данные
select top 100 r.feat_id as _road_id, lay.* from [layer].[dgLayer_176] lay
inner join dgRoadEnabledAttributes r on r.id = lay.dgRoadEnabledAttributes_id
inner join dgRoads f on f.feat_id = r.feat_id


---  переименовать поле idd -> id
---  проставить object_id
