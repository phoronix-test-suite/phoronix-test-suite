#!/bin/sh
rm -rf ~/data/
mkdir ~/data/
cd ~/tpcds-kit-1b7fb7529edae091684201fab142d956d6afd881/tools/
./dsdgen -dir $HOME/data/ -scale $1 -verbose y -terminate n

rm -rf ~/queries
./genquery.sh $1 2>&1
# sometimes the first one fails oddly...
sleep 1
./genquery.sh $1 2>&1

cd ~/data
rm -rf ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/*/*.dat
mv call_center.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/call_center/
mv catalog_page.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/catalog_page/
mv catalog_returns.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/catalog_returns/
mv catalog_sales.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/catalog_sales/
mv customer_address.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/customer_address/
mv customer.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/customer
mv customer_demographics.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/customer_demographics/
mv date_dim.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/date_dim/
mv household_demographics.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/household_demographics/
mv income_band.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/income_band/
mv inventory.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/inventory/
mv item.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/item/
mv promotion.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/promotion/
mv reason.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/reason/
mv ship_mode.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/ship_mode/
mv store.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/store/
mv store_returns.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/store_returns/
mv store_sales.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/store_sales/
mv time_dim.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/time_dim/
mv warehouse.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/warehouse/
mv web_page.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/web_page/
mv web_returns.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/web_returns/
mv web_sales.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/web_sales/
mv web_site.dat ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/data/web_site/

cd ~/queries
rm -rf ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/queries/*
sed -i "s/(cast('1998-08-04' as date) +  14 days)/(date_add(cast('1998-08-04' as date), 14 ))/g" query05.sql
sed -i "s/and (cast('2001-01-12' as date) + 30 days)/and date_add(cast('1999-02-22' as date), 30 )/g" query12.sql
sed -i 's/"order count"/order_cost/g' query16.sql
sed -i 's/"total shipping cost"/total_shipping_cost/g' query16.sql
sed -i 's/"total net profit"/total_net_profit/g' query16.sql
sed -i "s/(cast('1999-2-01' as date) + 60 days)/(date_add(cast('1999-2-01' as date), 60 ))/g" query16.sql
sed -i "s/and (cast('2001-01-12' as date) + 30 days)/and date_add(cast('1999-02-22' as date), 30 )/g" query20.sql
sed -i "s/(cast ('1998-04-08' as date) + 30 days)/date_add(cast('1998-04-08' as date), 30 )/g" query21.sql
sed -i "s/(cast ('1998-04-08' as date) - 30 days)/date_sub(cast('1998-04-08' as date), 30 )/g" query21.sql
sed -i 's/IL/GA/g' query30.sql
sed -i 's/c_last_review_date_sk/c_last_review_date/g' query30.sql
sed -i 's/"excess discount amount"/excess_discount_amount/g' query32.sql
sed -i "s/(cast('1998-03-18' as date) + 90 days)/date_add(cast('1998-03-18' as date), 90 )/g" query32.sql
sed -i "s/(cast('2001-06-02' as date) +  60 days)/date_add(cast('2001-06-02' as date), 60 )/g" query37.sql
sed -i "s/(cast('1998-08-04' as date) +  30 days)/date_add(cast('1998-08-04' as date), 30 )/g" query40.sql
sed -i "s/(cast ('1998-04-08' as date) + 30 days)/date_add(cast('1998-04-08' as date), 30 )/g" query40.sql
sed -i "s/(cast ('1998-04-08' as date) - 30 days)/date_sub(cast('1998-04-08' as date), 30 )/g" query40.sql
sed -i 's/"30 days"/30_days/g' query50.sql
sed -i 's/"31-60 days"/60_days/g' query50.sql
sed -i 's/"61-90 days"/90_days/g' query50.sql
sed -i 's/"91-120 days"/120_days/g' query50.sql
sed -i 's/">120 days"/greater120_days/g' query50.sql
sed -i 's/"30 days"/30_days/g' query62.sql
sed -i 's/"31-60 days"/60_days/g' query62.sql
sed -i 's/"61-90 days"/90_days/g' query62.sql
sed -i 's/"91-120 days"/120_days/g' query62.sql
sed -i 's/">120 days"/greater120_days/g' query62.sql
sed -i "s/(cast('1998-08-04' as date) +  30 days)/date_add(cast('1998-08-04' as date), 30 )/g" query77.sql
sed -i "s/(cast('1998-08-04' as date) +  30 days)/date_add(cast('1998-08-04' as date), 30 )/g" query80.sql
sed -i "s/(cast('2002-05-30' as date) +  60 days)/date_add(cast('2002-05-30' as date), 60 )/g" query82.sql
sed -i 's/"Excess Discount Amount"/excess_discount_amount/g' query92.sql
sed -i "s/(cast('1998-03-18' as date) + 90 days)/date_add(cast('1998-03-18' as date), 90 )/g" query92.sql
sed -i 's/"order count"/order_cost/g' query94.sql
sed -i 's/"total shipping cost"/total_shipping_cost/g' query94.sql
sed -i 's/"total net profit"/total_net_profit/g' query94.sql
sed -i "s/(cast('1999-5-01' as date) + 60 days)/date_add(cast('1999-5-01' as date), 60 )/g" query94.sql
sed -i 's/"order count"/order_cost/g' query95.sql
sed -i 's/"total shipping cost"/total_shipping_cost/g' query95.sql
sed -i 's/"total net profit"/total_net_profit/g' query95.sql
sed -i "s/(cast('1999-5-01' as date) + 60 days)/date_add(cast('1999-5-01' as date), 60 )/g" query95.sql
sed -i "s/and (cast('2001-01-12' as date) + 30 days)/and date_add(cast('1999-02-22' as date), 30 )/g" query98.sql
sed -i 's/"30 days"/30_days/g' query99.sql
sed -i 's/"31-60 days"/60_days/g' query99.sql
sed -i 's/"61-90 days"/90_days/g' query99.sql
sed -i 's/"91-120 days"/120_days/g' query99.sql
sed -i 's/">120 days"/greater120_days/g' query99.sql
cp -f *.sql ~/spark-tpc-ds-performance-test-e01345571d8dc1f746bbbfc306d7578d24c87907/src/queries
