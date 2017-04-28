Name:          webacula
Version:       7.0.0
Release:       1%{?dist}
Summary:       Web interface of a Bacula backup system
Summary(ru):   Веб интерфейс для Bacula backup system

Group:      Applications/Internet
License:    GPLv3+
URL:        http://webacula.sourceforge.net/
Source0:    http://downloads.sourceforge.net/%{name}/%{name}-%{version}.tar.gz
BuildRoot:  %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:  noarch

Requires: webserver
Requires: bacula-console >= 5.0
Requires: php-ZendFramework >= 1.8.3
Requires: php >= 5.2.4
Requires: php-pdo
Requires: php-json
Requires: php-pcre
Requires: php-gd
Requires: php-xml

%description
Webacula - Web Bacula - web interface of a Bacula backup system.
Supports the run Job, restore all files or selected files,
restore the most recent backup for a client,
restore backup for a client before a specified time,
mount/umount Storages, show scheduled, running and terminated Jobs and more.
Supported languages: English, Czech, French, German, Italian,
Portuguese Brazil, Russian.

%description -l ru
Webacula - Web Bacula - веб интерфейс для Bacula backup system.
Поддерживает запуск Заданий, восстановление всех или выбранных файлов,
восстановление самого свежего бэкапа для клиента,
восстановление бэкапа для клиента сделанного перед указанным временем,
монтирование/размонтирование Хранилищ, показ запланированных, 
выполняющихся и завершенных Заданий и прочее.
Поддерживаемые языки: английский, чешский, французский, немецкий, итальянский,
бразильский португальский, русский.


%prep
%setup -q



%build


%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}/application
mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}/html
mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}/languages
mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}/library
mkdir -p $RPM_BUILD_ROOT%{_datadir}/%{name}/install

cp ./application/config.ini  $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.ini
rm -f ./application/config.ini
ln -s %{_sysconfdir}/%{name}/config.ini  $RPM_BUILD_ROOT%{_datadir}/%{name}/application/config.ini 

cp ./install/apache/webacula.conf  $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/webacula.conf
rm -f ./install/apache/webacula.conf

cp -pr ./application $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./html        $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./languages   $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./library     $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./install     $RPM_BUILD_ROOT%{_datadir}/%{name}


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc 4CONTRIBUTORS 4CONTRIBUTORS.ru AUTHORS COPYING README UPDATE ChangeLog
%doc docs/
%{_datadir}/%{name}/application
%{_datadir}/%{name}/html
%{_datadir}/%{name}/library
%{_datadir}/%{name}/install
%dir %{_datadir}/%{name}
%dir %{_datadir}/%{name}/languages
%config(noreplace) %{_sysconfdir}/httpd/conf.d/webacula.conf
%config(noreplace) %{_sysconfdir}/%{name}/config.ini
%lang(cs) %{_datadir}/%{name}/languages/cs
%lang(de) %{_datadir}/%{name}/languages/de
%lang(en) %{_datadir}/%{name}/languages/en
%lang(es) %{_datadir}/%{name}/languages/es
%lang(fr) %{_datadir}/%{name}/languages/fr
%lang(it) %{_datadir}/%{name}/languages/it
%lang(pt) %{_datadir}/%{name}/languages/pt
%lang(ru) %{_datadir}/%{name}/languages/ru



%changelog
* Sat Oct 29 2011 Yuri Timofeev <tim4dev@gmail.com> 5.5.2-1
- Version 5.5.2

* Sat Sep 10 2011 Yuri Timofeev <tim4dev@gmail.com> 5.5.1-1
- Version 5.5.1

* Mon Jan 24 2011 Yuri Timofeev <tim4dev@gmail.com> 5.5.0-1
- Version 5.5.0

* Mon Jan 24 2011 Yuri Timofeev <tim4dev@gmail.com> 5.0.3-1
- Version 5.0.3

* Tue Aug 10 2010 Yuri Timofeev <tim4dev@gmail.com> 5.0.2-1
- Version 5.0.2

* Thu May 12 2010 Yuri Timofeev <tim4dev@gmail.com> 5.0.1-1
- Version 5.0.1

* Thu Feb 20 2010 Yuri Timofeev <tim4dev@gmail.com> 5.0-1
- Version 5.0

* Tue Feb 16 2010 Yuri Timofeev <tim4dev@gmail.com> 3.5-1
- Version 3.5

* Wed Dec 9 2009 Yuri Timofeev <tim4dev@gmail.com> 3.4.1-1
- Version 3.4.1

* Fri Oct 16 2009 Yuri Timofeev <tim4dev@gmail.com> 3.4-1
- Version 3.4

* Tue Oct 13 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-6
- Fix #526855.

* Tue Oct 13 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-5
- Fix #526855. Remove Zend Framework from source.

* Tue Oct 13 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-4
- Fix #526855

* Mon Oct 12 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-3
- Fix #526855

* Sat Oct 10 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-2
- Fix #526855 "Review Request"

* Thu Oct 08 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3-1
- Initial Spec file creation for Fedora
