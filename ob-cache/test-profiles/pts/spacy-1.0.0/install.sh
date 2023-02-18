#!/bin/sh
pip3 install --user spacy==3.4.1
echo $? > ~/install-exit-status
python3 -m spacy download en_core_web_sm
python3 -m spacy download en_core_web_lg
python3 -m spacy download en_core_web_trf

tar -xf spacy_benchmarks-1.tar.xz
echo "#!/bin/sh
python3 spacy_benchmarks.py \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > spacy
chmod +x spacy
