
    Copyright 2007, 2008, 2009 Yuri Timofeev tim4dev@gmail.com
    http://webacula.sourceforge.net/

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.


	INSTALL.es Copyright 2009	Reynier Perez Mira <rperezm@uci.cu>


	Requerimientos del Sistema
	~~~~~~~~~~~~~~~~~~~~~~~~~~~

- Bacula 3.0 o superior.
- Soportados MySQL y PostgreSQL.
- Zend Framework version 1.8.3
- Zend Framework está construido usando programación orientada a objetos y requiere: 
  PHP 5.2.4 o superior con la extensión PDO(de acuerdo al motor de bases de datos seleccionado) activada . Por favor revise el apéndice "Requerimientos del Sistema" para una información mejor detallada
  información más detallada:
  http://framework.zend.com/manual/en/requirements.html
- Apache con el módulo mod_rewrite o servidor web equivalente. Por ejemplo, nginx con ngx_http_rewrite_module.
- Módulo  GD (php-gd package) de PHP. GD es una librería de código abierto que permite la creación dinámica de imágenes por los programadores.
- Gestor de Bases de Datos MySQL, PostgreSQL para poder usar las funcionalidades: Restaurar Ficheros y Libro de Logs.
- http://php.net/dom para subscripciones RSS

Ejecute el script install/check_system_requirements.php antes de continuar lo que le permitirá comprobar los requerimientos.


	Instalación & Configuración
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~

- Inicie sesión como "root"
- Haga un directorio mkdir /var/www/webacula
- Copie la distribución descargada al directorio previamente creado /var/www/webacula

- Extraiga el archivo Zend.tar.gz que puede ser encontrado en el directorio "library/" o descargado desde Internet
  cd library/
  tar xvf Zend.tar.gz

- El árbol de directorios debe quedar como sigue:

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


- Configure los parámetros de conexión a la Base de Datos del Catálogo, webacula /var/www/webacula/application/config.ini
	
- Configure los parámetros
   tmpdir = "/tmp"
  En el directorio "tmpdir" será almacenado el fichero que contiene la lista de archivos a restaurar. Tanto 
  el fichero como el directorio deben poder ser leídos por el servicio Director

- Cree un nuevo grupo (en caso de que no haya sido creado con anterioridad) :
	groupadd bacula

- Adicione Apache al grupo creado:
	usermod -aG bacula apache

- Ahora, el fichero bconsole puede ser ejecutado por el servidor web Apache :
  NOTA. /usr/sbin/bconsole -- fichero ELF binario (no un script!)

  Variante 1:
	chown root:bacula /usr/sbin/bconsole
	chmod u=rwx,g=rx,o=  /usr/sbin/bconsole

	chown root:bacula /etc/bacula/bconsole.conf
	chmod u=rw,g=r,o= /etc/bacula/bconsole.conf

   Edite el fichero application/config.ini
       bacula.sudo = ""
       bacula.bconsole = "/usr/sbin/bconsole"


  Variante 2:
    Edite application/config.ini
       bacula.sudo = "/usr/bin/sudo"
       bacula.bconsole = "/usr/sbin/bconsole"

    visudo
        # (!!! comment here !!!) Defaults requiretty
        apache ALL=NOPASSWD: /usr/sbin/bconsole

  Compruebe los cambios realizados :
# su -l apache -s /bin/sh -c "/usr/bin/sudo /usr/sbin/bconsole -n -c /etc/bacula/bconsole.conf"

- Cree un fichero para Bacula en la configuración de Apache /etc/httpd/conf.d/webacula.conf
  NOTA. La organización de directorios puede ser diferente en su servidor.

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


- Configure el mod_rewrite :
  NOTA. La organización de directorios puede ser diferente en su servidor.

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

# edit RewriteBase if necessary
RewriteBase   /webacula
RewriteRule !\.(js|ico|gif|jpg|png|css)$ index.php

