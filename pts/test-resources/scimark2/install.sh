#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
g++ -o scimark2 -O *.c
cd ..

echo "#!/bin/sh
cd scimark2_files/

rm -f *.result

./scimark2 -large  > \$LOG_FILE

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$LOG_FILE | grep \"Composite Score\"
	;;
\"TEST_FFT\")
	cat \$LOG_FILE | grep \"FFT\"
	;;
\"TEST_SOR\")
	cat \$LOG_FILE | grep \"SOR\"
	;;
\"TEST_MONTE\")
	cat \$LOG_FILE | grep \"MonteCarlo\"
	;;
\"TEST_SPARSE\")
	cat \$LOG_FILE | grep \"Sparse matmult\"
	;;
\"TEST_DENSE\")
	cat \$LOG_FILE | grep \"LU\"
	;;
esac" > scimark2
chmod +x scimark2
