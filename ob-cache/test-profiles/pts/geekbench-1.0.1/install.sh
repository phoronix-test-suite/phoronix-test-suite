#!/bin/sh

tar -xf Geekbench-4.3.3-Linux.tar.gz

echo "This test profile requires a GeekBench PRO license for command-line automation. Before running this test you must run: 

          cd $HOME
          ./geekbench4 -r <YOUR EMAIL> <YOUR LICENSE KEY>" > ~/install-message

echo "#!/bin/bash
cd Geekbench-4.3.3-Linux
./geekbench4 \$@ --export-text \$LOG_FILE" > geekbench
chmod +x geekbench