For testing mod_rewrite change 'RewriteBase' (if necessary) in webacula/html/test_mod_rewrite/.htaccess
And use URL like
   http://localhost/webacula/test_mod_rewrite/
for test mod_rewrite



- Incremente los valores de los parámetros siguientes en el fichero de configuración /etc/php.ini :
  memory_limit = 32M
  max_execution_time = 300

  Si va a restaurar ("Restore Job" -> "Select Files for Restore") alrededor de 100,000 ficheros establezca la siguiente configuración:
  memory_limit = 128M
  max_execution_time = 600

- Lo próximo (NOTA. Algunos comandos pueden ser diferentes en su sistema) :
/sbin/service httpd restart

- Chequee que el módulo "mod_rewrite" y su configuración estén correctas :
apachectl -t -D DUMP_MODULES 2>&1 | grep rewrite
rewrite_module (shared)

- Para mostrar mensajes de salida de los trabajos se debe adicionar está línea:
  catalog = all, !skipped, !saved

  en el fichero bacula-dir.conf y reiniciar el demonio pertinente :

Messages {
  Name = Standard
...
  catalog = all, !skipped, !saved
}

Consulte el "Capítulo 15. Messages Resource" en el Manual de Bacula

- Seguidamente ejecute las siguientes órdenes:
cd /etc/bacula
./bacula stop
./bacula start

Elimine los ficheros temporales no necesarios: el script wb_clean_tmp.sh puede ser ejecutado mediante un cron.

Si es necesario cambie el usuario y contraseña en el fichero
	install/
		webacula_mysql_create_database.sh
		webacula_postgresql_create_database.sh
		webacula_postgresql_make_tables.sh

Cree la Base de Datos, las tablas y el usuario:
   cd install

MySQL:
   ./webacula_mysql_create_database.sh
   ./webacula_mysql_make_tables.sh

PostgreSQL:
   ./webacula_postgresql_create_database.sh
   ./webacula_postgresql_make_tables.sh



	Libro de Logs (LogBook)
	~~~~~~~
	Libro de Logs es un simple calendario electrónico de copias de seguridad. Son almacenadas entradas relacionadas con acciones de insertar, modificar, eliminar ejecutadas de forma manual por el operador.
Los registros pueden contener vínculos (usualmente vínculos web en los que sea posible clickear) a los trabajos de Bacula o a otros registros.
Algunos de los registros almacenados pueden ser, por ejemplo, fallas del equipamiento, situaciones sobrenaturales, etc.
Observe los pantallazos en el Sitio Web de Webacula (http://webacula.sourceforge.net/) para mejor información.
Libro de Logs(Logbook) almacena los registros en una Base de Datos separada y, MySQL, PostgreSQL es soportado.


	Actualizaciones
	~~~~~~~~~~~~~~~~

	Si es necesario( la funcionalidad "full text search" no funciona) ejecute mysqldump, elimine la Base de Datos webacula y creela con:

CREATE DATABASE webacula
	DEFAULT CHARACTER SET utf8
   DEFAULT COLLATE utf8_general_ci;

	y cargue los datos del fichero generado nuevamente.



    Trabajos de Recuperación (detalles técnicos)
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Comandos como "restore all" son enviados inmediatamente al servicio Director.

    Para seleccionar ficheros individuales a restaurar use el siguiente esquema.
    Of DB bacula in DB webacula into temporary tables copied data for a particular JobId.

Nota. Este trabajo puede tomar un tiempo excesivo.
    Deben ser incrementados los valores en el fichero /etc/php.ini : memory_limit and max_execution_time.

    Luego, marque los ficheros y directorios a restaurar.
    Luego, un listado de ficheros y/o carpetas y un comando son enviados al servicio Director.
    Remover las tablas temporales de la BD webacula.

Nota. Se necesita un fichero que contenga una lista de ficheros a restaurar el cual debe ser leído por el servicio Director.

<eof>
