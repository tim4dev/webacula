Name:				webacula
Version:       3.2.1
Release:       1%{?dist}
Summary:       Web interface of a Bacula backup system
Summary(ru):	Веб интерфейс для Bacula backup system

Group:			Applications/Internet
License:			GPLv3
URL:				http://webacula.sourceforge.net/
Source0:       http://downloads.sourceforge.net/project/%{name}/%{name}-%{version}.tar.gz
Source1:			webacula.conf
Source2:			config.ini
Source3:       webacula_clean_tmp_files
BuildRoot:     %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:		noarch

Requires: webserver
Requires: bacula-console >= 2.4.4
Requires: php-ZendFramework >= 1.8.3
Requires: php >= 5.2.4
Requires: php-pdo
Requires: php-json
Requires: php-pcre
Requires: php-gd
Requires: php-xml

%description
Webacula - Web Bacula - web interface of a Bacula backup system.
Currently it can run Job, restore all files or selected files,
restore the most recent backup for a client,
restore backup for a client before a specified time,
mount/umount Storages, show scheduled, running and terminated Jobs and more.
Supported languages: English, French, German, Portuguese Brazil, Russian.


%prep
%setup -q


%build


%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__mkdir} -p $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/
%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/
%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/%{name}
%{__cp} -pr ./* $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__cp} %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/webacula.conf
%{__cp} %{SOURCE2} $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.ini
%{__cp} %{SOURCE3} $RPM_BUILD_ROOT%{_sysconfdir}/cron.daily/webacula_clean_tmp_files
%{__ln_s} %{_sysconfdir}/%{name}/config.ini $RPM_BUILD_ROOT%{_datadir}/%{name}/application/config.ini 


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc 4CONTRIBUTORS 4CONTRIBUTORS.ru AUTHORS COPYING README UPDATE
%doc docs/FAQ
%doc install/INSTALL install/INSTALL.ru install/INSTALL.es
%{_datadir}/%{name}/
%{_sysconfdir}/cron.daily/webacula_clean_tmp_files
%config(noreplace) %{_sysconfdir}/httpd/conf.d/webacula.conf
%config(noreplace) %{_sysconfdir}/%{name}/config.ini



%changelog
* Wed Sep 30 2009 Yuri Timofeev <tim4dev@gmail.com> 3.2.1-1
- Initial Spec file creation for Fedora
