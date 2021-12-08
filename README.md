# elasticsearch_index_stats
Tool for extracting information about elasticsearch indices.

# Usage

## Example - export sizes of indexes that are per-month:
```
./bin/eis es:index-sizes --es-host=myhost.domain.edu --user=username --password='mypassword' --index-format='.logstash-web-drupal-*-{YYYY.MM}' --date-rows
```

## Example - export sizes of indexes that are per-day:
```
./bin/eis es:index-sizes --es-host=myhost.domain.edu --user=username --password='mypassword' --index-format='.logstash-web-drupal-fastly-{YYYY-MM-DD}' --date-rows
```
