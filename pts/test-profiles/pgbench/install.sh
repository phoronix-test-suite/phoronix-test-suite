#!/bin/sh

version=9.0.1

if [ ! -e postgresql-${version}.tar.bz2 ]; then
    echo Please download postgresql-${version}.tar.bz2 into $(pwd)
    echo "from http://www.postgresql.org/ftp/source/"
    exit 1;
fi

tar -xjf postgresql-${version}.tar.bz2 

rm -rf $HOME/pg_
mkdir $HOME/pg_

cd postgresql-${version}
./configure --prefix=$HOME/pg_ --datadir=$HOME/pg_/data --without-readline --without-zlib
make -j $NUM_CPU_JOBS
make -C contrib/pgbench all
echo $? > ~/install-exit-status
make install
make -C contrib/pgbench install
cd ..
rm -rf postgresql-${version}/
rm -rf pg_/doc/

# initialize database with encoding and locale
$HOME/pg_/bin/initdb -D $HOME/pg_/data/db --encoding=SQL_ASCII --locale=C


echo "#!/bin/sh
PGDATA=\$HOME/pg_/data/db/
PGPORT=7777
export PGDATA
export PGPORT
# start server
pg_/bin/pg_ctl start -o '-c checkpoint_segments=8 -c autovacuum=false'
# wait for server to start
sleep 30
# create test db
pg_/bin/createdb pgbench
# set up tables
pg_/bin/pgbench -i -s \$NUM_CPU_JOBS pgbench

# run the test 
pg_/bin/pgbench -t 30000 -c \`expr \$NUM_CPU_JOBS / 2\` pgbench >\$LOG_FILE 
# drop test db
pg_/bin/dropdb pgbench
# stop server
pg_/bin/pg_ctl stop" > pgbench
chmod +x pgbench

