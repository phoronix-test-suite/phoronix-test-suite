#!/bin/sh

tar -xvf ramspeed-2.6.0.tar.gz

echo "#!/bin/sh

rm -f *.result
./ramspeed \$@ > \$LOG_FILE.result 2>&1

case \"\$1\" in
\"COPY\")
	cat \$LOG_FILE.result | grep \"Copy\" > \$LOG_FILE
	;;
\"SCALE\")
	cat \$LOG_FILE.result | grep \"Scale\" > \$LOG_FILE
	;;
\"ADD\")
	cat \$LOG_FILE.result | grep \"Add\" > \$LOG_FILE
	;;
\"TRIAD\")
	cat \$LOG_FILE.result | grep \"Triad\" > \$LOG_FILE
	;;
\"AVERAGE\")
	cat \$LOG_FILE.result | grep \"AVERAGE\" > \$LOG_FILE
	;;
esac
" > ramspeed-benchmark
chmod +x ramspeed-benchmark

cd ramspeed-2.6.0/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
ln ramspeed ../

