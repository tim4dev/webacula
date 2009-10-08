#!/bin/bash
#
# script for create rpm(s) packages
#


cd ../../..

ROOT_DIR=`pwd`
SRC_DIR="`pwd`/webacula"

echo "SRC_DIR=${SRC_DIR}"

F_INDEX_PHP="${SRC_DIR}/html/index.php"
F_SPEC="${SRC_DIR}/packaging/Fedora/webacula.spec"
F_README="${SRC_DIR}/README"
F_EXCLUDE="exclude.lst"

RPM_ROOT="${HOME}/src"
RPM_SOURCES="${RPM_ROOT}/SOURCES"
RPM_SPECS="${RPM_ROOT}/SPECS"
RPM_TMP="${RPM_ROOT}/tmp"
RPM_RPMS="${RPM_ROOT}/RPMS"
SPEC="webacula.spec"

VERSION=`grep -e "^.*define('WEBACULA_VERSION.*$" ${F_INDEX_PHP} | awk -F "'" '{print($4)}'`
VER_SPEC=`grep -e "^Version:" ${F_SPEC} | awk '{print($2)}'`
VER_README=`grep -e "^Version:" ${F_README} | awk '{print($2)}'`

if [ ${VERSION} == ${VER_SPEC} ] && [ ${VERSION} == ${VER_README} ]
   then
      echo "OK. Versions correct."
   else
      echo -e "\nVersions not match. Correct this (file/version) :\n"
      echo -e "$F_INDEX_PHP\t${VERSION}"
      echo -e "${F_SPEC}\t${VER_SPEC}"
      echo -e "${F_README}\t${VER_README}"
      echo -e "\n"
      exit 10
fi


cd ${SRC_DIR}
git archive master --prefix="webacula-${VERSION}/" | gzip > "${RPM_TMP}/webacula-${VERSION}.tar.gz"

cd ${RPM_TMP}
tar xf "webacula-${VERSION}.tar.gz"

echo "webacula-${VERSION}/library/Zend
webacula-${VERSION}/library/runme
webacula-${VERSION}/library/Zend*.tar.gz
webacula-${VERSION}/packaging
webacula-${VERSION}/tests
webacula-${VERSION}/.settings
webacula-${VERSION}/.git
webacula-${VERSION}/.gitignore
webacula-${VERSION}/.project
webacula-${VERSION}/application/config.ini
webacula-${VERSION}/install/scripts/webacula_clean_tmp_files
webacula-${VERSION}/install/.htaccess
webacula-${VERSION}/install/scripts/.htaccess
webacula-${VERSION}/docs/.htaccess
webacula-${VERSION}/application/.htaccess
webacula-${VERSION}/languages/.htaccess
webacula-${VERSION}/html/.htaccess
webacula-${VERSION}/html/test_mod_rewrite/.htaccess
" > ${F_EXCLUDE}

tar zcvpf "${RPM_SOURCES}/webacula-${VERSION}.tar.gz"  --exclude-from ${F_EXCLUDE}  "webacula-${VERSION}"

echo -e "\nclean all\n"
rm -f "${RPM_TMP}/webacula-${VERSION}.tar.gz"
rm -f "${F_EXCLUDE}"
rm -f -r "${RPM_TMP}/webacula-${VERSION}"

echo -e "\ncopy files...\n"
cd ${ROOT_DIR}
cp -p -f "${SRC_DIR}/application/config.ini" "${RPM_SOURCES}/"
cp -p -f "${SRC_DIR}/install/scripts/webacula_clean_tmp_files" "${RPM_SOURCES}/"
cp -p -f "${SRC_DIR}/packaging/Fedora/webacula.conf" "${RPM_SOURCES}/"
cp -p -f "${SRC_DIR}/packaging/Fedora/webacula.spec" "${RPM_SPECS}/"

echo -e "\n"

cd ${RPM_SPECS}
pwd
ls -la 
echo -e "\nPress Enter to rpmlint ...\n"
read
rpmlint ${SPEC}

echo -e "\nPress Enter to rpmbuild ...\n"
read
rpmbuild -ba ${SPEC}

echo -e "\n"

cd ${RPM_RPMS}/noarch
pwd
ls -la 

echo -e "\n\n*****\n***** Next instruction :\n
cd ${RPM_RPMS}/noarch
rpmlint <rpm>\n
cd ${RPM_ROOT}/SRPMS
mock -r fedora-11-i386 rebuild <src rpm>\n
see files in
/var/lib/mock
and add sign
rpm --addsign *.rpm
\n"


