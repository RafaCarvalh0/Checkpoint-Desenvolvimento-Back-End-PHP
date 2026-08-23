<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona categoria e índice composto para relatórios agregados';
    }

    public function up(Schema $schema): void
    {
        $products = $schema->getTable('products');
        $products->addColumn('category', 'string', ['length' => 100, 'default' => 'Sem categoria']);
        $products->addIndex(['status', 'category'], 'idx_products_status_category');
    }

    public function down(Schema $schema): void
    {
        $products = $schema->getTable('products');
        $products->dropIndex('idx_products_status_category');
        $products->dropColumn('category');
    }
}
