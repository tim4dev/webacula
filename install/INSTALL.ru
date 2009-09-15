
    Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
    http://webacula.sourceforge.net/

    Webacula - это свободная программа; вы можете повторно распространять её и/или модифицировать
    её в соответствии со Стандартной Общественной Лицензей GNU, опубликованной
    Фондом Свободного ПО; либо версии 3, либо (по вашему выбору) любой более поздней версии.

    Webacula распространяется в надежде, что она будет полезной, но БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ;
    даже без подразумеваемых гарантий КОММЕРЧЕСКОЙ ЦЕННОСТИ или ПРИГОДНОСТИ ДЛЯ КОНКРЕТНОЙ
    ЦЕЛИ. Для получения подробных сведений смотрите Стандартную Общественную Лицензию GNU.

    Вы должны были получить копию Стандартной Общественной Лицензии GNU вместе с этой
    программой; если нет,смотрите <http://www.gnu.org/licenses/>.



	Системные требования
	~~~~~~~~~~~~~~~~~~~~

- Bacula 3.0 или выше.
- Полная поддержка СУБД MySQL, PostgreSQL и Sqlite.
- Zend Framework version 1.8.3
- Zend Framework требует PHP 5.2.4 и выше с расширением PDO. См. также:
  http://framework.zend.com/manual/en/requirements.html
- Apache и mod_rewrite, либо эквивалентный веб-сервер. Например, nginx и ngx_http_rewrite_module.
- Установленный пакет php-gd для графики.
- Создание отдельной БД "webacula" для восстановления файлов и ведения Журнала.
- http://php.net/dom для RSS ленты

Запустите install/check_system_requirements.php для проверки.


	Установка & настройка
	~~~~~~~~~~~~~~~~~~~~~

- От суперпользователя
  mkdir /var/www/webacula

- Скопировать webacula в каталог /var/www/webacula, например.

- Распакуйте архив Zend.tar.gz который находится в каталоге library/
  cd library/
  tar xvf Zend.tar.gz

- Дерево каталогов должно получиться таким :

