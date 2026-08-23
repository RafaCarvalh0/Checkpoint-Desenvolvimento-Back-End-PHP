<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260823213831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria o catálogo de produtos com índices para nome e status';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 180]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('active', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('updated_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'idx_product_name');
        $table->addIndex(['active'], 'idx_product_active');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('product');
    }
}
