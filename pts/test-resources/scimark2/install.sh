#!/bin/sh

unzip -o scimark2_1c.zip -d scimark2_files
cd scimark2_files/
g++ -o scimark2 -O *.c
cd ..

echo "#!/bin/sh
cd scimark2_files/

rm -f *.result

./scimark2 -large  > \$THIS_RUN_TIME.result

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$THIS_RUN_TIME.result | grep \"Composite Score\"
	;;
\"TEST_FFT\")
	cat \$THIS_RUN_TIME.result | grep \"FFT\"
	;;
\"TEST_SOR\")
	cat \$THIS_RUN_TIME.result | grep \"SOR\"
	;;
\"TEST_MONTE\")
	cat \$THIS_RUN_TIME.result | grep \"MonteCarlo\"
	;;
\"TEST_SPARSE\")
	cat \$THIS_RUN_TIME.result | grep \"Sparse matmult\"
	;;
\"TEST_DENSE\")
	cat \$THIS_RUN_TIME.result | grep \"LU\"
	;;
esac" > scimark2
chmod +x scimark2
