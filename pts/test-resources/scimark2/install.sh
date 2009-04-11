#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
g++ -o scimark2 -O *.c
cd ..

echo "#!/bin/sh
cd scimark2_files/

rm -f *.result

./scimark2 -large > \$LOG_FILE.result 2>&1

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$LOG_FILE.result | grep \"Composite Score\" > \$LOG_FILE
	;;
\"TEST_FFT\")
	cat \$LOG_FILE.result | grep \"FFT\" > \$LOG_FILE
	;;
\"TEST_SOR\")
	cat \$LOG_FILE.result | grep \"SOR\" > \$LOG_FILE
	;;
\"TEST_MONTE\")
	cat \$LOG_FILE.result | grep \"MonteCarlo\" > \$LOG_FILE
	;;
\"TEST_SPARSE\")
	cat \$LOG_FILE.result | grep \"Sparse matmult\" > \$LOG_FILE
	;;
\"TEST_DENSE\")
	cat \$LOG_FILE.result | grep \"LU\" > \$LOG_FILE
	;;
esac" > scimark2
chmod +x scimark2
