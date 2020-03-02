#!/bin/sh

pip3 install timeout-decorator

unzip -o mlpack-benchmarks-20200110.zip
cd benchmarks-master/
mv datasets/dataset-urls.txt datasets/dataset-urls.txt.bk
cat datasets/dataset-urls.txt.bk|grep "webpage" > datasets/dataset-urls.txt
cat datasets/dataset-urls.txt.bk|grep "reuters" >> datasets/dataset-urls.txt
make datasets
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd benchmarks-master/
case \$@ in
        \"SCIKIT_SVM\")
                cat test.yaml |grep -A 9 \"\$@\" > ./\$@.yaml
                sed -i -e '/oilspill_train/d' -e 's/iris/webpage/g' \$@.yaml
        ;;
        \"SCIKIT_LINEARRIDGEREGRESSION\")
                cat test.yaml |grep -A 9 \"\$@\" > ./\$@.yaml
                sed -i -e 's/sickEuthyroid/reuters/g' \$@.yaml
        ;;
        \"SCIKIT_QDA\")
                cat test.yaml |grep -A 9 \"\$@\" > ./\$@.yaml
                sed -i -e '/oilspill/d' -e 's/iris/reuters/g' \$@.yaml
        ;;
        \"SCIKIT_ICA\")
                cat test.yaml |grep -A 9 \"\$@\" > ./\$@.yaml
                sed -i -e '/wine/d' -e 's/iris/webpage_train/g' \$@.yaml
        ;;
esac
python3 run.py -c \$@.yaml > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mlpack
chmod +x mlpack
