#!/bin/sh

/cygdrive/c/Windows/system32/cmd.exe /c Geekbench-5.2.3-WindowsSetup.exe

echo "This test profile requires a GeekBench PRO license for command-line automation. Before running this test make sure you activate your Geekbench installation." > ~/install-message

echo "#!/bin/bash
cd \"C:\Program Files (x86)\Geekbench 5\"
./geekbench5.exe \$@ --export-text \$LOG_FILE" > geekbench
chmod +x geekbench
