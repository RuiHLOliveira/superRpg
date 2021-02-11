<?php

namespace App\Controller;

use App\Entity\Character;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CharactersController extends AbstractController
{

    /**
     * @Route("/characters", methods={"GET","HEAD"})
     */
    public function index(): Response
    {
        $characters = $this->getDoctrine()
        ->getRepository(Character::class)
        ->findAll();
        return new JsonResponse(compact('characters'));
    }

    /**
     * @Route("/characters", methods={"POST"})
     */
    public function store(Request $request): Response
    {
        $requestData = $request->toArray();
        $characters = new Character();
        $characters->setName($requestData['name']);
        $characters->setHp(100);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($characters);
        $em->flush();
        $characters->getId();
        
        return new JsonResponse(compact('characters'));
    }
}
