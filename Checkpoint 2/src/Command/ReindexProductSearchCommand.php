<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchIndexInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:search:reindex-products', description: 'Reconstrói o índice de busca de produtos.')]
final class ReindexProductSearchCommand extends Command
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductSearchIndexInterface $searchIndex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->searchIndex->initialize();
        $indexed = 0;
        do {
            $batch = $this->products->findFiltered([], limit: 50, offset: $indexed);
            foreach ($batch as $product) {
                $this->searchIndex->upsert($product);
                ++$indexed;
            }
        } while (count($batch) === 50);
        $output->writeln(sprintf('%d produtos indexados via %s.', $indexed, $this->searchIndex->driver()));
        return Command::SUCCESS;
    }
}
