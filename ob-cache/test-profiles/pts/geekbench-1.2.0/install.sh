#!/bin/sh

tar -xf Geekbench-*-Linux.tar.gz

echo "This test profile requires a GeekBench PRO license for command-line automation. Before running this test you must run: 

          cd $HOME/Geekbench-5.2.3-Linux
          ./geekbench5 -r <YOUR EMAIL> <YOUR LICENSE KEY>" > ~/install-message

echo "#!/bin/bash
cd Geekbench-5.2.3-Linux
./geekbench5 \$@ > \$LOG_FILE" > geekbench
chmod +x geekbench
