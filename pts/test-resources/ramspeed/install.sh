#!/bin/sh

tar -xvf ramspeed-2.5.2.tar.gz

echo "#!/bin/sh

rm -f *.result
./ramspeed \$@ > \$LOG_FILE.result

case \"\$1\" in
\"COPY\")
	cat \$LOG_FILE.result | grep \"Copy\"
	;;
\"SCALE\")
	cat \$LOG_FILE.result | grep \"Scale\"
	;;
\"ADD\")
	cat \$LOG_FILE.result | grep \"Add\"
	;;
\"TRIAD\")
	cat \$LOG_FILE.result | grep \"Triad\"
	;;
\"AVERAGE\")
	cat \$LOG_FILE.result | grep \"AVERAGE\"
	;;
esac
" > ramspeed-benchmark
chmod +x ramspeed-benchmark

cd ramspeed-2.5.2/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
ln ramspeed ../

