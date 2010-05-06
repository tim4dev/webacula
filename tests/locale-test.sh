#!/bin/bash
#
# Test for translate into several languages and display content
#

LINE1="*********************************************************************************************"

###########################################################
# Functions
###########################################################

# for auto determination locales
my_wget() {
    LOCALE1="${1}"
    URL1="${2}"
    STR1="${3}"
    echo -ne "Locale ${LOCALE1} - \t"
    wget --quiet --no-proxy --header='Accept-Charset: UTF-8' \
        --header="Accept-Language: ${LOCALE1}"   \
        -O - \
       "${URL1}" | grep "${STR1}" > /dev/null

    if [ ${?} -ne 0 ]
    then
        echo -e "ERROR!\n"
        exit 10
    fi
    echo -ne "OK\n"
}


# for user defined locales
my_wget_def() {
    LOCALE1="${1}"
    URL1="${2}"
    STR1="${3}"
    DEFLOCALE1="${4}"
    echo -ne "Locale ${DEFLOCALE1} - \t"
    wget --quiet --no-proxy --header='Accept-Charset: UTF-8' \
        --header="Accept-Language: ${LOCALE1}"   \
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

echo -e "\n\n"
diff -q ../application/config.ini  ../application/config.ini.original
if [ $? == 0 ]
   then
      echo "OK. config.ini"
   else
      echo -e "\nMake cp ../application/config.ini ../application/config.ini.original\n\n"
      exit 11
fi

echo -e "\n\n${LINE1}"
echo "Testing locales and languages"
echo -e "${LINE1}\n"

echo -e "\n*** Testing auto determination\n"

my_wget "en" "http://localhost/webacula/" "Desktop"
my_wget "en-us" "http://localhost/webacula/" "Desktop"
my_wget "de" "http://localhost/webacula/" "Nächste"
my_wget "fr" "http://localhost/webacula/" "Bureau"
my_wget "it" "http://localhost/webacula/" "Problemi"
my_wget "es" "http://localhost/webacula/" "Escritorio"
my_wget "pt-br" "http://localhost/webacula/" "últimos"
my_wget "ru" "http://localhost/webacula/" "Панель"

echo ""
my_wget "eo" "http://localhost/webacula/" "Desktop"

# real multi locales
my_wget "en,ru;q=0.9,de;q=0.7,en-us;q=0.6,it;q=0.4,pt-br;q=0.3,eo;q=0.1" "http://localhost/webacula/" "Desktop"

echo -e "\n---------------\nAll tests - OK\n\n"




echo -e "\n*** Testing user-defined locales\n"

APPINI="../application/config.ini"

cp -f conf/locale/config.ini.de  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Nächste" "de"

cp -f conf/locale/config.ini.fr  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Bureau" "fr"

cp -f conf/locale/config.ini.en  "${APPINI}"
my_wget_def "it" "http://localhost/webacula/" "Desktop" "en"

cp -f conf/locale/config.ini.it  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Problemi" "it"

cp -f conf/locale/config.ini.pt_BR  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "últimos" "pt_BR"

cp -f conf/locale/config.ini.es  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Escritorio" "es"

cp -f conf/locale/config.ini.ru  "${APPINI}"
my_wget_def "en" "http://localhost/webacula/" "Панель" "ru"

cp -f conf/locale/config.ini.fake_locale  "${APPINI}"
my_wget_def "ru" "http://localhost/webacula/" "Desktop" "fake"

# restore original conf
cp -f ../application/config.ini.original  ../application/config.ini

echo -e "\n---------------\nAll tests - OK\n\n"

