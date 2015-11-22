<?php

namespace MikeBell\GhostToSculpin;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{
    protected function configure()
    {
        $this
            ->setName('migrate:run')
            ->setDescription('Run Ghost to Sculpin migrate')
            ->addArgument(
                'dbpath',
                InputArgument::OPTIONAL,
                'Path to sqlite db.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filepath = $input->getArgument('dbpath');
        if ($filepath) {
            $this->migrate($filepath);
            $text = '<info>Migration successful</info>';
        } else {
            $text = '<error>Please specify a database to migrate.</error>';
        }

        $output->writeln($text);
    }
    
    protected function migrate($filepath)
    {
        $database = new \PDO('sqlite:'.$filepath);
        foreach ($database->query('SELECT * FROM posts') as $row) {
//            var_dump($row);
            $post = new \stdClass();
            $post->title = $row['title'];
            $post->slug = $row['slug'];
            //$post->tags = '';
            $post->content = $row['markdown'];
            $post->created = $row['created_at'];
            $this->writePost($post);
        }
    }

    protected function writePost($post) {
        //Check if posts directory exists.
        if (!file_exists('posts')) {
            mkdir('posts', 0777, true);
        }
        //Filename - 2010-04-05-style-drupal-6-lists.md.
        $filename = date('Y-m-d', ($post->created / 1000)) . '-' . $post->slug . '.md';
        $content = '---' . PHP_EOL;
        $content .= 'title: ' . $post->title . PHP_EOL;
        $content .= 'slug: ' . $post->slug . PHP_EOL;
//        $content += 'tags: ' . $post->slug . PHP_EOL;
        $content .= '---' . PHP_EOL;
        $content .= $post->content . PHP_EOL;

        $file = fopen('posts/' . $filename, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    protected function parseTags($pid) {

    }
}