#!/bin/sh

unzip -o scimark2lib.zip

echo "#!/bin/sh

rm -f *.result
java jnt.scimark2.commandline > \$LOG_FILE.result

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$LOG_FILE.result | grep \"Composite Score\"
	;;
\"TEST_FFT\")
	cat \$LOG_FILE.result | grep \"FFT (1024)\"
	;;
\"TEST_SOR\")
	cat \$LOG_FILE.result | grep \"SOR (100x100)\"
	;;
\"TEST_MONTE\")
	cat \$LOG_FILE.result | grep \"Monte Carlo\"
	;;
\"TEST_SPARSE\")
	cat \$LOG_FILE.result | grep \"Sparse matmult\"
	;;
\"TEST_DENSE\")
	cat \$LOG_FILE.result | grep \"LU (100x100)\"
	;;
esac" > java-scimark2
chmod +x java-scimark2
