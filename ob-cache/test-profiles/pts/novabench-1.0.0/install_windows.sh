#!/bin/sh

/cygdrive/c/Windows/system32/cmd.exe /c novabench.msi
/cygdrive/c/Windows/system32/cmd.exe /c "C:\Program Files\Novawave\Novabench\NovabenchGUI.exe"

echo "This test profile requires a Novabench PRO or Commercial license for command-line automation. Before running this test make sure you activate your Novabench PRO/Commercial installation." > ~/install-message

echo "#!/bin/bash
cd \"C:\Program Files\Novawave\Novabench\"
./novabench.exe \$@ > \$LOG_FILE" > novabench
chmod +x novabench
