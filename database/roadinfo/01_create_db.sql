set names utf8;
set time_zone = '+00:00';
set foreign_key_checks = 0;
set sql_mode = 'no_auto_value_on_zero';

-- drop database if exists amstrad_roadinfo;
-- create database amstrad_roadinfo character set utf8 collate utf8_bin;
use amstrad_roadinfo;


drop table if exists layers;
drop table if exists attachments;



CREATE TABLE `attachments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `desc` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,  
  `file` longblob NOT NULL,
  `mime` varchar(60) DEFAULT NULL,
  `size` int(10) unsigned NOT NULL,
  `object_id` int unsigned null,
  `created_at` datetime NULL,
  `updated_at` datetime NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;



delete from attachments;



create table layers
(
   id int not null comment 'ИД',
   name varchar(400) not null comment 'наименование слоя',
   parent_id int comment 'родительский',
   lft int comment 'служебное',
   rgt int comment 'служебное',
   table_name varchar(50) null comment 'наименование таблицы содержащей данные',
   geometry_type varchar(20) null comment 'тип геоданных LineString , Point',
   isHidden int(1) null default(0) comment 'показывать на экране',
   primary key (id),
   unique key id_unique (id, parent_id)
) engine=innodb comment='справочник типов сведений';

 /* Select 'insert into layers (id, name, parent_id, table_name) values (' + cast([layer_id] as varchar(7)) + ',''' + [name] + ''',' + cast(case when [parent_layer_id] is null then '' else [parent_layer_id] end as varchar(7)) + ', ''dgLayer_' +  cast([layer_id] as varchar(7)) +''');' from [dbo].[dgLayers] */

insert into layers (id, name, parent_id) values (-1,'Сведения',null);

insert into layers (id, name, parent_id, table_name) values (1,'Ведомости',-1, 'dgLayer_1');
insert into layers (id, name, parent_id, table_name) values (12,'Сооружения для пропуска транспортных потоков',1, 'dgLayer_12');
insert into layers (id, name, parent_id, table_name) values (1962,'Интенсивность движения',1, 'dgLayer_1962');
insert into layers (id, name, parent_id, table_name) values (20114,'ДТП',1, 'dgLayer_20114');
insert into layers (id, name, parent_id, table_name) values (20115,'Дорожные работы',1, 'dgLayer_20115');
insert into layers (id, name, parent_id, table_name) values (202,'Конструкция земляного полотна и дорожной одежды',1, 'dgLayer_202');
insert into layers (id, name, parent_id, table_name) values (20336,'Дефекты дорожного покрытия',1, 'dgLayer_20336');
insert into layers (id, name, parent_id, table_name) values (20566,'Фактические объекты',1, 'dgLayer_20566');
insert into layers (id, name, parent_id, table_name) values (219,'Пересечения и примыкания',1, 'dgLayer_219');
insert into layers (id, name, parent_id, table_name) values (26,'Сооружения для пешеходного движения',1, 'dgLayer_26');
insert into layers (id, name, parent_id, table_name) values (27,'Средства организации и безопасность движения',1, 'dgLayer_27');
insert into layers (id, name, parent_id, table_name) values (28,'Геометрические параметры поперечного профиля дорог',1, 'dgLayer_28');
insert into layers (id, name, parent_id, table_name) values (294,'Коммуникации и сооружения водоотвода',1, 'dgLayer_294');
insert into layers (id, name, parent_id, table_name) values (298,'Параметры продольного профиля и плана трассы',1, 'dgLayer_298');
insert into layers (id, name, parent_id, table_name) values (299,'Характеристики местности',1, 'dgLayer_299');
insert into layers (id, name, parent_id, table_name) values (3,'Общие сведения о дорогах',1, 'dgLayer_3');
insert into layers (id, name, parent_id, table_name) values (300,'Сервис',1, 'dgLayer_300');
insert into layers (id, name, parent_id, table_name) values (302,'Обустройство и защитные сооружения',1, 'dgLayer_302');
insert into layers (id, name, parent_id, table_name) values (304,'Транспортно-эксплуатационные показатели',1, 'dgLayer_304');
insert into layers (id, name, parent_id, table_name) values (305,'Объекты дорожной службы',1, 'dgLayer_305');
insert into layers (id, name, parent_id, table_name) values (6986,'Прочие сведения',1, 'dgLayer_6986');
insert into layers (id, name, parent_id, table_name) values (77,'Населенные пункты',1, 'dgLayer_77');
insert into layers (id, name, parent_id, table_name) values (62,'2. Территориальное положение дорог',3, 'dgLayer_62');
insert into layers (id, name, parent_id, table_name) values (456,'2а. Положение участков дороги по отношению к основному участку',3, 'dgLayer_456');
insert into layers (id, name, parent_id, table_name) values (307,'6. Выходы на границы края',3, 'dgLayer_307');
insert into layers (id, name, parent_id, table_name) values (310,'8a. Фактические категории дорог',3, 'dgLayer_310');
insert into layers (id, name, parent_id, table_name) values (314,'9. Размеры полосы отвода',3, 'dgLayer_314');
insert into layers (id, name, parent_id, table_name) values (311,'91. Совмещенные участки дорог',3, 'dgLayer_311');
insert into layers (id, name, parent_id, table_name) values (312,'92. Дорожные организации, обслуживающие дорогу',3, 'dgLayer_312');
insert into layers (id, name, parent_id, table_name) values (7108,'98. Историческая справка',3, 'dgLayer_7108');
insert into layers (id, name, parent_id, table_name) values (7083,'99. Сведения об инвентаризации',3, 'dgLayer_7083');
insert into layers (id, name, parent_id, table_name) values (94,'4. Дороги по населенным пунктам',77, 'dgLayer_94');
insert into layers (id, name, parent_id, table_name) values (1319,'4a. Расстояния до застройки',77, 'dgLayer_1319');
insert into layers (id, name, parent_id, table_name) values (1941,'4б. Участки дорог по улицам в населенных пунктах',77, 'dgLayer_1941');
insert into layers (id, name, parent_id, table_name) values (313,'5. Обходы населенных пунктов',77, 'dgLayer_313');
insert into layers (id, name, parent_id, table_name) values (280,'11. Продольные водоотводы',294, 'dgLayer_280');
insert into layers (id, name, parent_id, table_name) values (1863,'22. Водопропускные трубы',294, 'dgLayer_1863');
insert into layers (id, name, parent_id, table_name) values (295,'38. Коммуникации в полосе отвода',294, 'dgLayer_295');
insert into layers (id, name, parent_id, table_name) values (1318,'85. Ведомость пунктов привязки подземных коммуникаций',294, 'dgLayer_1318');
insert into layers (id, name, parent_id, table_name) values (315,'10. Высота насыпи, глубина выемок',298, 'dgLayer_315');
insert into layers (id, name, parent_id, table_name) values (316,'24. Кривые в плане',298, 'dgLayer_316');
insert into layers (id, name, parent_id, table_name) values (317,'36. Продольный профиль',298, 'dgLayer_317');
insert into layers (id, name, parent_id, table_name) values (318,'71. Расстояния видимости',298, 'dgLayer_318');
insert into layers (id, name, parent_id, table_name) values (319,'12. Грунты земляного полотна',202, 'dgLayer_319');
insert into layers (id, name, parent_id, table_name) values (203,'14. Конструкция дорожной одежды',202, 'dgLayer_203');
insert into layers (id, name, parent_id, table_name) values (321,'35. Укрепления откосов земполотна',202, 'dgLayer_321');
insert into layers (id, name, parent_id, table_name) values (68,'15. Ширина проезжей части',28, 'dgLayer_68');
insert into layers (id, name, parent_id, table_name) values (261,'15а. Поперечные уклоны проезжей части',28, 'dgLayer_261');
insert into layers (id, name, parent_id, table_name) values (322,'26. Переходно-скоростные полосы',28, 'dgLayer_322');
insert into layers (id, name, parent_id, table_name) values (323,'27. Обочины',28, 'dgLayer_323');
insert into layers (id, name, parent_id, table_name) values (324,'37. Разделительные полосы',28, 'dgLayer_324');
insert into layers (id, name, parent_id, table_name) values (325,'16. Тротуары',26, 'dgLayer_325');
insert into layers (id, name, parent_id, table_name) values (6987,'16a. Велосипедные дорожки',26, 'dgLayer_6987');
insert into layers (id, name, parent_id, table_name) values (326,'17. Пешеходные дорожки',26, 'dgLayer_326');
insert into layers (id, name, parent_id, table_name) values (327,'18. Подземные переходы',26, 'dgLayer_327');
insert into layers (id, name, parent_id, table_name) values (656,'21а. Пешеходные мосты',26, 'dgLayer_656');
insert into layers (id, name, parent_id, table_name) values (328,'49. Пешеходные путепроводы',26, 'dgLayer_328');
insert into layers (id, name, parent_id, table_name) values (330,'21. Искусственные сооружения',12, 'dgLayer_330');
insert into layers (id, name, parent_id, table_name) values (331,'67. Транспортные развязки',12, 'dgLayer_331');
insert into layers (id, name, parent_id, table_name) values (333,'23. Железнодорожные переезды',219, 'dgLayer_333');
insert into layers (id, name, parent_id, table_name) values (221,'25. Съезды',219, 'dgLayer_221');
insert into layers (id, name, parent_id, table_name) values (335,'90. Дикие съезды',219, 'dgLayer_335');
insert into layers (id, name, parent_id, table_name) values (336,'19. Светофоры',27, 'dgLayer_336');
insert into layers (id, name, parent_id, table_name) values (176,'28. Дорожные знаки',27, 'dgLayer_176');
insert into layers (id, name, parent_id, table_name) values (337,'43. Ограждения',27, 'dgLayer_337');
insert into layers (id, name, parent_id, table_name) values (338,'43а. Особые случаи ограждений',27, 'dgLayer_338');
insert into layers (id, name, parent_id, table_name) values (339,'45. Направляющие устройства',27, 'dgLayer_339');
insert into layers (id, name, parent_id, table_name) values (340,'46. Горизонтальная разметка',27, 'dgLayer_340');
insert into layers (id, name, parent_id, table_name) values (341,'47. Вертикальная разметка',27, 'dgLayer_341');
insert into layers (id, name, parent_id, table_name) values (343,'29. Опасные участки',299, 'dgLayer_343');
insert into layers (id, name, parent_id, table_name) values (345,'31. Участки с ограничнием по габариту',299, 'dgLayer_345');
insert into layers (id, name, parent_id, table_name) values (371,'48. Характер рельефа местности',299, 'dgLayer_371');
insert into layers (id, name, parent_id, table_name) values (354,'40. Площадки для стоянки',300, 'dgLayer_354');
insert into layers (id, name, parent_id, table_name) values (355,'41. Площадки отдыха',300, 'dgLayer_355');
insert into layers (id, name, parent_id, table_name) values (356,'42. Автобусные остановки',300, 'dgLayer_356');
insert into layers (id, name, parent_id, table_name) values (357,'50. Контрольные посты милиции',300, 'dgLayer_357');
insert into layers (id, name, parent_id, table_name) values (358,'53. Автостанции, Автовокзалы',300, 'dgLayer_358');
insert into layers (id, name, parent_id, table_name) values (359,'54. Гостиници, мотели, кемпинги',300, 'dgLayer_359');
insert into layers (id, name, parent_id, table_name) values (360,'55. Станции технического обслуживания',300, 'dgLayer_360');
insert into layers (id, name, parent_id, table_name) values (361,'56. АЗС',300, 'dgLayer_361');
insert into layers (id, name, parent_id, table_name) values (362,'57. Общественные туалеты',300, 'dgLayer_362');
insert into layers (id, name, parent_id, table_name) values (363,'58. Моечные пункты',300, 'dgLayer_363');
insert into layers (id, name, parent_id, table_name) values (364,'59. Пункты медицинской помощи',300, 'dgLayer_364');
insert into layers (id, name, parent_id, table_name) values (365,'60. Пункты общественного питания',300, 'dgLayer_365');
insert into layers (id, name, parent_id, table_name) values (366,'61. Пункты связи',300, 'dgLayer_366');
insert into layers (id, name, parent_id, table_name) values (367,'62. Телефоны',300, 'dgLayer_367');
insert into layers (id, name, parent_id, table_name) values (368,'64. КПП таможни',300, 'dgLayer_368');
insert into layers (id, name, parent_id, table_name) values (369,'84. Торговые точки',300, 'dgLayer_369');
insert into layers (id, name, parent_id, table_name) values (375,'39. Освещение',302, 'dgLayer_375');
insert into layers (id, name, parent_id, table_name) values (377,'44. Подпорные стенки',302, 'dgLayer_377');
insert into layers (id, name, parent_id, table_name) values (376,'65. Озеленение',302, 'dgLayer_376');
insert into layers (id, name, parent_id, table_name) values (381,'66. Объекты дорожной службы',305, 'dgLayer_381');
insert into layers (id, name, parent_id, table_name) values (383,'68. Асфальто-бетонные заводы',305, 'dgLayer_383');
insert into layers (id, name, parent_id, table_name) values (386,'69a. Расположение карьеров',305, 'dgLayer_386');
insert into layers (id, name, parent_id, table_name) values (387,'70. Склады противогололедных материалов',305, 'dgLayer_387');
insert into layers (id, name, parent_id, table_name) values (7274,'95. Видеокамеры',305, 'dgLayer_7274');
insert into layers (id, name, parent_id, table_name) values (20172,'Среднегодовая суточная интенсивность движения автомобилей',1962, 'dgLayer_20172');
insert into layers (id, name, parent_id, table_name) values (389,'52. Отдельные объекты',6986, 'dgLayer_389');
insert into layers (id, name, parent_id, table_name) values (20000,'Карточка учета ДТП',20114, 'dgLayer_20000');
insert into layers (id, name, parent_id, table_name) values (20156,'Участки концентрации ДТП',20114, 'dgLayer_20156');
insert into layers (id, name, parent_id, table_name) values (20116,'Объекты дорожных работ',20115, 'dgLayer_20116');
insert into layers (id, name, parent_id, table_name) values (20670,'Ограничения движения',20566, 'dgLayer_20670');
insert into layers (id, name, parent_id, table_name) values (20687,'Ограничение поворотов',20566, 'dgLayer_20687');
insert into layers (id, name, parent_id, table_name) values (20572,'Фактически действующие АЗС',20566, 'dgLayer_20572');

insert into layers (id, name, parent_id) values (30000,'Паспорт',-1);
insert into layers (id, name, parent_id) values (40000,'Сведения ДГ-1',-1);

create index idx_id on layers(id);
create index idx_parent_id on layers(parent_id);


update layers set table_name = null where id in (1,12,1962,20114,20115,202,20336,20566,219,26,27,28,294,298,299,3,300,302,304,305,6986,77);
update layers set table_name = null where table_name = '';

/*
select id from layers where parent_id in (3,27,299,40009) or id in (1, 3,27,299,40009)
update layers set isHidden = 1 where id not in (1,3,27,299,40009,62,307,310,311,312,314,456,7083,7108,176,336,337,338,339,340,341,343,345,371)
*/


ALTER TABLE layers CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ИД' ;





create table layer_icons
(
id int not null auto_increment comment 'ИД',
`layer_id` int(6) not null comment 'ИД ведомости',
dict_icons varchar(15) not null comment 'код иконки',
where_param json not null comment 'список условий рендеринга иконки',
primary key (id)
) engine=innodb;


select * from layer_icons



drop table if exists table_attributes;

create table table_attributes
(
   id int not null auto_increment comment 'ИД',
   `table_name` varchar(50) not null comment 'наименование таблицы',
   field_name varchar(200) not null comment 'наименование поля',
   display_name varchar(300) not null comment 'пользовательское наименование',
   type_name varchar(10) not null comment 'тип данных',
   table_ref_name varchar(50) null comment 'название таблицы справочника',
   orderId DECIMAL(8,2) null comment 'порядок отображения на экране',
   isHidden int(1) not null default(0) comment 'признак скрытого поля',
   isDisabled int(1) not null default(0) comment 'признак только для чтения',
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (`table_name`, field_name),
   unique key id_unique3 (`table_name`, display_name)
) engine=innodb comment='описание структуры таблицы';

update table_attributes set isHidden = 0 where field_name in ('idd','_road_id','coordinate','id');
update table_attributes set orderId = id;
update table_attributes set orderId = -3 where field_name = 'object_id';

Select * from table_attributes



!!!!!!  при переносе данных из ДорГИС, делать trim() для полей ведомости и справочника





/*  выборка из MsSQL
 --  select 'insert into dgDict_6868_6869_6870(code, name) values("' + trim(convert(nvarchar(50), code)) + '", "' + trim(name) + '");' from dict.dgDict_6868_6869_6870
*/




drop table if exists dgDict_icons;
create table dgDict_icons(
id int not null auto_increment comment 'ИД',
code varchar(10) not null comment 'расширение',
name varchar(100) null comment 'описание иконки',
primary key (id)
) engine=innodb comment='справочник иконок';

delete from dicts where table_name = 'dgDict_icons';
insert into dicts(`table_name`, name) values('dgDict_icons', 'Иконки');   

delete from dict_attributes where table_name = 'dgDict_icons';
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_icons', 'code', 'Имя файла', 'string');   
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_icons', 'name', 'Описание', 'string');


insert into dgDict_icons(id, code, name)
select id, concat(id, '.png'), name from layers where id > 0



drop table if exists dicts;

create table dicts
(
id int not null auto_increment comment 'ИД',
`table_name` varchar(50) not null comment 'наименование таблицы',
name varchar(200) not null comment 'наименование поля',
primary key (id),
unique key id_unique (id),
unique key id_unique2 (`table_name`),
unique key id_unique3 (`table_name`, name)
) engine=innodb;

drop table if exists dict_attributes;

create table dict_attributes
(
id int not null auto_increment comment 'ИД',
`table_name` varchar(50) not null comment 'наименование таблицы',
field_name varchar(200) not null comment 'наименование поля',
display_name varchar(300) not null comment 'пользовательское наименование',
type_name varchar(10) not null comment 'тип данных',
primary key (id),
unique key id_unique (id),
unique key id_unique2 (`table_name`, field_name),
unique key id_unique3 (`table_name`, display_name)
) engine=innodb;



ALTER TABLE `amstrad_roadinfo`.`dgDict_6878_6879_6880` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `amstrad_roadinfo`.`dgDict_6878_6879_6880` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL;
ALTER TABLE `amstrad_roadinfo`.`dgDict_6878_6879_6880` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL;




delete from dicts where table_name = 'dgDict_591_592_593';
insert into dicts(`table_name`, name) values('dgDict_591_592_593', 'поперечное положение');   

delete from dict_attributes where table_name = 'dgDict_591_592_593';
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_591_592_593', 'code', 'Код', 'string');   
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_591_592_593', 'name', 'Наименование', 'string');

drop table if exists dgDict_591_592_593;
create table dgDict_591_592_593
(
   id int not null auto_increment comment 'ИД',
   code varchar(6) not null comment 'наименование таблицы',
   name varchar(6) not null comment 'наименование поля',
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (code),
   unique key id_unique3 (name)
) engine=innodb comment='справочник';

insert into dgDict_591_592_593(code, name) values("1.1", "1.1");
insert into dgDict_591_592_593(code, name) values("1.10", "1.10");
insert into dgDict_591_592_593(code, name) values("1.11", "1.11");
insert into dgDict_591_592_593(code, name) values("1.2", "1.2");
insert into dgDict_591_592_593(code, name) values("1.3", "1.3");
insert into dgDict_591_592_593(code, name) values("1.4", "1.4");
insert into dgDict_591_592_593(code, name) values("1.5", "1.5");
insert into dgDict_591_592_593(code, name) values("1.6", "1.6");
insert into dgDict_591_592_593(code, name) values("1.7", "1.7");
insert into dgDict_591_592_593(code, name) values("1.8", "1.8");
insert into dgDict_591_592_593(code, name) values("1.9", "1.9");
insert into dgDict_591_592_593(code, name) values("2.1", "2.1");
insert into dgDict_591_592_593(code, name) values("2.10", "2.10");
insert into dgDict_591_592_593(code, name) values("2.11", "2.11");
insert into dgDict_591_592_593(code, name) values("2.2", "2.2");
insert into dgDict_591_592_593(code, name) values("2.3", "2.3");
insert into dgDict_591_592_593(code, name) values("2.4", "2.4");
insert into dgDict_591_592_593(code, name) values("2.5", "2.5");
insert into dgDict_591_592_593(code, name) values("2.6", "2.6");
insert into dgDict_591_592_593(code, name) values("2.7", "2.7");
insert into dgDict_591_592_593(code, name) values("2.8", "2.8");
insert into dgDict_591_592_593(code, name) values("2.9", "2.9");
insert into dgDict_591_592_593(code, name) values("3.1", "3.1");
insert into dgDict_591_592_593(code, name) values("3.10", "3.10");
insert into dgDict_591_592_593(code, name) values("3.11", "3.11");
insert into dgDict_591_592_593(code, name) values("3.2", "3.2");
insert into dgDict_591_592_593(code, name) values("3.3", "3.3");
insert into dgDict_591_592_593(code, name) values("3.4", "3.4");
insert into dgDict_591_592_593(code, name) values("3.5", "3.5");
insert into dgDict_591_592_593(code, name) values("3.6", "3.6");
insert into dgDict_591_592_593(code, name) values("3.7", "3.7");
insert into dgDict_591_592_593(code, name) values("3.8", "3.8");
insert into dgDict_591_592_593(code, name) values("3.9", "3.9");
insert into dgDict_591_592_593(code, name) values("4.1", "4.1");
insert into dgDict_591_592_593(code, name) values("4.10", "4.10");
insert into dgDict_591_592_593(code, name) values("4.11", "4.11");
insert into dgDict_591_592_593(code, name) values("4.2", "4.2");
insert into dgDict_591_592_593(code, name) values("4.3", "4.3");
insert into dgDict_591_592_593(code, name) values("4.4", "4.4");
insert into dgDict_591_592_593(code, name) values("4.5", "4.5");
insert into dgDict_591_592_593(code, name) values("4.6", "4.6");
insert into dgDict_591_592_593(code, name) values("4.7", "4.7");
insert into dgDict_591_592_593(code, name) values("4.8", "4.8");
insert into dgDict_591_592_593(code, name) values("4.9", "4.9");









delete from dicts where table_name = 'dgDict_588_589_590';
insert into dicts(`table_name`, name) values('dgDict_588_589_590', 'тип светофора');   

delete from dict_attributes where table_name = 'dgDict_588_589_590';
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_588_589_590', 'code', 'Код', 'string');   
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_588_589_590', 'name', 'Наименование', 'string');
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_588_589_590', 'dgDict_icons', 'Имя файла', 'string');  


drop table if exists dgDict_588_589_590;
create table dgDict_588_589_590
(
   id int not null auto_increment comment 'ИД',
   code varchar(10) not null comment 'наименование таблицы',
   name varchar(10) not null comment 'наименование поля',
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (code),
   unique key id_unique3 (name)
) engine=innodb comment='справочник';

insert into dgDict_588_589_590(code, name) values("П.1", "П.1");
insert into dgDict_588_589_590(code, name) values("П.2", "П.2");
insert into dgDict_588_589_590(code, name) values("Т.1", "Т.1");
insert into dgDict_588_589_590(code, name) values("Т.10", "Т.10");
insert into dgDict_588_589_590(code, name) values("Т.1.г", "Т.1.г");
insert into dgDict_588_589_590(code, name) values("Т.1.л", "Т.1.л");
insert into dgDict_588_589_590(code, name) values("Т.1.п", "Т.1.п");
insert into dgDict_588_589_590(code, name) values("Т.1.пл", "Т.1.пл");
insert into dgDict_588_589_590(code, name) values("Т.2", "Т.2");
insert into dgDict_588_589_590(code, name) values("Т.3", "Т.3");
insert into dgDict_588_589_590(code, name) values("Т.3.л", "Т.3.л");
insert into dgDict_588_589_590(code, name) values("Т.3.п", "Т.3.п");
insert into dgDict_588_589_590(code, name) values("Т.4", "Т.4");
insert into dgDict_588_589_590(code, name) values("Т.4.ж", "Т.4.ж");
insert into dgDict_588_589_590(code, name) values("Т.5", "Т.5");
insert into dgDict_588_589_590(code, name) values("Т.6", "Т.6");
insert into dgDict_588_589_590(code, name) values("Т.6.д", "Т.6.д");
insert into dgDict_588_589_590(code, name) values("Т.7", "Т.7");
insert into dgDict_588_589_590(code, name) values("Т.8", "Т.8");
insert into dgDict_588_589_590(code, name) values("Т.9", "Т.9");




delete from dicts where table_name = 'dgDict_6868_6869_6870';
insert into dicts(`table_name`, name) values('dgDict_6868_6869_6870', 'объект регулирования');   

delete from dict_attributes where table_name = 'dgDict_6868_6869_6870';
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_6868_6869_6870', 'code', 'Код', 'string');   
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_6868_6869_6870', 'name', 'Наименование', 'string');

drop table if exists dgDict_6868_6869_6870;
create table dgDict_6868_6869_6870
(
   id int not null auto_increment comment 'ИД',
   code varchar(2) not null comment 'наименование таблицы',
   name varchar(20) not null comment 'наименование поля',
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (code),
   unique key id_unique3 (name)
) engine=innodb comment='справочник';

insert into dgDict_6868_6869_6870(code, name) values("5", "ДПС");
insert into dgDict_6868_6869_6870(code, name) values("4", "другой");
insert into dgDict_6868_6869_6870(code, name) values("3", "ж/д переезд");
insert into dgDict_6868_6869_6870(code, name) values("1", "пересечение");
insert into dgDict_6868_6869_6870(code, name) values("2", "пешеходный переход");
insert into dgDict_6868_6869_6870(code, name) values("6", "примыкание");





delete from dicts where table_name = 'dgDict_6878_6879_6880';
insert into dicts(`table_name`, name) values('dgDict_6878_6879_6880', 'техническое состояние');   

delete from dict_attributes where table_name = 'dgDict_6878_6879_6880';
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_6878_6879_6880', 'code', 'Код', 'string');   
insert into dict_attributes(`table_name`, field_name, display_name, type_name) values('dgDict_6878_6879_6880', 'name', 'Наименование', 'string');

drop table if exists dgDict_6878_6879_6880;
create table dgDict_6878_6879_6880
(
   id int not null auto_increment comment 'ИД',
   code varchar(2) not null comment 'наименование таблицы',
   name varchar(20) not null comment 'наименование поля',
   primary key (id),
   unique key id_unique (id),
   unique key id_unique2 (code),
   unique key id_unique3 (name)
) engine=innodb comment='справочник';

insert into dgDict_6878_6879_6880(code, name) values("1", "неудовлетворительное");
insert into dgDict_6878_6879_6880(code, name) values("0", "удовлетворительное");
insert into dgDict_6878_6879_6880(code, name) values("2", "хорошее");