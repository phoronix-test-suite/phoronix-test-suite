#!/bin/bash

if which pip3 >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Python pip3 is not found on the system! This test profile needs Python pip3 to proceed."
	echo 2 > ~/install-exit-status
fi

pip3 install --user tensorflow==2.9.1
pip3 install --user ai-benchmark==0.1.2

if [[ ! -f "$HOME/.local/bin/ai-benchmark" ]]
then
	echo "ERROR: AI-Benchmark failed to install on the system!"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/bash

cd \$HOME/Library/Python/3.*/bin/
python3 ./ai-benchmark > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ai-benchmark
chmod +x ai-benchmark
