<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Landlord\Event;
use App\Entity\Landlord\User;
use App\Events;
use App\Request\EntityTransformer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EventsListener implements EventSubscriberInterface
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var EntityTransformer
     */
    private $transformer;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        RegistryInterface $registry,
        EntityTransformer $transformer,
        TokenStorageInterface $tokenStorage
    ) {
        $this->registry = $registry;
        $this->transformer = $transformer;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        $reflection = new \ReflectionClass(Events::class);

        return \array_map(function () {
            return 'onEvent';
        }, \array_flip(\array_values($reflection->getConstants())));
    }

    public function onEvent(GenericEvent $event, string $name): void
    {
        $em = $this->registry->getEntityManager();

        $arguments = [];
        if (null !== $subject = $event->getSubject()) {
            $arguments['subject'] = $this->transformer->transform($subject);
        }

        foreach ($event->getArguments() as $key => $argument) {
            if (\is_object($argument)) {
                $arguments['arguments'][$key] = $this->transformer->transform($argument);
            } else {
                $arguments['arguments'][$key] = $argument;
            }
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $em->persist(new Event($name, $arguments, $user));
        $em->flush();
    }
}
