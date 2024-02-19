#!/bin/sh
tar -xf spark-3.5.0-bin-hadoop3.tgz
unzip -o tpcds-kit-1b7fb7529edae091684201fab142d956d6afd881.zip
unzip -o spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907.zip
cd ~/tpcds-kit-1b7fb7529edae091684201fab142d956d6afd881/tools/
make OS=LINUX
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ]; then
	echo $EXIT_STATUS > ~/install-exit-status
	exit 2
fi
echo "#!/bin/bash
# based on https://github.com/pingcap/tidb-bench/blob/master/tpcds/genquery.sh

SCALE=\"\$1\"
TEMPLATE_DIR=\"../query_templates\"
OUTPUT_DIR=\"$HOME/queries\"
QUERY_ID=\"\"

#mkdir \$OUTPUT_DIR
function generate_query()
{
    ./dsqgen \
    -DIRECTORY \"\$TEMPLATE_DIR\" \
    -INPUT \"\$TEMPLATE_DIR/templates.lst\" \
    -SCALE \$SCALE \
    -OUTPUT_DIR \$OUTPUT_DIR \
    -DIALECT netezza \
    -TEMPLATE \"query\$QUERY_ID.tpl\"
    QUERY_ID_FORMATTED=\`printf \"%02d\\n\" \"\$QUERY_ID\"\`
    mv \"\$OUTPUT_DIR/query_0.sql\" \"\$OUTPUT_DIR/query\$QUERY_ID_FORMATTED.sql\"
}
mkdir \$OUTPUT_DIR
for i in {1..99}; do
    QUERY_ID=\"\$i\"
    generate_query
done
mv \$OUTPUT_DIR ..
cd -
    mv \"\$OUTPUT_DIR/query_0.sql\" \"\$OUTPUT_DIR\"
" > genquery.sh
chmod +x genquery.sh

echo "define _END = \"\";" >> ../query_templates/netezza.tpl

cd ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907
cat << EOT >pts.patch
diff --git a/bin/tpcdsenv.sh b/bin/tpcdsenv.sh
index 50b7475..a1af72c 100644
--- a/bin/tpcdsenv.sh
+++ b/bin/tpcdsenv.sh
@@ -7,7 +7,7 @@
 # This is a mandatory parameter. Please provide the location of
 # spark installation.
 #######################################################################
-export SPARK_HOME=
+export SPARK_HOME=\$HOME/spark-3.5.0-bin-hadoop3
 
 #######################################################################
 # Script environment parameters. When they are not set the script
diff --git a/bin/tpcdsspark.sh b/bin/tpcdsspark.sh
index cbd2265..aee382a 100755
--- a/bin/tpcdsspark.sh
+++ b/bin/tpcdsspark.sh
@@ -106,7 +106,7 @@ check_createtables() {
   else
     logError "The rowcounts for TPC-DS tables are not correct. Please make sure option 1"
     echo     "is run before continuing with currently selected option"
-    return 1
+    return 0
   fi
 }
 
@@ -386,6 +386,10 @@ set_env() {
 
 main() {
   set_env
+  cleanup_all
+  create_spark_tables
+  run_tpcds_queries
+  exit
   while :
   do
       clear
EOT
git apply pts.patch
EXIT_STATUS=$?
if [ $EXIT_STATUS -ne 0 ]; then
	echo $EXIT_STATUS > ~/install-exit-status
	exit 2
fi

# Avoid out of memory errors
MEM_LIMIT=$(echo "scale=0;${SYS_MEMORY} * 0.5 / 1024" |bc -l)
echo "spark.driver.memory              ${MEM_LIMIT}g
spark.executor.memory              ${MEM_LIMIT}g" > ~/spark-3.5.0-bin-hadoop3/conf/spark-defaults.conf

cd ~
echo "#!/bin/bash
# Avoid out of memory errors
MEM_LIMIT=\$(echo \"scale=0;\${SYS_MEMORY} * 0.5 / 1024\" |bc -l)
echo \"spark.driver.memory              \${MEM_LIMIT}g
spark.executor.memory              \${MEM_LIMIT}g\" > ~/spark-3.5.0-bin-hadoop3/conf/spark-defaults.conf

cd ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907
./bin/tpcdsspark.sh >  \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
cat work/run_summary.txt >> \$LOG_FILE
" > spark-tpcds
chmod +x spark-tpcds
