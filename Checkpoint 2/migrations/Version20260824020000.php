<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria a fila persistente do Symfony Messenger';
    }

    public function up(Schema $schema): void
    {
        $messages = $schema->createTable('messenger_messages');
        $messages->addColumn('id', 'bigint', ['autoincrement' => true]);
        $messages->addColumn('body', 'text');
        $messages->addColumn('headers', 'text');
        $messages->addColumn('queue_name', 'string', ['length' => 190]);
        $messages->addColumn('created_at', 'datetime_immutable');
        $messages->addColumn('available_at', 'datetime_immutable');
        $messages->addColumn('delivered_at', 'datetime_immutable', ['notnull' => false]);
        $messages->setPrimaryKey(['id']);
        $messages->addIndex(
            ['queue_name', 'available_at', 'delivered_at', 'id'],
            'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750',
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('messenger_messages');
    }
}
