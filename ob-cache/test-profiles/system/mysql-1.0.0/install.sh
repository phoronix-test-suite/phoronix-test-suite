#!/bin/sh
if which mysqld>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: mysql server is not found on the system!"
	echo 2 > ~/install-exit-status
fi

echo "diff --git a/tests/test_run.sh b/tests/test_run.sh
index 669ed751..17ef0fac 100755
--- a/tests/test_run.sh
+++ b/tests/test_run.sh
@@ -86,6 +86,9 @@ export LUA_PATH
 if \$(command -v python >/dev/null 2>&1)
 then
     PYTHON=python
+elif \$(command -v python3 >/dev/null 2>&1)
+then
+    PYTHON=python3
 elif \$(command -v python2 >/dev/null 2>&1)
 then
     PYTHON=python2
" > sysbench-1.0.20-python3.patch

tar -xzf sysbench-1.0.20.tar.gz
cd sysbench-1.0.20
patch -p1 < ../sysbench-1.0.20-python3.patch
autoreconf -vif
./configure --with-mysql --without-gcc-arch
make -j
echo $? > ~/install-exit-status

cd $HOME

echo "#!/bin/sh
table_num=30
table_size=600000
db_pswd=hellomysql123
CORE_NUMS=\$[\$NUM_CPU_CORES * 5]
if [\$CORE_NUMS -ge 100]; then
    CORE_NUMS=\$NUM_CPU_CORES
fi
cd sysbench-1.0.20
./src/sysbench \$HOME/sysbench-1.0.20/src/lua/oltp_read_write.lua \\
                        --mysql-host=127.0.0.1 \\
                        --mysql-port=3306 \\
                        --mysql-user=test \\
                        --mysql-password=\$db_pswd \\
                        --mysql-db=test \\
                        --db-driver=mysql \\
                        --tables=\$table_num \\
                        --table-size=\$table_size \\
                        --report-interval=10 \\
                        --threads=\$NUM_CPU_CORES \\
                        prepare
echo 3 > /proc/sys/vm/drop_caches
./src/sysbench \$HOME/sysbench-1.0.20/src/lua/oltp_read_write.lua \\
                        --mysql-host=127.0.0.1 \\
                        --mysql-port=3306 \\
                        --mysql-user=test \\
                        --mysql-password=\$db_pswd \\
                        --mysql-db=test \\
                        --db-driver=mysql \\
                        --tables=\$table_num \\
                        --table-size=\$table_size \\
                        --report-interval=10 \\
                        --threads=\$CORE_NUMS \\
                        --time=120 \\
                        run > \$LOG_FILE 2>&1
./src/sysbench \$HOME/sysbench-1.0.20/src/lua/oltp_read_write.lua \\
                        --mysql-host=127.0.0.1 \\
                        --mysql-port=3306 \\
                        --mysql-user=test \\
                        --mysql-password=\$db_pswd \\
                        --mysql-db=test \\
                        --db-driver=mysql \\
                        --tables=\$table_num \\
                        --table-size=\$table_size \\
                        --report-interval=10 \\
                        --threads=\$NUM_CPU_CORES \\
                        cleanup
echo 3 > /proc/sys/vm/drop_caches

mysqld --version > ~/pts-footnote 2>/dev/null
" > mysql
chmod +x mysql
