<?php

namespace ElasticsearchIndexStats\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Evaluate index sizes in Elasticsearch
 *
 */
class IndexSizesCommand extends BaseCommand
{
  // the name of the command (the part after "bin/console")
  protected static $defaultName = 'es:index-sizes';

  protected function configure(): void
  {
    parent::configure();

    $this->addOption('index-format', null, InputOption::VALUE_REQUIRED, "Index format. Examples: '.logstash-web-drupal-fastly-{YYYY-MM-DD}' '.logstash-web-*-{YYYY.MM}'");
    $this->addOption('date-rows', null, InputOption::VALUE_NONE, "Use dates are rows in output rather than columns");
  }

  protected function interact(InputInterface $input, OutputInterface $output): void
  {
    parent::interact($input, $output);

    if (!$input->hasOption('index-format') || empty($input->getOption('index-format')))
    {
      throw new \RuntimeException('You must specify --index-format');
    }

  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $client = $this->getClient($input);
    $datePlaceholder = preg_replace('/^.*(\{[^}]+\}).*/', '\1', $input->getOption('index-format'));
    $allDatesString = str_replace($datePlaceholder, '*', $input->getOption('index-format'));

    $dateMatch = trim($datePlaceholder, '{}');
    $dateMatch = str_replace('.', '\\.', $dateMatch);
    $dateMatch = str_replace('YYYY', '\d{4}', $dateMatch);
    $dateMatch = str_replace('YY', '\d{2}', $dateMatch);
    $dateMatch = str_replace('MM', '\d{2}', $dateMatch);
    $dateMatch = str_replace('DD', '\d{2}', $dateMatch);
    $indexSplit = str_replace('.', '\\.', $input->getOption('index-format'));
    $indexSplit = str_replace('*', '.*', $indexSplit);
    $indexSplit = preg_replace('/^([^{]+){.*$/', '\1', $indexSplit);
    $indexSplit = '/^(' . $indexSplit . ')(' . $dateMatch . ')/';

    $response = $client->get('/' . $allDatesString .'/_stats/store');
    $responseText = $response->getBody()->getContents();
    $json = json_decode($responseText);

    // Loop through our indices and bucket them by prefix --> Date
    $buckets = [];
    $allDateStrings = [];
    $unmatched = [];
    foreach ($json->indices as $name => $data) {
      if (preg_match($indexSplit, $name, $m)) {
        $bucket = $m[1] . '*';
        $dateString = $m[2];

        if (!isset($buckets[$bucket])) {
          $buckets[$bucket] = [];
        }
        $buckets[$bucket][$dateString] = $data->primaries->store->size_in_bytes;
        $allDateStrings[] = $dateString;
      }
      else {
        $unmatched[$name] = $data->primaries->store->size_in_bytes;
      }
    }
    ksort($buckets);
    $allDateStrings = array_unique($allDateStrings);
    sort($allDateStrings);

    if ($input->getOption('date-rows')) {
      $this->outputDateRows($allDateStrings, $buckets);
    }
    else {
      $this->outputDateColumns($allDateStrings, $buckets);
    }

    if (count($unmatched)) {
      print "\n\nUnmatched indices:\n";
      foreach ($unmatched as $name => $size) {
        print $name . "\t" . $size . "\n";
      }
    }

    return Command::SUCCESS;
  }

  protected function outputDateColumns(array $allDateStrings, array $buckets) {
    // Output tab-delimited. Date are columns, buckets are rows.

    // Header row: Dates
    print "\t" . implode("\t", $allDateStrings) . "\n";
    foreach ($buckets as $name => $data) {
      print $name . "\t";
      foreach ($allDateStrings as $dateString) {
        if (!empty($data[$dateString])) {
          print $data[$dateString];
        }
        print "\t";
      }
      print "\n";
    }
  }

  protected function outputDateRows(array $allDateStrings, array $buckets) {
    // Output tab-delimited. Date are columns, buckets are rows.

    // Header row: buckets
    print "\t" . implode("\t", array_keys($buckets)) . "\n";

    foreach ($allDateStrings as $dateString) {
      print $dateString . "\t";
      foreach ($buckets as $name => $data) {
        if (!empty($data[$dateString])) {
          print $data[$dateString];
        }
        print "\t";
      }
      print "\n";
    }
  }
}
