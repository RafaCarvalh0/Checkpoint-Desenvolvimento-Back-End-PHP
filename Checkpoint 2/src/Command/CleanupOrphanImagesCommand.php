<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\CleanupOrphanImages;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:media:cleanup', description: 'Enfileira a limpeza de imagens órfãs')]
final class CleanupOrphanImagesCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bus->dispatch(new CleanupOrphanImages());
        $output->writeln('<info>Limpeza enfileirada.</info>');
        return Command::SUCCESS;
    }
}
