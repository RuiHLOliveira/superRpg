<?php

namespace App\Controller;

use App\Entity\Game;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GamesController extends AbstractController
{
    /**
     * @Route("/games", methods={"GET","HEAD"})
     */
    public function index(): Response
    {
        $games = $this->getDoctrine()
        ->getRepository(Game::class)
        ->findAll();
        return new JsonResponse(compact('games'));
    }

    /**
     * @Route("/games", methods={"POST"})
     */
    public function store(Request $request): Response
    {
        $requestData = $request->toArray();
        $user = $this->getUser();
        $game = new Game();
        $game->setName($requestData['name']);
        $game->setDescription($requestData['description']);
        $game->setUser($user);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();
        $game->getId();

        return new JsonResponse(compact('game'));
    }
}
