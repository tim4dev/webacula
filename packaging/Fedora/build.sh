#!/bin/bash
#
# script for create rpm(s) packages
#

LANG=C

# for rpmlint
# export http_proxy=http://user:password@proxy:port/

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
mkdir -p "${RPM_TMP}/webacula-${VERSION}"

echo "library/Zend
nbproject
docs/src
library/Zend.arc
library/runme
library/Zend*.tar.gz
packaging
tests
.htaccess
.settings
.git
.gitignore
.project
.buildpath
*~
*.tmp
*.swp
" > "${RPM_TMP}/${F_EXCLUDE}"

echo "create tarball..."

rsync -a --exclude-from="${RPM_TMP}/${F_EXCLUDE}"  . "${RPM_TMP}/webacula-${VERSION}"
echo -e "\n*** exit=$?"

rm -f "${RPM_TMP}/${F_EXCLUDE}"

cd ${RPM_TMP}
tar zcvpf "${RPM_SOURCES}/webacula-${VERSION}.tar.gz"  "webacula-${VERSION}"
echo -e "\n*** exit=$?"


echo -e "\nclean all\n"
rm -f "${RPM_TMP}/webacula-${VERSION}.tar.gz"
rm -f -r "${RPM_TMP}/webacula-${VERSION}"


echo -e "\ncopy files...\n"
cd ${ROOT_DIR}
cp -p -f "${SRC_DIR}/packaging/Fedora/webacula.spec" "${RPM_SPECS}/"

echo -e "\n"

cd ${RPM_SPECS}
pwd
ls -la 
echo -e "\nPress Enter to rpmlint ..."
read

rpmlint ${SPEC}
echo -e "\n*** exit=$?"

echo -e "\nPress Enter to rpmbuild ..."
read

# fix error: unpacking of archive failed on file XXX cpio: MD5 sum mismatch
rpmbuild --define "_source_filedigest_algorithm md5"  --define "_binary_filedigest_algorithm md5" -ba ${SPEC}
echo -e "\n*** exit=$?"





echo -e "\n\n\n**************************************************************"
echo -e "***** Next instruction :\n

rpmlint -i ${RPM_RPMS}/noarch/webacula-

Install rpm and testing.
rpm -ihv ${RPM_RPMS}/noarch/webacula-\n

mock -r fedora-14-i386 rebuild  ${RPM_ROOT}/SRPMS/webacula-\n
mock -r fedora-15-i386 rebuild  ${RPM_ROOT}/SRPMS/webacula-\n
mock -r fedora-16-i386 rebuild  ${RPM_ROOT}/SRPMS/webacula-\n
mock -r epel-5-i386    rebuild  ${RPM_ROOT}/SRPMS/webacula-\n
mock -r epel-6-i386    rebuild  ${RPM_ROOT}/SRPMS/webacula-\n

see result:

ls -la /var/lib/mock/fedora-*/result/

Add sign

rpm --addsign *.rpm
\n"


