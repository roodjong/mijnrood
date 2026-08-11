<?php

namespace App\Controller;

use App\Entity\{ Member, Event, EventAttendant};

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{ JsonResponse, Response, Request };
use Symfony\Component\Routing\Annotation\Route;

use DateTime;

class CheckInController extends AbstractController
{
    /**
    * @Route("/evenement/{eventId<\d+>}", name="event_inchecker", options={"expose": true},)
    */
    public function index(Request $request, $eventId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je mag hier niet komen.');
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        $contributionEnabled = $this->getParameter('app.contributionEnabled');

        return $this->render('user/check-in.html.twig', 
        [
            'event' => $event,
            'stats' => $this->getAttendentInfo($event),
	        'contributionEnabled' => $contributionEnabled,
        ]);
    }

    /**
     * Get the attendant data so we know how much people reserved and are checked in.
     */
    private function getAttendentInfo(Event $event)
    {
        return [
            'reservations'=> count($event->getReservedAttendants()),
            'checkedInWithReservations'=> count($event->getCheckedInWithReservation()),
            'checkedInWithoutReservations'=> count($event->getCheckedInWithoutReservation()),
        ];
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/stats", name="event_stats", options={"expose": true}, methods={"GET"})
    */
    public function getStats(Request $request, $eventId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je mag hier niet komen.');
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        return new JsonResponse($this->getAttendentInfo($event));
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/checkin", name="event_check_in", options={"expose": true}, methods={"POST"})
    */
    public function checkin(Request $request, $eventId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je mag hier niet komen.');
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        if (!$request->request->has('userId'))
            return $this->json(['status' => 'missing-fields', 'missing-fields' => ['userId']]);

        # if admin or assigned to this event
        $userId = $request->request->get('userId');
        $user = $this->getDoctrine()->getRepository(Member::class)->find($userId);
        $this->checkAttendant($event, $user, true);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/checkout", name="event_check_out", options={"expose": true}, methods={"POST"})
    */
    public function checkout(Request $request, $eventId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je mag hier niet komen.');
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        if (!$request->request->has('userId'))
            return $this->json(['status' => 'missing-fields', 'missing-fields' => ['userId']]);

        # if admin or assigned to this event
        $userId = $request->request->get('userId');
        $user = $this->getDoctrine()->getRepository(Member::class)->find($userId);
        $this->checkAttendant($event, $user, false);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Check a member in or out on the given event
     */
    private function checkAttendant(Event $event, Member $member, bool $checkedIn){
        $attendant = $this->getDoctrine()->getRepository(EventAttendant::class)
        ->findOneBy(['event' => $event, 'member' => $member]);
        if ($attendant == null){
            $attendant = new EventAttendant();
            $attendant->setMember($member);
            $attendant->setEvent($event);
        }

        $attendant->setCheckedIn($checkedIn);

        $em = $this->getDoctrine()->getManager();
        $em->persist($attendant);
        $em->flush();
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/reserve", name="event_reserve", options={"expose": true}, methods={"POST"})
    */
    public function reserve(Request $request, $eventId): Response
    {
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        $attendant = $this->getDoctrine()->getRepository(EventAttendant::class)
        ->findOneBy(['event' => $event, 'member' => $member]);
        if ($attendant == null) {    
            $attendant = new EventAttendant();
            $attendant->setMember($member);
            $attendant->setEvent($event);
        }
        $attendant->setReserved(new DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($attendant);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/dereserve", name="event_dereserve", options={"expose": true}, methods={"POST"})
    */
    public function dereserve(Request $request, $eventId): Response
    {
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        $attendant = $this->getDoctrine()->getRepository(EventAttendant::class)
        ->findOneBy(['event' => $event, 'member' => $member]);

        $attendant->setReserved(null);
    
        $em = $this->getDoctrine()->getManager();
        $em->persist($attendant);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @Route("/evenement/{eventId<\d+>}/attendees", name="event_attendees", options={"expose": true}, methods={"GET"})
    */
    public function getAttendees(Request $request, $eventId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException('Je mag hier niet komen.');
        $member = $this->getUser();
        $event = $this->getDoctrine()->getRepository(Event::class)->find($eventId);

        // get arguments from request
        $searchTerm = $request->query->get('search', "");
        $ascCols = $request->query->all('ascending', []);
        $descCols = $request->query->all('descending', []);
        $page = $request->query->get('page', 1);

        // create the select query, to be reused by where clauses
        $selectQuery = [
            "id" => 'm.id',
            "firstname" => 'm.firstName',
            "lastname" => 'm.lastName',
            "fullLastname" => 'TRIM(
                  CONCAT(m.middleName, \' \', m.lastName)
                  )',
            "fullname" => 'CONCAT(
                 TRIM(
                  CONCAT(m.firstName, \' \', m.middleName)
                  ), 
                  \' \', 
                  m.lastName
                )',
            "divisionName" => 'COALESCE(d.name, \'\')',
            "reserved" => 'CASE WHEN ea.reserved IS NOT NULL THEN true ELSE false END',
            "checkedIn" => 'COALESCE(ea.checkedIn, false)',
        ];

        // build base query
        $stmts = [];
        foreach ($selectQuery as $alias => $stmt) {
            array_push($stmts, $stmt . " AS " . $alias);
        }
        $selectString = join(', ', $stmts);
        $query = $this->getDoctrine()->getRepository(Member::class)
            ->createQueryBuilder('m')
            ->select($selectString)
            ->leftJoin('m.division', 'd')
            ->leftJoin('m.eventsAttended', 'ea', 'WITH', 'ea.event = :eventId')
            ->setParameter('eventId', $eventId);
            
        // add search condition to query, if given
        if (strlen($searchTerm) > 0){
            $query
            ->where($selectQuery['fullname'] .' LIKE :nameSearch')
            ->setParameter('nameSearch', '%' . $searchTerm . '%')
            ->orWhere($selectQuery['divisionName'] . ' LIKE :divisionSearch')
            ->setParameter('divisionSearch', '%' . $searchTerm . '%');
        }

        // Get the amount of results of the query
        $count = (clone $query)->select('count(m.id)')->getQuery()->getSingleScalarResult();

        // get the columns to orderby
        $cols = [
            // name in request => columns in query
            'isCheckedIn' =>'checkedIn',
            'isReserved' =>'reserved',
            'division' =>'divisionName',
            'firstname' =>'firstname',
            'lastname' =>'lastname',
        ];
        $orderbys = [];
        foreach ($cols as $name => $col ) {
            if (in_array($name, $ascCols)){
                array_push( $orderbys, [$col, "ASC"]);
            }
            if (in_array($name, $descCols)){
                array_push( $orderbys, [$col, "DESC"]);
            }
        }
        // Add orderby clauses, if given
        if (count($orderbys)){
            foreach ($orderbys as $orderby) {
                $col = $orderby[0];
                $direction = $orderby[1];
                $query->addOrderBy($col, $direction);
            }
        }
        
        // Paginate the resulsts
        $amount = 25;
        $offset = $amount * ($page - 1);
        $query->setFirstResult($offset)
            ->setMaxResults($amount);
        $rows = $query->getQuery()->getResult();

        
        // Loop through each row and create a new dict with the keys we want to show
        $responeSelect = [
            'id',
            'firstname',
            'fullLastname',
            'divisionName',
            'reserved',
            'checkedIn',
        ];
        $data = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($responeSelect as $key) {
                $item[$key] = $row[$key];
            }
            array_push($data, $item);
        }

        return new JsonResponse([
            'results' => $data,
            'amount' => $count,
            'pages' => floor($count / $amount) + 1
        ]);
    }
}
