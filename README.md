# elasticsearch_index_stats
Tool for extracting information about ElasticSearch indices, such as storage-size per index.

# Installation
## Dependencies
- PHP 7.4+
- [Composer](https://getcomposer.org/)
- Git

## Installation steps
1. Clone this repository:
   ```
   git clone https://github.com/middlebury/elasticsearch_index_stats.git
   ```
2. Change to the new directory:
   ```
   cd elasticsearch_index_stats
   ```
3. Install dependencies:
   ```
   composer install
   ```

# Usage

The `./bin/eis` command is the entry point for this package.

Subcommands:
- `es:index-sizes` - Show storage sizes of indexes.

## Export sizes of indexes that are per-month:
```
./bin/eis es:index-sizes --es-host=myhost.domain.edu --user=username --password --index-format='.logstash-web-drupal-*-{YYYY.MM}' --date-rows
```

## Export sizes of indexes that are per-day:
```
./bin/eis es:index-sizes --es-host=myhost.domain.edu --user=username --password --index-format='.logstash-web-drupal-fastly-{YYYY-MM-DD}' --date-rows
```
