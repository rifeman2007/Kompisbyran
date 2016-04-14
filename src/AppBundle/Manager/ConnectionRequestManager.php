<?php

namespace AppBundle\Manager;

use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use JMS\DiExtraBundle\Annotation\Service;
use AppBundle\Entity\ConnectionRequestRepository;
use AppBundle\Entity\ConnectionRequest;
use AppBundle\Entity\City;
use AppBundle\Entity\User;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Service("connection_request_manager")
 */
class ConnectionRequestManager implements ManagerInterface
{
    /**
     * @var ConnectionRequestRepository
     */
    private $connectionRequestRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @InjectParams({
     *     "translator" = @Inject("translator")
     * })
     * @param ConnectionRequestRepository $connectionRequestRepository
     */
    public function __construct(ConnectionRequestRepository $connectionRequestRepository, TranslatorInterface $translator)
    {
        $this->connectionRequestRepository  = $connectionRequestRepository;
        $this->translator                   = $translator;
    }

    /**
     * @return ConnectionRequest
     */
    public function createNew()
    {
        return new ConnectionRequest();
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function save($entity)
    {
        return $this->connectionRequestRepository->save($entity);
    }

    /**
     * @param $id
     * @return null|object
     */
    public function getFind($id)
    {
        return $this->connectionRequestRepository->find($id);
    }

    /**
     * @return array
     */
    public function getFindAll()
    {
        return $this->connectionRequestRepository->findAll();
    }

    /**
     * @param $entity
     */
    public function remove($entity)
    {
        $this->connectionRequestRepository->remove($entity);
    }

    /**
     * @param City $city
     * @return array
     */
    public function getFindNewWithinCity(City $city)
    {
        return $this->connectionRequestRepository->findNewWithinCity($city);
    }

    /**
     * @param City $city
     * @return array
     */
    public function getFindEstablishedWithinCity(City $city)
    {
        return $this->connectionRequestRepository->findEstablishedWithinCity($city);
    }

    /**
     * @param User $user
     * @return null|object
     */
    public function getFindOneByUser(User $user)
    {
        return $this->connectionRequestRepository->findOneByUser($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function userHasActiveRequest(User $user)
    {
        return $this->countUserActiveRequests($user)? true: false;
    }

    /**
     * @param City $city
     * @return array
     */
    public function getFindCityStats(City $city)
    {
        return $this->connectionRequestRepository->findCityStats($city);
    }

    /**
     * @param City $city
     * @return array
     */
    public function getFindCity(City $city)
    {
        return $this->connectionRequestRepository->findCity($city);
    }

    /**
     * @param City $city
     * @param int $page
     * @return array
     */
    public function getFindPaginatedByCityResults(City $city, $page = 1)
    {
        $qb         = $this->connectionRequestRepository->findByCityQueryBuilder($city);
        $adapter    = new DoctrineORMAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(25);
        $pagerfanta->setCurrentPage($page);

        return [
            'success'           => true,
            'newUsers'          => count($this->getFindNewWithinCity($city)),
            'establishedUsers'  => count($this->getFindEstablishedWithinCity($city)),
            'results'           => $this->getCityResultsdByPagination($pagerfanta),
            'next'              => ($pagerfanta->hasNextPage()? $pagerfanta->getNextPage(): false)
        ];
    }

    /**
     * @param Pagerfanta $pagerfanta
     * @return array
     */
    private function getCityResultsdByPagination(Pagerfanta $pagerfanta)
    {
        $datas              = [];
        $connectionRequests = $pagerfanta->getCurrentPageResults();

        foreach ($connectionRequests as $connectionRequest) {
            $datas[] = [
                'request_date'  => $connectionRequest->getCreatedAt()->format('Y-m-d'),
                'name'          => $connectionRequest->getUser()->getFullName(),
                'email'         => $connectionRequest->getUser()->getEmail(),
                'category'      => ($connectionRequest->getWantToLearn()? $this->translator->trans('New'): $this->translator->trans('Established')),
                'action'        => $connectionRequest->getUser()->getId().'|'.$connectionRequest->getId()
            ];
        }

        return $datas;
    }

    /**
     * @param $id
     * @return bool
     */
    public function markAsPending($id)
    {
        $connectionRequest = $this->getFind($id);

        if ($connectionRequest instanceof ConnectionRequest) {
            $connectionRequest->setPending(true);

            $this->save($connectionRequest);

            return true;
        }

        return false;
    }

    /**
     * @param $userId
     * @return null|object
     */
    public function getFindOneUnpendingByUserId($userId)
    {
        return $this->connectionRequestRepository->findOneUnpendingByUserId($userId);
    }

    /**
     * @return array
     */
    public function getFindAllPending()
    {
        return $this->connectionRequestRepository->findAllPending();
    }

    /**
     * @return array
     */
    public function getFindAllUninspected()
    {
        return $this->connectionRequestRepository->findAllByInspected(false);
    }

    /**
     * @param $id
     * @return bool
     */
    public function markAsInspected($id)
    {
        $connectionRequest = $this->getFind($id);

        if ($connectionRequest instanceof ConnectionRequest) {
            $connectionRequest->setInspected(true);

            $this->save($connectionRequest);

            return true;
        }

        return false;
    }
}