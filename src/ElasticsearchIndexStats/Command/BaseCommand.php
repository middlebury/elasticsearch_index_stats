<?php

namespace ElasticsearchIndexStats\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Base command for interacting with Elasticsearch
 *
 */
abstract class BaseCommand extends Command
{

  protected function configure(): void
  {
    $this->addOption('es-host', null, InputOption::VALUE_REQUIRED, 'ElasticSearch host');
    $this->addOption('es-port', null, InputOption::VALUE_REQUIRED, 'ElasticSearch port. [9200]');
    $this->addOption('ca-cert', null, InputOption::VALUE_REQUIRED, 'ElasticSearch CA Certificate to verify');
    $this->addOption('no-verify-cert', null, InputOption::VALUE_NONE, 'Do not verify the ElasticSearch CA Certificate');
    $this->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username');
    $this->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Password');
  }

  protected function interact(InputInterface $input, OutputInterface $output): void
  {
    if (!$input->hasOption('es-host') || empty($input->getOption('es-host')))
    {
      throw new \RuntimeException('You must specify --es-host');
    }
    if (empty($input->getOption('es-port')))
    {
      $input->setOption('es-port', 9200);
    }

    if ($input->getOption('ca-cert') && $input->getOption('no-verify-cert'))
    {
      throw new \RuntimeException('You must specify --ca-cert OR --no-verify-cert, not both');
    }

    if (!$input->hasOption('user') || empty($input->getOption('user')))
    {
      throw new \RuntimeException('You must specify --user');
    }

    // Prompt for Password.
    if (!$input->hasOption('password') || empty($input->getOption('password')))
    {
      $helper = $this->getHelper('question');

      $question = new Question('ElasticSearch password for ' . $input->getOption('user') . ': ');
      $question->setValidator(function ($value) {
        if (trim($value) == '') {
            throw new \Exception('The password cannot be empty');
        }
        return $value;
      });
      $question->setHidden(true);
      $question->setMaxAttempts(20);

      $input->setOption('password', $helper->ask($input, $output, $question));
    }
  }

  protected function getClient(InputInterface $input) : Client
  {
    $guzzleArgs = [
      // 'debug' => true,
      'base_uri' => 'https://' . $input->getOption('es-host') . ':' . $input->getOption('es-port'),
      'allow_redirects' => FALSE,
      'http_errors' => FALSE,
      'headers' => [],
      'auth' => [
        $input->getOption('user'),
        $input->getOption('password'),
      ],
    ];
    if ($input->hasOption('ca-cert') && !empty($input->getOption('ca-cert'))) {
      $guzzleArgs['verify'] = $input->getOption('ca-cert');
    }
    elseif ($input->hasOption('no-verify-cert')) {
      $guzzleArgs['verify'] = FALSE;
    }
    return new Client($guzzleArgs);
  }

}
