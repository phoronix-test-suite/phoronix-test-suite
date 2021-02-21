#!/bin/sh

tar -zxvf byte-benchmark-2.tar.gz
cd bm/
make clean
make
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
rm -f result
cd bm/

case \"\$1\" in
\"TEST_DHRY2\")
	./Run dhry2 > \$LOG_FILE
	;;
\"TEST_REGISTER\")
	./Run register > \$LOG_FILE
	;;
\"TEST_INT\")
	./Run int > \$LOG_FILE
	;;
\"TEST_FLOAT\")
	./Run float > \$LOG_FILE
	;;
esac

cat \$LOG_FILE | grep lps" > byte
chmod +x byte
