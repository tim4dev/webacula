Name:          webacula
Version:       3.3.0
Release:       3%{?dist}
Summary:       Web interface of a Bacula backup system
Summary(ru):   Веб интерфейс для Bacula backup system

Group:      Applications/Internet
License:    GPLv3+
URL:        http://webacula.sourceforge.net/
Source0:    http://downloads.sourceforge.net/project/%{name}/%{name}-%{version}.tar.gz
Source1:    webacula.conf
Source2:    config.ini
Source3:    webacula_clean_tmp_files.sh
BuildRoot:  %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:  noarch

Requires: webserver
Requires: bacula-console >= 2.4.0
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
Supported languages: English, French, German, Portuguese Brazil, Russian.

%description -l ru
Webacula - Web Bacula - веб интерфейс для Bacula backup system.
Поддерживает запуск Заданий, восстановление всех или выбранных файлов,
восстановление самого свежего бэкапа для клиента,
восстановление бэкапа для клиента сделанного перед указанным временем,
монтирование/размонтирование Хранилищ, показ запланированных, 
выполняющихся и завершенных Заданий и прочее.
Поддерживаемые языки: английский, французский, немецкий,
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
cp -pr ./application $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./html        $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./languages   $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./library     $RPM_BUILD_ROOT%{_datadir}/%{name}
cp -pr ./install     $RPM_BUILD_ROOT%{_datadir}/%{name}
cp %{SOURCE1}  $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/webacula.conf
cp %{SOURCE2}  $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.ini
install -p -m 755 %{SOURCE3}  $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/webacula_clean_tmp_files.sh
ln -s %{_sysconfdir}/%{name}/config.ini  $RPM_BUILD_ROOT%{_datadir}/%{name}/application/config.ini 


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
%{_sysconfdir}/cron.daily/webacula_clean_tmp_files.sh
%config(noreplace) %{_sysconfdir}/httpd/conf.d/webacula.conf
%config(noreplace) %{_sysconfdir}/%{name}/config.ini
%lang(de) %{_datadir}/%{name}/languages/de
%lang(en) %{_datadir}/%{name}/languages/en
%lang(fr) %{_datadir}/%{name}/languages/fr
%lang(pt) %{_datadir}/%{name}/languages/pt
%lang(ru) %{_datadir}/%{name}/languages/ru



%changelog
* Mon Oct 12 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3.0-3
- Fix #526855

* Sat Oct 10 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3.0-2
- Fix #526855 "Review Request"

* Thu Oct 08 2009 Yuri Timofeev <tim4dev@gmail.com> 3.3.0-1
- Initial Spec file creation for Fedora
