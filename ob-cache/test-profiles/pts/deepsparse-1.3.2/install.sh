#!/bin/sh
rm -rf ~/.cache/
pip3 install --user deepsparse==1.3.2 sparsezoo==1.3.1
~/.local/bin/sparsezoo.download zoo:nlp/text_classification/bert-base/pytorch/huggingface/sst2/base-none
~/.local/bin/sparsezoo.download zoo:nlp/text_classification/distilbert-none/pytorch/huggingface/mnli/base-none
~/.local/bin/sparsezoo.download zoo:cv/classification/resnet_v1-50/pytorch/sparseml/imagenet/base-none
~/.local/bin/sparsezoo.download zoo:nlp/token_classification/bert-base/pytorch/huggingface/conll2003/base-none
~/.local/bin/sparsezoo.download zoo:nlp/question_answering/bert-base/pytorch/huggingface/squad/12layer_pruned90-none
~/.local/bin/sparsezoo.download zoo:cv/detection/yolov5-s/pytorch/ultralytics/coco/base-none
~/.local/bin/sparsezoo.download zoo:nlp/document_classification/obert-base/pytorch/huggingface/imdb/base-none
~/.local/bin/sparsezoo.download zoo:nlp/sentiment_analysis/bert-base/pytorch/huggingface/sst2/12layer_pruned90-none
~/.local/bin/sparsezoo.download zoo:cv/segmentation/yolact-darknet53/pytorch/dbolya/coco/pruned90-none
echo $? > ~/install-exit-status
echo "#!/bin/sh
~/.local/bin/deepsparse.benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > deepsparse
chmod +x deepsparse
