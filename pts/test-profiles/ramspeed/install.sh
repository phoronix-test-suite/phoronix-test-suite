#!/bin/sh

tar -zxvf ramsmp-3.5.0.tar.gz

cd ramsmp-3.5.0/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
cd ..

echo "#!/bin/sh
cd ramsmp-3.5.0/
./ramsmp \$@ > \$LOG_FILE 2>&1" > ramspeed
chmod +x ramspeed

