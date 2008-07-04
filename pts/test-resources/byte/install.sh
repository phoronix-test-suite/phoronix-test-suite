#!/bin/sh

tar -xvf byte-benchmark-1.tar.gz
cd bm/
make clean
make
cd ..

echo "#!/bin/sh
rm -f result
cd bm/

case \"\$1\" in
\"TEST_DHRY2\")
	./Run dhry2 > ../result
	;;
\"TEST_REGISTER\")
	./Run register > ../result
	;;
\"TEST_INT\")
	./Run int > ../result
	;;
\"TEST_FLOAT\")
	./Run float > ../result
	;;
esac

cat ../result | grep lps" > byte
chmod +x byte
