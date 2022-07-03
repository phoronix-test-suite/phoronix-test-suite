#!/bin/sh

unzip -o ethr_windows_100.zip
mv ethr.exe ethr_run.exe

echo "#!/bin/sh
cmd /c ethr_run.exe \$@ > \$LOG_FILE" > ethr
chmod +x ethr
