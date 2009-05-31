#!/bin/sh

version=8.3.7

if [ ! -e postgresql-${version}.tar.bz2 ]; then
    echo Please download postgresql-${version}.tar.bz2 into $(pwd)
    echo "from http://www.postgresql.org/ftp/source/"
    exit 1;
fi

tar xjf postgresql-${version}.tar.bz2 

PG=$(pwd)/pg_
if [ -d $PG ]; then
    rm -rf $PG
fi
mkdir $PG

cd postgresql-${version}
./configure --prefix=$PG --datadir=$PG/data --without-readline --without-zlib
make 
make -C contrib/pgbench all
echo $? > ~/install-exit-status
make install
make -C contrib/pgbench install
cd ..
rm -rf postgresql-${version}/

PGDATA=$PG/data/db
# initialize database with encoding and locale
$PG/bin/initdb -D $PGDATA --encoding=SQL_ASCII --locale=C


echo "#!/bin/sh
PGDATA=$PGDATA
PGPORT=7777
export PGDATA
export PGPORT
# start server
$PG/bin/pg_ctl start -o '-c checkpoint_segments=8 -c autovacuum=false'
# wait for server to start
sleep 30
# create test db
$PG/bin/createdb pgbench
# set up tables
$PG/bin/pgbench -i -s \$NUM_CPU_JOBS pgbench

# run the test 
$PG/bin/pgbench -t 30000 -c \`expr \$NUM_CPU_JOBS / 2\` pgbench >\$LOG_FILE 
# drop test db
$PG/bin/dropdb pgbench
# stop server
$PG/bin/pg_ctl stop" > pgbench
chmod +x pgbench