/var/www/webacula/
|-- application
|   |-- controllers
|   |-- models
|   `-- views
|-- docs
|-- install
|-- html
|-- languages
`-- library
    |-- Other
    |-- MyClass
    |
    `-- Zend (this is Zend Framework package)
        |-- Acl
        |-- Auth
        |-- Cache
        |-- Config
       ...




- Укажите параметры подключения к БД Каталога и webacula в /var/www/webacula/application/config.ini

- Настройте параметр
   tmpdir = "/tmp"
  это каталог куда будет сохранен файл в котором будет список файлов для восстановления.
  Каталог "tmpdir" и файлы в нем должны быть доступны Директору по чтению.


- Создайте системную группу (если еще не создана при установке bacula) :
	groupadd bacula

- Добавьте apache в группу:
	usermod -aG bacula apache

- Далее, Apache должен иметь возможность запуска bconsole :
  ПРИМЕЧАНИЕ. /usr/sbin/bconsole -- это бинарный ELF файл (не shell скрипт!)

   Вариант 1:
	chown root:bacula /usr/sbin/bconsole
	chmod u=rwx,g=rx,o=  /usr/sbin/bconsole

	chown root:bacula /etc/bacula/bconsole.conf
	chmod u=rw,g=r,o= /etc/bacula/bconsole.conf

    Измените application/config.ini
       bacula.sudo = ""
       bacula.bconsole = "/usr/sbin/bconsole"


   Вариант 2:
    Измените application/config.ini
       bacula.sudo = "/usr/bin/sudo"
       bacula.bconsole = "/usr/sbin/bconsole"

    visudo
        #  (!!! закомментировать здесь !!!) Defaults requiretty
        apache ALL=NOPASSWD: /usr/sbin/bconsole

  Проверка :
# su -l apache -s /bin/sh -c "/usr/bin/sudo /usr/sbin/bconsole -n -c /etc/bacula/bconsole.conf"

- Содержимое файла /etc/httpd/conf.d/webacula.conf
  Примечание. Конкретные каталоги на вашей системе могут быть другими.

Alias "/webacula"  "/var/www/webacula/html"
<Directory "/var/www/webacula/html">
	Options Indexes FollowSymLinks
	AllowOverride All
	Order deny,allow
	Allow from 127.0.0.1
	# your network
	Allow from 192.161.150.0/255.255.255.0

	AuthType Basic
	AuthName "Webacula"
	AuthUserFile       /etc/httpd/conf/webacula.users
	Require valid-user
</Directory>


- Настройка mod_rewrite.
  Примечание. Конкретные каталоги на вашей системе могут быть другими.

----------- /etc/httpd/conf/httpd.conf

LoadModule rewrite_module modules/mod_rewrite.so
AccessFileName .htaccess
RewriteEngine on
# for DEBUG # RewriteLog "/var/log/httpd/rewrite.log"
# for DEBUG # RewriteLogLevel 3


----------- /var/www/webacula/html/.htaccess

php_flag magic_quotes_gpc off
php_flag register_globals off
RewriteEngine On

# измените RewriteBase если нужно
RewriteBase   /webacula
RewriteRule !\.(js|ico|gif|jpg|png|css)$ index.php


Для проверки работы mod_rewrite измените значение 'RewriteBase' (если нужно) в webacula/html/test_mod_rewrite/.htaccess
И используйте URL типа
   http://localhost/webacula/test_mod_rewrite/
для проверки mod_rewrite



- Увеличьте значения в /etc/php.ini :
  memory_limit = 32M
  max_execution_time = 300

  Если вы будете восстанавливать ("Restore Job" -> "Select Files for Restore") примерно 100,000 уникальных файлов установите:
  memory_limit = 128M
  max_execution_time = 600

- Далее нужно рестартовать Apache.
  Примечание. Конкретные команды в вашей системе могут быть другими.
/sbin/service httpd restart

- Проверить, загружен ли mod_rewrite :
apachectl -t -D DUMP_MODULES 2>&1 | grep rewrite
rewrite_module (shared)

- Для показа сообщений, которые выводятся во время выполнения заданий добавьте в вашу конфигурацию bacula-dir.conf :

  catalog = all, !skipped, !saved

  и перезапустите сервис Bacula Director :

Messages {
  Name = Standard
...
  catalog = all, !skipped, !saved
}

Подробнее см. документацию Bacula "Chapter 15. Messages Resource".

- Далее :
cd /etc/bacula
./bacula stop
./bacula start

Для удаления старых tmp-файлов запускайте скрипт wb_clean_tmp.sh по крону.

Если нужно измените имя пользователя и пароль в каталоге install/ для следующих файлов:
       webacula_mysql_create_database.sh
       webacula_postgresql_create_database.sh
       webacula_postgresql_make_tables.sh


Создайте БД, таблицы и пользователя  :
   cd install

для MySQL:
   ./webacula_mysql_create_database.sh
   ./webacula_mysql_make_tables.sh

для PostgreSQL:
   ./webacula_postgresql_create_database.sh
   ./webacula_postgresql_make_tables.sh

для Sqlite:
   sudo mkdir /var/lib/sqlite/
	sudo chown root.apache /var/lib/sqlite
	sudo chmod g+rw /var/lib/sqlite
	sudo ./webacula_sqlite_create_database.sh
	sudo chgrp apache /var/lib/sqlite/webacula.db
	sudo chmod g+rw /var/lib/sqlite/webacula.db


Примечание. БД webacula требуется для восстановления заданий и файлов.





	LogBook
	~~~~~~~
	Logbook - это простой электронный журнал для ведения записей о бэкапах, сбоях и др. Записи вносятся вручную и могут содержать кликабельные ссылки
на записи о заданиях в БД Каталога Bacula, а также ссылки на другие записи LogBook.
См. скриншоты http://webacula.sourceforge.net/. Таблица LogBook находится в отдельной БД "webacula". 
Поддерживаются СУБД MySQL и PostgreSQL.


	Обновления
	~~~~~~~~~~

   Если нужно (например, не работает полнотекстовой поиск) сохраните данные с помощью mysqldump, удалите БД webacula и пересоздайте командой :

CREATE DATABASE webacula 
   DEFAULT CHARACTER SET utf8
   DEFAULT COLLATE utf8_general_ci;

	затем загрузите данные из дампа.




    Restore Job (технические детали)
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Команда типа "restore all" сразу отсылается к сервису Director.

    Для выбора отдельных файлов для восстановления используется следующая схема.
    Из БД bacula в БД webacula во временные таблицы копируются данные для конкретного JobId.

Примечание. Этот процесс может занять продолжительное время.
    Возможно придется увеличить значения в /etc/php.ini : memory_limit и max_execution_time.

    Затем файлы и каталоги помечаются для восстановления.
    Затем список файлов и команда на восстановление отсылаются к сервису Director.
    Временные таблицы в БД webacula удаляются.

Примечание. Требуется, чтобы файл, содержащий список файлов для восстановления, был доступен сервису Director
    по чтению.


<eof>
