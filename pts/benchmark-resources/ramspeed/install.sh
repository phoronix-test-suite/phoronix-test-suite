#!/bin/sh

cd $1

if [ ! -f ramspeed.tar.gz ]
  then
     wget http://www.alasir.com/software/ramspeed/ramspeed-2.5.1.tar.gz -O ramspeed.tar.gz
fi

tar -xvf ramspeed.tar.gz


echo "#!/bin/sh

rm -f *.result
./ramspeed \$@ > \$THIS_RUN_TIME.result

case \"\$1\" in
\"COPY\")
	cat \$THIS_RUN_TIME.result | grep \"Copy\"
	;;
\"SCALE\")
	cat \$THIS_RUN_TIME.result | grep \"Scale\"
	;;
\"ADD\")
	cat \$THIS_RUN_TIME.result | grep \"Add\"
	;;
\"TRIAD\")
	cat \$THIS_RUN_TIME.result | grep \"Triad\"
	;;
\"AVERAGE\")
	cat \$THIS_RUN_TIME.result | grep \"AVERAGE\"
	;;
esac
" > ramspeed-benchmark
chmod +x ramspeed-benchmark

cd ramspeed-2.5.1/
cat build.sh | grep -v "read ANS" > build_pts.sh
chmod +x build_pts.sh
./build_pts.sh
ln ramspeed ../

