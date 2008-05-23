#!/bin/sh

cd $1

tar -xvf tandem-benchmark-1.tar.gz

echo "#!/bin/sh

rm -f tmp/*.xml
case \"\$1\" in
\"WRITE\")
	/usr/bin/time -f \"tandem_Xml Time: %e Seconds\" php tandem-benchmark/tandem_benchmark.php WRITE 2>&1
	;;
\"READ\")
	php tandem-benchmark/tandem_benchmark.php WRITE
	/usr/bin/time -f \"tandem_Xml Time: %e Seconds\" php tandem-benchmark/tandem_benchmark.php READ 2>&1
	;;
esac
" > tandem-xml
chmod +x tandem-xml

