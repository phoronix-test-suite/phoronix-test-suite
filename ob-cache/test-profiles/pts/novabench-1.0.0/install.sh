#!/bin/sh

tar -xf novabench-linux.tar.gz

echo "This test profile requires a Novabench PRO or Commercial license for command-line automation. Before running this test make sure you activate your Novabench PRO/Commercial installation. You can run the ./activate_license script within $HOME/novabench-linux-*." > ~/install-message

echo "#!/bin/bash
cd novabench-linux-4.0.0
./novabench \$@ > \$LOG_FILE" > novabench
chmod +x novabench
