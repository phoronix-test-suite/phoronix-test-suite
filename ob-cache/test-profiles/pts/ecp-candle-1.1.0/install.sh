#!/bin/sh

tar -xf ECP-CANDLE-Benchmarks-0.4.tar.gz
mkdir -p Benchmarks-0.4/Data/Pilot1
mkdir -p Benchmarks-0.4/Data/Pilot2
mkdir -p Benchmarks-0.4/Data/Pilot3
cp *.csv Benchmarks-0.4/Data/Pilot1
cp 3k_run10_10us.35fs-DPPC.10-DOPC.70-CHOL.20.dir.tar.gz Benchmarks-0.4/Data/Pilot2
cp P3B1_data.tar.gz Benchmarks-0.4/Data/Pilot3
cp P3B2_data.tgz Benchmarks-0.4/Data/Pilot3
cd ~/Benchmarks-0.4/Data/Pilot2
tar -xf 3k_run10_10us.35fs-DPPC.10-DOPC.70-CHOL.20.dir.tar.gz
cd ~/Benchmarks-0.4/Data/Pilot3
tar -xf P3B1_data.tar.gz
tar -xf P3B2_data.tgz
pip3 install --user torch numpy tqdm keras tensorflow sklearn pandas matplotlib numba astropy patsy statsmodels
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd Benchmarks-0.4

case \$@ in
	\"P1B2\")
		cd Pilot1/P1B2/
		python3 ./p1b2_baseline_keras2.py
		echo \$? > ~/test-exit-status
	;;
	\"P2B1\")
		cd Pilot2/P2B1/
		python3 ./p2b1_baseline_keras2.py
		echo \$? > ~/test-exit-status
	;;
	\"P3B1\")
		cd Pilot3/P3B1/
		python3 ./p3b1_baseline_keras2.py
		echo \$? > ~/test-exit-status
	;;
	\"P3B2\")
		cd Pilot3/P3B2/
		python3 ./p3b2_baseline_keras2.py
		echo \$? > ~/test-exit-status
	;;
esac
" > ecp-candle

chmod +x ecp-candle
