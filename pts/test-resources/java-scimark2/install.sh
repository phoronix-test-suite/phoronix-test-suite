#!/bin/sh

unzip -o scimark2lib.zip

echo "#!/bin/sh

rm -f *.result
java jnt.scimark2.commandline > \$LOG_FILE

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$LOG_FILE | grep \"Composite Score\"
	;;
\"TEST_FFT\")
	cat \$LOG_FILE | grep \"FFT (1024)\"
	;;
\"TEST_SOR\")
	cat \$LOG_FILE | grep \"SOR (100x100)\"
	;;
\"TEST_MONTE\")
	cat \$LOG_FILE | grep \"Monte Carlo\"
	;;
\"TEST_SPARSE\")
	cat \$LOG_FILE | grep \"Sparse matmult\"
	;;
\"TEST_DENSE\")
	cat \$LOG_FILE | grep \"LU (100x100)\"
	;;
esac" > java-scimark2
chmod +x java-scimark2
