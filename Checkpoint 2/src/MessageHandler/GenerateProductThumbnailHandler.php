<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateProductThumbnail;
use App\Repository\ProductImageRepository;
use App\Service\ProductImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateProductThumbnailHandler
{
    public function __construct(
        private readonly ProductImageRepository $images,
        private readonly ProductImageStorage $storage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(GenerateProductThumbnail $message): void
    {
        $image = $this->images->find($message->imageId);
        if ($image === null || ($thumbnail = $this->storage->generateThumbnail($image)) === null) {
            return;
        }
        $image->setThumbnailUrl($thumbnail);
        $this->entityManager->flush();
    }
}
