#!/bin/sh

unzip -o pyperformance-1.0.0.zip
cd pyperformance-1.0.0
python3 setup.py build
python3 setup.py install --user
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
~/.local/bin/pyperformance run \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pyperformance

chmod +x pyperformance
