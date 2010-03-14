#!/bin/sh

tar -zxvf ramspeed-2.6.0.tar.gz

cd ramspeed-2.6.0/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
cd ..

echo "#!/bin/sh
cd ramspeed-2.6.0/
./ramspeed \$@ > \$LOG_FILE 2>&1" > ramspeed
chmod +x ramspeed

