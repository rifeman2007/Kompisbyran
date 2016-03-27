<?php

namespace AppBundle\Manager;

use Knp\Component\Pager\Paginator;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use AppBundle\Entity\ConnectionRepository;
use AppBundle\Entity\Connection;
use AppBundle\Entity\ConnectionRequest;
use AppBundle\Entity\User;
use AppBundle\DomainEvents;
use AppBundle\Event\ConnectionCreatedEvent;

/**
 * @Service("connection_manager")
 */
class ConnectionManager implements ConnectionManagerInterface
{
    /**
     * @var ConnectionRepository
     */
    private $connectionRepository;

    /**
     * @var \Knp\Component\Pager\Paginator
     */
    private $paginator;

    private $dispatcher;

    /**
     * @InjectParams({
     *      "paginator" = @Inject("knp_paginator"),
     *      "dispatcher" = @Inject("event_dispatcher")
     * })
     * @param ConnectionRepository $connectionRepository
     */
    public function __construct(ConnectionRepository $connectionRepository, Paginator $paginator, $dispatcher)
    {
        $this->connectionRepository = $connectionRepository;
        $this->paginator            = $paginator;
        $this->dispatcher           = $dispatcher;
    }

    /**
     * @return Connection
     */
    public function createNew()
    {
        return new Connection();
    }

    /**
     * @param Connection $connection
     * @return mixed
     */
    public function save(Connection $connection)
    {
        return $this->connectionRepository->save($connection);
    }

    /**
     * @param ConnectionRequest $userRequest
     * @param ConnectionRequest $matchUserRequest
     * @param User $loggedUser
     * @return mixed
     */
    public function saveByConnectionRequest(ConnectionRequest $userRequest, ConnectionRequest $matchUserRequest, User $loggedUser)
    {
        $connection                 = $this->createNew();
        list($learner, $speaker)    = $this->getLearnerSpeaker($userRequest, $matchUserRequest);

        $connection->setCreatedBy           ( $loggedUser               );
        $connection->setLearner             ( $learner->getUser()       );
        $connection->setFluentSpeaker       ( $speaker->getUser()       );
        $connection->setCity                ( $learner->getCity()       );
        $connection->setFluentSpeakerComment( $speaker->getComment()    );
        $connection->setLearnerComment      ( $learner->getComment()    );
        $connection->setMusicFriend         ( $learner->isMusicFriend() );

        $this->save($connection);

        $this->dispatcher->dispatch(
            DomainEvents::CONNECTION_CREATED,
            new ConnectionCreatedEvent($connection)
        );

        return $connection;
    }

    /**
     * @param User $user
     * @param User $matchUser
     * @return int
     */
    public function getIsUserConnectionExists(User $user, User $matchUser)
    {
        return $this->connectionRepository->isUserConnectionExists($user, $matchUser);
    }

    /**
     * @param ConnectionRequest $userRequest
     * @param ConnectionRequest $matchUserRequest
     * @return array
     */
    public function getLearnerSpeaker(ConnectionRequest $userRequest, ConnectionRequest $matchUserRequest)
    {
        $learner    = $userRequest;
        $speaker    = $matchUserRequest;

        if (!$userRequest->getWantToLearn()) {
            $learner = $matchUserRequest;
            $speaker = $userRequest;
        }

        return [
            $learner,
            $speaker
        ];
    }
}