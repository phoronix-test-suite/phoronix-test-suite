#!/bin/sh

tar -xvf numpy-benchmarks-20160218.tar.gz
cd numpy-benchmarks-master/
patch -p1 <<'EOT'
--- a/run.py
+++ b/run.py
@@ -111,7 +111,7 @@ def run(filenames, extractors):
                 setup, run, content = e(filename)
                 open(where, 'w').write(content)
                 e.compile(where)
-                shelllines.append('printf "{function} {extractor} " && PYTHONPATH=..:$PYTHONPATH python -m benchit -r 11 -n 40 -s "{setup}; from {module} import {function} ; {run}" "{run}" 2>/dev/null || echo unsupported'.format(setup=setup, module=tmpmodule, function=function, run=run, extractor=extractor.name))
+                shelllines.append('printf "{function} {extractor} " && PYTHONPATH=..:$PYTHONPATH python2 -m benchit -r 11 -n 40 -s "{setup}; from {module} import {function} ; {run}" "{run}" 2>/dev/null || echo unsupported'.format(setup=setup, module=tmpmodule, function=function, run=run, extractor=extractor.name))
             except:
                 shelllines.append('echo "{function} {extractor} unsupported"'.format(function=function, extractor=extractor.name))
EOT
cd ~

echo $? > ~/install-exit-status
echo "#!/bin/sh
cd numpy-benchmarks-master/
python2 run.py -t python > numpy_log
echo 'Test name :   Avg time ( nanoseconds )'
cat numpy_log | awk 'BEGIN{total_avg_time=0} {print \$1\":\"\$4;total_avg_time+=\$4;} END{printf(\"\n\n-------------------\nTotal avg time (nanoseconds): %.02f\n\", total_avg_time);}' \$@ > \$LOG_FILE
echo \$? > ~/test-exit-status " > numpy
chmod +x numpy
