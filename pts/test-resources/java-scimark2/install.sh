#!/bin/sh

cd $1

unzip -o scimark2lib.zip

echo "#!/bin/sh

rm -f *.result
java jnt.scimark2.commandline > \$THIS_RUN_TIME.result

case \"\$1\" in
\"TEST_COMPOSITE\")
	cat \$THIS_RUN_TIME.result | grep \"Composite Score\"
	;;
\"TEST_FFT\")
	cat \$THIS_RUN_TIME.result | grep \"FFT (1024)\"
	;;
\"TEST_SOR\")
	cat \$THIS_RUN_TIME.result | grep \"SOR (100x100)\"
	;;
\"TEST_MONTE\")
	cat \$THIS_RUN_TIME.result | grep \"Monte Carlo\"
	;;
\"TEST_SPARSE\")
	cat \$THIS_RUN_TIME.result | grep \"Sparse matmult\"
	;;
\"TEST_DENSE\")
	cat \$THIS_RUN_TIME.result | grep \"LU (100x100)\"
	;;
esac" > java-scimark2
chmod +x java-scimark2
