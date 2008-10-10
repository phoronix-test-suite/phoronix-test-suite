#!/bin/sh

tar -xvf ramspeed-2.5.1.tar.gz

echo "#!/bin/sh

rm -f *.result
./ramspeed \$@ > \$LOG_FILE

case \"\$1\" in
\"COPY\")
	cat \$LOG_FILE | grep \"Copy\"
	;;
\"SCALE\")
	cat \$LOG_FILE | grep \"Scale\"
	;;
\"ADD\")
	cat \$LOG_FILE | grep \"Add\"
	;;
\"TRIAD\")
	cat \$LOG_FILE | grep \"Triad\"
	;;
\"AVERAGE\")
	cat \$LOG_FILE | grep \"AVERAGE\"
	;;
esac
" > ramspeed-benchmark
chmod +x ramspeed-benchmark

cd ramspeed-2.5.1/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
ln ramspeed ../

