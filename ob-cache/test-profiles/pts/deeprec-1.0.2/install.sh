#!/bin/sh
if which docker>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Docker is not found on the system! This test profile needs a working docker installation in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi
HOME=$DEBUG_REAL_HOME
pip3 install --user shyaml
tar -xf deeprec-cpu-benchmark-run-1.tar.xz
sed -i 's/-it//g' benchmark.sh
docker pull alideeprec/deeprec-release-modelzoo:latest
echo $? > ~/install-exit-status
echo "#!/bin/bash
export HOME=\$DEBUG_REAL_HOME 
export PATH=\$PATH:\$HOME/.local/bin
rm -f config.yaml
cp config.template.yaml config.yaml
sed -i \"s/MODEL_REPLACE/\$1/g\" config.yaml
sed -i \"s/DATA_TYPE_REPLACE/\$2/g\" config.yaml
bash benchmark.sh > \$LOG_FILE 2>&1" > deeprec
chmod +x deeprec
