#!/bin/sh

unzip -o pyperformance-1.0.0.zip
cd pyperformance-1.0.0
$DEBUG_REAL_HOME/AppData/Local/Programs/Python/Python37/python.exe  setup.py build
$DEBUG_REAL_HOME/AppData/Local/Programs/Python/Python37/python.exe  setup.py install
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cmd.exe /c \"\$DEBUG_REAL_HOME\AppData\Local\Programs\Python\Python37\Scripts\pyperformance.exe\" run \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pyperformance

chmod +x pyperformance
