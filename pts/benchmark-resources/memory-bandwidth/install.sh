#!/bin/sh

cd $1

if [ ! -f bandwidth-0.13.tar.gz ]
  then
     wget http://www.phoronix-test-suite.com/benchmark-files/bandwidth-0.13.tar.gz -O bandwidth-0.13.tar.gz
fi

tar -xvf bandwidth-0.13.tar.gz

echo "#!/bin/sh

if [ ! -f \$THIS_RUN_TIME.result ]
  then
	rm -f *.result
	./bandwidth > \$THIS_RUN_TIME.result
fi

case \"\$1\" in
\"TEST_L2READ\")
	cat \$THIS_RUN_TIME.result | grep \"L2 cache sequential read\"
	;;
\"TEST_L2WRITE\")
	cat \$THIS_RUN_TIME.result | grep \"L2 cache sequential write\"
	;;
\"TEST_READ\")
	cat \$THIS_RUN_TIME.result | grep \"Main memory sequential read\"
	;;
\"TEST_WRITE\")
	cat \$THIS_RUN_TIME.result | grep \"Main memory sequential write\"
	;;
esac
" > memory-bandwidth
chmod +x memory-bandwidth


cd bandwidth-0.13/
make
ln bandwidth ../

