#!/bin/sh

chmod +x redshift_v3.0.28_linux_demo.run
./redshift_v3.0.28_linux_demo.run --target redshift-install
cd redshift-install
echo "\n" | ./setup.sh --eula accept --installpath /usr/redshift

cd ~
tar -xf RedshiftBenchmarkScenes.tar.gz

rm -rf redshift-install

echo "#!/bin/sh
export REDSHIFT_LOCALDATAPATH=\$HOME
/usr/redshift/bin/redshiftBenchmark RedshiftBenchmarkScenes/vultures/Vultures.rs > \$LOG_FILE
echo \$? > ~/test-exit-status" > redshift
chmod +x redshift

