# webacula_bconsole.te SELinux Policy file

To integrate webacula into a SELinux enabled system (Fedora, RHEL, CentOS; SL),
you need to compile and install a SELinux policy for webacula which allows the 
bconsole calls from apache httpd server.

INSTALLATION:

1. Install required packages:
 yum install selinux-policy-devel
 
2. Compile the policy source file into a policy package file:
 make -f /usr/share/selinux/devel/Makefile

3. Activate policy package:
semodule -i webacula_bconsole.pp 

4. Cleanup
make -f /usr/share/selinux/devel/Makefile clean

