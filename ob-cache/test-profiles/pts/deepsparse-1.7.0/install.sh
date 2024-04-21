#!/bin/sh
rm -rf ~/.cache/
pip3 install --user deepsparse==1.7.0 sparsezoo==1.7.0
~/.local/bin/sparsezoo.download zoo:nlp/text_classification/distilbert-none/pytorch/huggingface/mnli/base-none
~/.local/bin/sparsezoo.download zoo:cv/classification/resnet_v1-50/pytorch/sparseml/imagenet/base-none
~/.local/bin/sparsezoo.download zoo:nlp/token_classification/bert-base/pytorch/huggingface/conll2003/base-none
~/.local/bin/sparsezoo.download zoo:nlp/document_classification/obert-base/pytorch/huggingface/imdb/base-none
~/.local/bin/sparsezoo.download zoo:cv/segmentation/yolact-darknet53/pytorch/dbolya/coco/pruned90-none
~/.local/bin/sparsezoo.download zoo:cv/classification/resnet_v1-50/pytorch/sparseml/imagenet/base-none
~/.local/bin/sparsezoo.download zoo:cv/classification/resnet_v1-50/pytorch/sparseml/imagenet/pruned95_uniform_quant-none
~/.local/bin/sparsezoo.download zoo:cv/detection/yolov5-s/pytorch/ultralytics/coco/pruned85-none
~/.local/bin/sparsezoo.download zoo:nlp/sentiment_analysis/oberta-base/pytorch/huggingface/sst2/pruned90_quant-none
~/.local/bin/sparsezoo.download zoo:nlp/question_answering/obert-large/pytorch/huggingface/squad/pruned97_quant-none
~/.local/bin/sparsezoo.download zoo:llama2-7b-llama2_chat_llama2_pretrain-base_quantized
echo $? > ~/install-exit-status
echo "#!/bin/sh
~/.local/bin/deepsparse.benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > deepsparse
chmod +x deepsparse
