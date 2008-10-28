#!/bin/sh

tar -xvf tandem-benchmark-1.tar.gz

echo "#!/bin/sh

rm -f tmp/*.xml
case \"\$1\" in
\"WRITE\")
	\$TIMER_START
	\$PHP_BIN tandem-benchmark/tandem_benchmark.php WRITE 2>&1
	\$TIMER_STOP
	;;
\"READ\")
	php tandem-benchmark/tandem_benchmark.php WRITE
	\$TIMER_START
	\$PHP_BIN tandem-benchmark/tandem_benchmark.php READ 2>&1
	\$TIMER_STOP
	;;
esac
" > tandem-xml
chmod +x tandem-xml

