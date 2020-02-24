#!/bin/sh

unzip -o IozoneSetup.zip

echo "#!/bin/sh

cd \"C:\Program Files (x86)\Benchmarks\Iozone3.344\"

iozone.exe \$@ > \$LOG_FILE" > iozone

IozoneSetup.exe
