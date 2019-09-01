#!/bin/sh

python3 -m pip install pymongo

echo "
import time
import pymongo
m = pymongo.MongoClient()

doc = {'a': 1, 'b': 'hat'}

i = 0

start = time.time()
while (i < 200000):
	m.tests.insertTest.insert(doc, manipulate=False, w=1)
	i = i + 1

end = time.time()
executionTime = (end - start)
print('Total Time: ', executionTime)" > inserts.py

echo "#!/bin/sh
python3 inserts.py > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > pymongo-inserts
chmod +x pymongo-inserts
