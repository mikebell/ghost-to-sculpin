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
                InputArgument::REQUIRED,
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
        }

        $output->writeln($text);
    }

    protected function migrate($filepath)
    {
        $database = new \PDO('sqlite:'.$filepath);
        foreach ($database->query('SELECT * FROM posts') as $row) {
            $post = new \stdClass();
            $post->title = $row['title'];
            $post->slug = $row['slug'];
            $post->content = $row['markdown'];
            $post->created = $row['created_at'];
            $post->status = $row['status'];
            $post->id = $row['id'];
            $this->writePost($post, $database);
        }
    }

    protected function writePost($post, $database)
    {
        //Check if posts directory exists.
        if (!file_exists('posts')) {
            mkdir('posts', 0777, true);
        }
        //Filename - 2010-04-05-style-drupal-6-lists.md.
        $filename = date('Y-m-d', ($post->created / 1000)) . '-' . $post->slug . '.md';
        $content = '---' . PHP_EOL;
        $content .= 'title: ' . $post->title . PHP_EOL;
        $content .= 'slug: ' . $post->slug . PHP_EOL;
        if ($post->status == 'draft') {
          $content .= 'draft: true' . PHP_EOL;
        }
        $content .= $this->parseTags($post->id, $database);
        $content .= '---' . PHP_EOL;
        $content .= $post->content . PHP_EOL;

        $file = fopen('posts/' . $filename, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    protected function parseTags($pid, $database)
    {
        $tags = 'tags:' . PHP_EOL;
        foreach ($database->query('SELECT tag_id FROM posts_tags WHERE post_id = ' . $pid) as $row) {
            $tagname = $database->query('SELECT name FROM tags WHERE id = ' . $row['tag_id'])->fetch();
            if (isset($tagname)) {
                $tags .= '  - ' . $tagname['name'] . PHP_EOL;
            }
        }
        return $tags;
    }
}
