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
        return 'Migra o catálogo de produtos do Checkpoint 1 para Doctrine';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('products');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 120]);
        $table->addColumn('slug', 'string', ['length' => 140]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2]);
        $table->addColumn('sku', 'string', ['length' => 60]);
        $table->addColumn('stock', 'integer', ['default' => 0, 'unsigned' => true]);
        $table->addColumn('status', 'string', ['length' => 20, 'default' => 'active']);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('updated_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku'], 'uniq_products_sku');
        $table->addIndex(['name'], 'idx_products_name');
        $table->addIndex(['slug'], 'idx_products_slug');
        $table->addIndex(['price'], 'idx_products_price');
        $table->addIndex(['status'], 'idx_products_status');
        $table->addIndex(['status', 'stock'], 'idx_products_status_stock');

        $images = $schema->createTable('product_images');
        $images->addColumn('id', 'integer', ['autoincrement' => true]);
        $images->addColumn('product_id', 'integer');
        $images->addColumn('url', 'string', ['length' => 2048]);
        $images->addColumn('thumbnail_url', 'string', ['length' => 2048, 'notnull' => false]);
        $images->addColumn('position', 'integer', ['default' => 0, 'unsigned' => true]);
        $images->addColumn('created_at', 'datetime_immutable');
        $images->setPrimaryKey(['id']);
        $images->addIndex(['product_id', 'position'], 'idx_product_images_position');
        $images->addForeignKeyConstraint('products', ['product_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('product_images');
        $schema->dropTable('products');
    }
}
