#!/bin/sh

tar -zxvf apache-ab-test-files-1.tar.gz
tar -xvf httpd-system-test-conf-1.tar.bz2
test_directory=$HOME/html
mkdir -p ${test_directory}
test_directory="`realpath ${test_directory}`"
cp $HOME/httpd.conf ${test_directory}
httpdconf=${test_directory}/httpd.conf
if [ ! -f /usr/sbin/apachectl ]; then
	echo "ERROR: Apache is not found on the system!"
	echo 2> ~/install-exit-status
exit
fi
if [ ! -f /usr/sbin/apache2ctl ]; then
	actual_server_root="`httpd -S | grep -i "ServerRoot" | cut -d' ' -f2 | cut -d'"' -f2`"
	actual_user="`httpd -S | grep -i "User" | cut -d' ' -f2 | cut -d'=' -f2 | cut -d'"' -f2`"
	actual_group="`httpd -S | grep -i "Group" | cut -d' ' -f2 | cut -d'=' -f2 | cut -d'"' -f2`"
	actual_error_log="`httpd -S | grep -i "Main ErrorLog:" | cut -d' ' -f3 | cut -d'"' -f2`"
	actual_pid_file="`httpd -S | grep -i "PidFile:" | cut -d' ' -f2`"
	path="`find / -iname mod_auth_basic.so | grep /httpd/`"
	actual_module_path="`dirname ${path}`"
else
	actual_server_root="`apache2ctl -S | grep -i "ServerRoot" | cut -d' ' -f2 | cut -d'"' -f2`"
	actual_user="`apache2ctl -S | grep -i "User" | cut -d' ' -f2 | cut -d'=' -f2 | cut -d'"' -f2`"
	actual_group="`apache2ctl -S | grep -i "Group" | cut -d' ' -f2 | cut -d'=' -f2 | cut -d'"' -f2`"
	actual_error_log="`apache2ctl -S | grep -i "Main ErrorLog:" | cut -d' ' -f3 | cut -d'"' -f2`"
	actual_pid_file="`apache2ctl -S | grep -i "PidFile:" | cut -d' ' -f2`"
	path="`find / -iname mod_auth_basic.so | grep /apache2/`"
	actual_module_path="`dirname ${path}`"
fi
if [ -f /etc/mime.types ]; then
	actual_mime_types_path=/etc/mime.types
else
	actual_mime_types_path="`find / -iname mime.types | grep httpd | head -n 1`"
fi
if [ ! -f /usr/sbin/apache2ctl ]; then
	sed -i 's|LoadModule alias_module {modules_path}/mod_alias.so|LoadModule alias_module {modules_path}/mod_alias.so'\\n'LoadModule unixd_module {modules_path}/mod_unixd.so|' ${httpdconf}
fi
mod_var=$(find / -iname mod_mpm_event.so)
if [ -z "$mod_var" ]; then
	sed -i 's|LoadModule mpm_event_module {modules_path}/mod_mpm_event.so| |' ${httpdconf}
fi
sed -i "s|{modules_path}|${actual_module_path}|" ${httpdconf}
sed -i "s|{port}|8088|" ${httpdconf}
sed -i "s|{server_root}|${actual_server_root}|" ${httpdconf}
sed -i "s|{test_directory}|${test_directory}|" ${httpdconf}
sed -i "s|{user}|${actual_user}|" ${httpdconf}
sed -i "s|{group}|${actual_group}|" ${httpdconf}
sed -i "s|{error_log}|${actual_error_log}|" ${httpdconf}
sed -i "s|{mimetypes}|${actual_mime_types_path}|" ${httpdconf}
sed -i "s|{pidfile}|${actual_pid_file}|" ${httpdconf}
mv -f test.html ${test_directory}
mv -f pts.png ${test_directory}
echo "#!/bin/sh
if [ -f /usr/bin/ab ]; then
	ab \$@ > \$LOG_FILE 2>&1
else
	ab2 \$@ > \$LOG_FILE 2>&1
fi
echo \$? > ~/test-exit-status" > apache
chmod +x apache
