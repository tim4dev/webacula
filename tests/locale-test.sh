#!/bin/bash
#
# Test for translate into several languages and display content
# 
# @author Yuriy Timofeev <tim4dev@gmail.com>
# @package webacula
# @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License 
#

LINE1="*********************************************************************************************"
LINE2="********************************"

###########################################################
# Functions
###########################################################

my_login() {
    LOCALE1="${1}"
    wget --quiet --no-proxy --save-cookies cookies.txt \
        --header="Accept-Language: ${LOCALE1}"   \
        --post-data 'login=root&pwd=1&rememberme=1&submit=Log+In' \
        -O /dev/null \
        http://localhost/webacula/auth/login
    if [ ${?} -ne 0 ]
    then
        echo -e "Login ERROR!\n"
        exit 10
    fi
}


my_logout() {
    LOCALE1="${1}"
    wget --quiet --no-proxy --header='Accept-Charset: UTF-8' \
        --header="Accept-Language: ${LOCALE1}"   \
        --load-cookies cookies.txt \
        -O - \
       http://localhost/webacula/auth/logout > /dev/null
    if [ ${?} -ne 0 ]
    then
        echo -e "Logout ERROR!\n"
        exit 10
    fi
}


# for auto determination locales
my_wget() {
    my_login "${1}"
    LOCALE1="${1}"
    URL1="${2}"
    STR1="${3}"
    echo -ne "Locale ${LOCALE1} - \t"
    wget --quiet --no-proxy --header='Accept-Charset: UTF-8' \
        --header="Accept-Language: ${LOCALE1}"   \
        --load-cookies cookies.txt \
        -O - \
       "${URL1}" | grep "${STR1}" > /dev/null

    if [ ${?} -ne 0 ]
    then
        echo -e "ERROR!\n"
        exit 10
    fi
    echo -ne "OK\n"
    my_logout "${1}"
}


# for user defined locales
my_wget_def() {
    my_login "${4}"
    LOCALE1="${1}"
    URL1="${2}"
    STR1="${3}"
    DEFLOCALE1="${4}"
    echo -ne "Locale ${DEFLOCALE1} - \t"
    wget --quiet --no-proxy --header='Accept-Charset: UTF-8' \
        --header="Accept-Language: ${LOCALE1}"   \
        --load-cookies cookies.txt \
        -O - \
       "${URL1}" | grep "${STR1}" > /dev/null

    if [ ${?} -ne 0 ]
    then
        echo -e "ERROR!\n"
        # restore original conf
        cp -f ../application/config.ini.original  ../application/config.ini
        exit 10
    fi
    echo -ne "OK\n"
    my_logout "${4}"
}

my_on_exit() {
    echo "clean and exit"
    cp -f ../application/config.ini.original  ../application/config.ini
    cp -f ../html/.htaccess_original  ../html/.htaccess
    rm -f cookies.txt
    rm -f  ../data/cache/zend_cache*
    rm -f  ../data/tmp/webacula*
    rm -f  ../data/session/ses*
}




###########################################################
# Main program
###########################################################

PIDH="/var/run/httpd/httpd.pid"

echo "Check httpd..."
if [ ! -e "$PIDH" ]
then
    echo "Can't connect to httpd."
    /sbin/service httpd start
    sleep 5
fi

trap my_on_exit  0 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15

diff -q ../application/config.ini  ../application/config.ini.original
if [ $? == 0 ]
   then
      echo "OK. config.ini"
   else
      echo -e "\nMake cp ../application/config.ini ../application/config.ini.original\n\n"
      exit 11
fi

echo "copy production .htaccess"
cp -f conf/.htaccess_development  ../html/.htaccess
if test $? -ne 0; then
    exit
fi

echo -en "clean Zend cache, session, tmp files : login as root ..."
my_login  "en"
echo -en " logout ... "
my_logout "en"
echo -en "OK\n\n"

echo -e "\n${LINE1}"
echo "Testing locales and languages"
echo -e "${LINE1}\n"

echo -e "${LINE2}"
echo "Testing auto determination"
echo -e "${LINE2}\n"

my_wget "en" "http://localhost/webacula/" "Desktop"
my_wget "en-us" "http://localhost/webacula/" "Desktop"
my_wget "cs" "http://localhost/webacula/" "Přehled"
my_wget "de" "http://localhost/webacula/" "Nächste"
my_wget "fr" "http://localhost/webacula/" "Bureau"
my_wget "it" "http://localhost/webacula/" "Completati"
my_wget "es" "http://localhost/webacula/" "Escritorio"
my_wget "pt-br" "http://localhost/webacula/" "últimos"
my_wget "ru" "http://localhost/webacula/" "Панель"

echo ""
my_wget "eo" "http://localhost/webacula/" "Desktop"

# real multi locales
my_wget "en,ru;q=0.9,de;q=0.7,en-us;q=0.6,it;q=0.4,pt-br;q=0.3,eo;q=0.1" "http://localhost/webacula/" "Desktop"

echo -en '\n\E[30;42m Auto determination tests - OK                    '
tput sgr0
echo -e "\n"


echo -e "\n${LINE2}"
echo "Testing user-defined locales"
echo -e "${LINE2}\n"

APPINI="../application/config.ini"

cp -f conf/locale/config.ini.cs  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Přehled" "cs"

cp -f conf/locale/config.ini.de  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Nächste" "de"

cp -f conf/locale/config.ini.fr  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Bureau" "fr"

cp -f conf/locale/config.ini.en  "${APPINI}"
my_wget_def "it" "http://localhost/webacula/" "Desktop" "en"

cp -f conf/locale/config.ini.it  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Completati" "it"

cp -f conf/locale/config.ini.pt_BR  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "últimos" "pt_BR"

cp -f conf/locale/config.ini.es  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Escritorio" "es"

cp -f conf/locale/config.ini.ru  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Панель" "ru"

cp -f conf/locale/config.ini.fake_locale  "${APPINI}"
my_wget_def "ru" "http://localhost/webacula/" "Desktop" "fake"

echo -en '\n\E[30;42m User-defined locales tests - OK                    '
tput sgr0
echo -e "\n\n"

