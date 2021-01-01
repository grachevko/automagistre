<?php

declare(strict_types=1);

namespace App\Google\Entity;

use App\Google\Messages\ReviewReceived;
use App\MessageBus\ContainsRecordedMessages;
use App\MessageBus\PrivateMessageRecorderCapabilities;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="google_review")
 */
class Review implements ContainsRecordedMessages
{
    use PrivateMessageRecorderCapabilities;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     */
    public UuidInterface $id;

    /**
     * @ORM\Column
     */
    public string $reviewId;

    /**
     * @ORM\Column(type="json")
     */
    public array $payload;

    public function __construct(UuidInterface $id, string $reviewId, array $payload)
    {
        $this->id = $id;
        $this->reviewId = $reviewId;
        $this->payload = $payload;

        $this->record(new ReviewReceived($this->id));
    }

    public static function create(string $reviewId, array $payload): self
    {
        return new self(
            Uuid::uuid6(),
            $reviewId,
            $payload,
        );
    }
}
